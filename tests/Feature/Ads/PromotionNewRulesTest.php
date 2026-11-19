<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use App\Services\AdBumpService;
use App\Services\StripeCheckoutFulfillmentService;
use App\Support\AdDisclosure;
use App\Support\AdPromotionCatalog;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lot 2b : nouvelles règles de mise en avant (« À la une », « Remontée
 * automatique », Urgent pour toute la durée de la demande). Elles ne
 * s'appliquent qu'à partir de la date d'effet annoncée ; avant, rien ne change.
 */
class PromotionNewRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_nothing_changes_before_the_effective_date(): void
    {
        foreach ([null, now()->addDays(15)->format('Y-m-d')] as $date) {
            config(['feed.sponsored.effective_at' => $date]);
            $this->assertFalse(AdPromotionCatalog::newRulesActive());
            $this->assertSame(['boost_3', 'boost_7', 'boost_15', 'boost_30'], array_keys(AdPromotionCatalog::boosts()));
            $this->assertSame(14.00, AdPromotionCatalog::urgent()['price_euros']);
        }

        $owner = User::factory()->create();
        $offer = $this->ad($owner, ['bump_every_days' => 1, 'bump_until' => now()->addDays(3)]);

        $this->actingAs($owner)->get(route('boost.show', $offer))->assertOk()
            ->assertSee('boost-container', false)->assertDontSee('pk-boost__pay', false);
        $this->from(route('boost.show', $offer))->post(route('boost.purchase.stripe', $offer), ['package' => 'bump_daily_7'])
            ->assertSessionHasErrors('package');

        // La commande planifiée ne remonte rien tant que les règles ne sont pas en vigueur.
        $this->assertSame(['purchased' => 0, 'subscribers' => 0, 'ended' => 0], app(AdBumpService::class)->run());
        $this->assertNull($offer->fresh()->bumped_at);
        $this->assertNotContains(ProviderSubscriptionPlans::WEEKLY_BUMP_FEATURE, ProviderSubscriptionPlans::get('monthly')['features']);
        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertSee('de nouvelles règles de mise en avant s’appliqueront')
            ->assertDontSee('Pour une offre, trois options existent');
    }

    public function test_the_catalog_switches_on_the_effective_date(): void
    {
        $this->activate();

        $this->assertSame(['boost_7' => 5.90, 'boost_30' => 14.90],
            array_map(fn ($pack) => $pack['price_euros'], AdPromotionCatalog::boosts()));
        $this->assertSame(['bump_daily_7' => 2.90, 'bump_daily_30' => 7.90, 'bump_weekly_8' => 3.90],
            array_map(fn ($pack) => $pack['price_euros'], AdPromotionCatalog::bumps()));
        $this->assertSame(['price_points' => 0, 'price_euros' => 2.50, 'duration_days' => null], AdPromotionCatalog::urgent());
        $this->assertContains(ProviderSubscriptionPlans::WEEKLY_BUMP_FEATURE, ProviderSubscriptionPlans::get('monthly')['features']);

        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertSee('Pour une offre, trois options existent')
            ->assertSee('Remontée automatique')
            ->assertSee('celle de la dernière remontée')
            ->assertSee('elle ne donne ni mention dans les listes, ni priorité')
            ->assertDontSee('Évolution annoncée');
    }

    public function test_purchase_page_lists_every_option_with_a_single_payment_button(): void
    {
        $this->activate();
        $owner = User::factory()->create();
        $offer = $this->ad($owner);

        $html = $this->actingAs($owner)->get(route('boost.show', $offer))->assertOk()
            ->assertSee('À la une')->assertSee('Remontée automatique')
            ->assertSee('5,90 €')->assertSee('14,90 €')->assertSee('2,90 €')->assertSee('7,90 €')->assertSee('3,90 €')
            ->assertSee(route('legal.platform-rules').'#classement', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'class="pk-boost__pay"'), 'Un seul bouton de paiement.');
        $this->assertSame(5, substr_count($html, 'type="radio" name="package"'));
        $this->assertDoesNotMatchRegularExpression('/garanti|\bx\s?10\b|\bVIP\b/iu',
            substr($html, strpos($html, '<div class="pk-boost">'), 6000));

        // La page « votre annonce est publiée » est la même page d'achat.
        $this->get(route('boost.after-creation', $offer))->assertOk()
            ->assertSee('Votre annonce est publiée')->assertSee('class="pk-boost__pay"', false);
    }

    public function test_a_subscriber_is_not_sold_what_the_subscription_already_gives(): void
    {
        $this->activate();
        $owner = User::factory()->create();
        $owner->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => null]);
        $offer = $this->ad($owner);

        $html = $this->actingAs($owner)->get(route('boost.show', $offer))->assertOk()
            ->assertSee('Déjà incluse dans votre abonnement Premium')
            ->assertSee('2,32 €') // remontée quotidienne 7 jours, −20 %
            ->getContent();
        // Seules les deux remontées quotidiennes restent en vente.
        $this->assertSame(2, substr_count($html, 'type="radio" name="package"'));

        $this->from(route('boost.show', $offer));
        $this->post(route('boost.purchase.stripe', $offer), ['package' => 'boost_7'])->assertSessionHas('error');
        $this->post(route('boost.purchase.stripe', $offer), ['package' => 'bump_weekly_8'])->assertSessionHas('error');
    }

    public function test_buying_a_bump_opens_a_checkout_at_the_listed_price(): void
    {
        $this->activate();
        config(['services.stripe.secret' => 'sk_test_simulated']);
        $owner = User::factory()->create();
        $offer = $this->ad($owner);

        \Mockery::mock('alias:Stripe\\Checkout\\Session')->shouldReceive('create')->once()
            ->andReturnUsing(function (array $params) use ($offer) {
                $this->assertSame(290, $params['line_items'][0]['price_data']['unit_amount']);
                $this->assertSame('bump', $params['metadata']['type']);
                $this->assertSame('bump_daily_7', $params['metadata']['package']);
                $this->assertSame($offer->id, $params['metadata']['ad_id']);

                return (object) ['url' => 'https://checkout.example.test/bump'];
            });

        $this->actingAs($owner)->post(route('boost.purchase.stripe', $offer), ['package' => 'bump_daily_7'])
            ->assertRedirect('https://checkout.example.test/bump');
        $this->assertNull($offer->fresh()->bumped_at, 'Rien n’est appliqué avant le paiement.');
    }

    public function test_a_paid_bump_is_applied_once_and_then_repeats_until_its_term(): void
    {
        $this->activate();
        $owner = User::factory()->create();
        $offer = $this->ad($owner, ['created_at' => now()->subDays(20)]);
        $session = $this->paidSession($owner, $offer, 'cs_test_bump', 'bump', 290, 'bump_daily_7');

        $fulfillment = app(StripeCheckoutFulfillmentService::class);
        $this->assertSame('processed', $fulfillment->fulfill($session));
        $this->assertSame('duplicate', $fulfillment->fulfill($session));
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', ['stripe_session_id' => 'cs_test_bump', 'type' => 'AD_BUMP']);

        $fresh = $offer->fresh();
        $this->assertNotNull($fresh->bumped_at);
        $this->assertSame(1, (int) $fresh->bump_every_days);
        $this->assertTrue($fresh->bump_until->between(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()));
        $this->assertTrue(AdDisclosure::isBumped($fresh));

        // Moins d'un jour après : pas encore d'échéance.
        $this->assertSame(0, app(AdBumpService::class)->run()['purchased']);
        $this->travel(25)->hours();
        $this->assertSame(1, app(AdBumpService::class)->run()['purchased']);
        $this->assertTrue($offer->fresh()->bumped_at->greaterThan($fresh->bumped_at));

        // Au terme : plus rien de programmé, la dernière remontée reste acquise.
        $this->travel(7)->days();
        $result = app(AdBumpService::class)->run();
        $this->assertSame(0, $result['purchased']);
        $this->assertSame(1, $result['ended']);
        $this->assertNull($offer->fresh()->bump_until);
    }

    public function test_a_bump_is_refused_for_a_request_and_for_a_wrong_amount(): void
    {
        $this->activate();
        $owner = User::factory()->create();
        $demand = $this->ad($owner, ['service_type' => 'demande']);
        $offer = $this->ad($owner);
        $fulfillment = app(StripeCheckoutFulfillmentService::class);

        foreach ([$this->paidSession($owner, $demand, 'cs_test_bump_demand', 'bump', 290, 'bump_daily_7'),
            $this->paidSession($owner, $offer, 'cs_test_bump_cheap', 'bump', 100, 'bump_daily_7')] as $session) {
            try {
                $fulfillment->fulfill($session);
                $this->fail('Le paiement aurait dû être refusé.');
            } catch (\DomainException) {
                $this->assertNull(Ad::find($session['metadata']['ad_id'])->bumped_at);
            }
        }
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_subscribers_offers_are_bumped_every_week_without_purchase(): void
    {
        $this->activate();
        $subscriber = User::factory()->create();
        $subscriber->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subMonth(), 'ends_at' => null]);
        $old = $this->ad($subscriber, ['created_at' => now()->subDays(10)]);
        $recent = $this->ad($subscriber, ['title' => 'Offre récente', 'created_at' => now()->subDays(2)]);
        $other = $this->ad(User::factory()->create(), ['title' => 'Offre sans abonnement', 'created_at' => now()->subDays(10)]);

        $this->assertSame(1, app(AdBumpService::class)->run()['subscribers']);
        $this->assertNotNull($old->fresh()->bumped_at);
        $this->assertNull($recent->fresh()->bumped_at);
        $this->assertNull($other->fresh()->bumped_at);

        // Une offre d'abonné est déjà signalée « Sponsorisé » : pas de double mention.
        $listed = app(\App\Services\ClientFeedOffersService::class)->addPromotedColumn(Ad::query())->find($old->id);
        $this->assertTrue(AdDisclosure::isSponsored($listed));
        $this->assertFalse(AdDisclosure::isBumped($listed));
    }

    public function test_the_ads_list_puts_sponsored_offers_first_then_the_most_recent_or_bumped(): void
    {
        $this->activate();
        $author = fn () => User::factory()->create();
        $plain = $this->ad($author(), ['title' => 'Offre ordinaire récente', 'created_at' => now()->subDay()]);
        $bumped = $this->ad($author(), ['title' => 'Offre remontée', 'created_at' => now()->subDays(30), 'bumped_at' => now()->subHour()]);
        $featured = $this->ad($author(), ['title' => 'Offre à la une', 'created_at' => now()->subDays(40),
            'is_boosted' => true, 'boost_end' => now()->addDays(5), 'boost_type' => 'boost_7']);

        $html = $this->get(route('ads.index'))->assertOk()
            ->assertSee('id="adsRankingDisclosure"', false)
            ->assertSee(AdDisclosure::BUMPED_LABEL)
            ->getContent();

        $positions = array_map(fn (Ad $ad) => strpos($html, e($ad->title)), [$featured, $bumped, $plain]);
        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all(), 'Ordre : à la une, remontée, puis ordinaire.');

        // Un tri choisi par le visiteur n'est jamais modifié par une option payante.
        $this->get(route('ads.index', ['sort' => 'price_low']))->assertOk()
            ->assertDontSee('id="adsRankingDisclosure"', false);
    }

    public function test_only_the_paid_urgent_option_is_promoted_and_it_lasts_as_long_as_the_request(): void
    {
        $this->activate();
        $client = User::factory()->create();
        $expires = now()->addDays(21)->startOfMinute();
        $paid = $this->ad($client, ['service_type' => 'demande', 'title' => 'Demande urgente payée', 'expires_at' => $expires]);
        $free = $this->ad($client, ['service_type' => 'demande', 'title' => 'Demande urgence déclarée', 'is_urgent' => true]);

        $this->actingAs($client)->get(route('boost.show', $paid))->assertOk()
            ->assertSee('Option Urgent')->assertSee('2,50 €')
            ->assertSee(route('ads.make-urgent.stripe', $paid), false)
            ->assertDontSee('À la une');

        $session = $this->paidSession($client, $paid, 'cs_test_urgent', 'urgent', 250);
        $this->assertSame('processed', app(StripeCheckoutFulfillmentService::class)->fulfill($session));
        $this->assertTrue($paid->fresh()->urgent_until->equalTo($expires));

        $this->assertSame(AdDisclosure::PAID_URGENT_LABEL, AdDisclosure::urgentLabel($paid->fresh()));
        $this->assertNull(AdDisclosure::urgentLabel($free->fresh()));
        $this->assertTrue(AdDisclosure::declaresUrgency($free->fresh()));
        $this->assertFalse(AdDisclosure::declaresUrgency($paid->fresh()));

        // Le filtre « Urgentes » ne retient que l'option payante ; l'urgence déclarée reste lisible dans la demande.
        $this->get(route('ads.index', ['urgent' => 1]))->assertOk()
            ->assertSee($paid->title)->assertDontSee($free->title);
        $this->get(route('ads.show', $free))->assertOk()->assertSee('data-declared-urgency', false);
        $this->get(route('ads.show', $paid))->assertOk()->assertDontSee('data-declared-urgency', false)
            ->assertSee('Option Urgent jusqu’au '.$expires->format('d/m/Y'));
    }

    public function test_a_featured_purchase_extends_the_running_one(): void
    {
        $this->activate();
        $owner = User::factory()->create();
        $end = now()->addDays(3)->startOfMinute();
        $offer = $this->ad($owner, ['is_boosted' => true, 'boost_end' => $end, 'boost_type' => 'boost_7']);

        $session = $this->paidSession($owner, $offer, 'cs_test_featured', 'ad_boost', 590, 'boost_7');
        $this->assertSame('processed', app(StripeCheckoutFulfillmentService::class)->fulfill($session));
        $this->assertTrue($offer->fresh()->boost_end->equalTo($end->copy()->addDays(7)));

        // Un paiement engagé à l'ancien tarif juste avant la date d'effet reste honoré.
        $legacy = $this->paidSession($owner, $offer, 'cs_test_legacy', 'ad_boost', 400, 'boost_3');
        $this->assertSame('processed', app(StripeCheckoutFulfillmentService::class)->fulfill($legacy));
        $this->assertTrue($offer->fresh()->boost_end->equalTo($end->copy()->addDays(10)));
    }

    private function activate(): void
    {
        config(['feed.sponsored.effective_at' => now()->subDay()->format('Y-m-d')]);
        $this->assertTrue(AdPromotionCatalog::newRulesActive());
    }

    private function ad(User $owner, array $attributes = []): Ad
    {
        $dates = array_intersect_key($attributes, ['created_at' => true]);
        $ad = Ad::create(array_merge(['user_id' => $owner->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'],
            array_diff_key($attributes, $dates)));
        if ($dates !== []) {
            $ad->forceFill($dates)->saveQuietly();
        }

        return $ad->fresh();
    }

    /** @return array<string, mixed> */
    private function paidSession(User $owner, Ad $ad, string $id, string $type, int $cents, ?string $package = null): array
    {
        return [
            'id' => $id,
            'payment_status' => 'paid',
            'currency' => 'eur',
            'amount_total' => $cents,
            'metadata' => array_filter([
                'ad_id' => $ad->id,
                'user_id' => $owner->id,
                'type' => $type,
                'package' => $package,
                'expected_amount_cents' => $cents,
                'expected_currency' => 'eur',
            ]),
        ];
    }
}
