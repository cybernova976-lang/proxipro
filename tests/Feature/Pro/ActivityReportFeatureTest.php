<?php

namespace Tests\Feature\Pro;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProQuote;
use App\Models\ProSubscription;
use App\Models\Review;
use App\Models\SavedAd;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Notifications\MonthlyActivityReportNotification;
use App\Services\ProviderActivityReport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rapport d'activité mensuel (Premium) : réservé aux abonnés, uniquement des
 * chiffres mesurés, jamais ceux d'un autre prestataire, un e-mail par mois.
 */
class ActivityReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_reserved_to_premium_and_shows_no_figure_without_subscription(): void
    {
        $this->travelTo('2026-06-01 10:00:00');
        $provider = $this->provider();
        $client = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
        $this->travelTo('2026-10-15 10:00:00');
        $this->profileView($provider, 'a:visiteur-1');
        $this->travelTo('2026-11-12 10:00:00');

        $this->actingAs($client)->get(route('pro.report'))->assertRedirect(route('pro.dashboard'));

        $this->actingAs($provider)->get(route('pro.report'))->assertOk()
            ->assertSee('compris dans Premium')
            ->assertSee('Rapport mensuel')
            ->assertDontSee('class="pk-metric"', false)
            ->assertDontSee('Vues de votre profil')
            ->assertViewMissing('report');

        // Un abonnement arrivé à son terme ne donne plus accès aux chiffres.
        ProSubscription::create(['user_id' => $provider->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subMonths(2), 'ends_at' => now()->subDay()]);
        $this->get(route('pro.report'))->assertOk()->assertViewMissing('report');
    }

    public function test_subscriber_sees_only_measured_figures_of_their_own_activity(): void
    {
        [$provider, $other, $client] = $this->seedActivity();
        $this->travelTo('2026-11-12 10:00:00');
        $this->subscribe($provider);

        $response = $this->actingAs($provider)->get(route('pro.report'))->assertOk()
            ->assertSee('octobre 2026')
            ->assertSee('+1 par rapport à septembre')
            ->assertSee('90,00')
            ->assertDontSee('Mois en cours');

        $metrics = collect($response->viewData('report')['sections'])->flatMap(fn ($section) => $section['metrics'])->keyBy('key');
        $expected = ['profile_views' => 3, 'favorites' => 1, 'contacts' => 1, 'proposals_sent' => 2, 'proposals_accepted' => 1,
            'orders_received' => 2, 'orders_completed' => 1, 'revenue' => 90.0, 'reviews' => 1, 'rating' => 4.0,
            'quotes_sent' => 1, 'quotes_accepted' => 0, 'invoices_paid' => 0.0];
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $metrics[$key]['value'], $key);
        }
        $this->assertSame(2, $metrics['profile_views']['previous']);
        $this->assertEquals(1, $metrics['profile_views']['delta']);
        $this->assertSame(1, $metrics['proposals_sent']['previous']);

        // Avant le début du comptage dédoublonné des vues : « non mesuré », jamais zéro.
        $july = $this->get(route('pro.report', ['mois' => '2026-07']))->assertOk()->assertSee('Non mesuré');
        $julyViews = collect($july->viewData('report')['sections'])->flatMap(fn ($section) => $section['metrics'])->firstWhere('key', 'profile_views');
        $this->assertNull($julyViews['value']);

        $this->get(route('pro.report', ['mois' => '2026-11']))->assertOk()->assertSee('Mois en cours');
        // Pas de mois antérieur à l'inscription, ni de mois futur.
        $this->get(route('pro.report', ['mois' => '2026-05']))->assertNotFound();
        $this->get(route('pro.report', ['mois' => '2026-12']))->assertNotFound();

        // Un particulier prestataire n'a pas de section devis et factures.
        $other->update(['user_type' => 'particulier', 'account_type' => 'particulier']);
        $this->subscribe($other);
        $otherMetrics = collect($this->actingAs($other)->get(route('pro.report'))->assertOk()
            ->assertDontSee('Vos devis et factures')->viewData('report')['sections'])
            ->flatMap(fn ($section) => $section['metrics'])->keyBy('key');
        $this->assertSame(5, $otherMetrics['profile_views']['value']);
        $this->assertSame(0, $otherMetrics['favorites']['value']);
        $this->assertFalse($otherMetrics->has('quotes_sent'));
    }

    public function test_monthly_email_is_sent_once_to_subscribers_with_measured_activity(): void
    {
        [$provider, $other] = $this->seedActivity();
        $this->travelTo('2026-06-01 10:00:00');
        $idle = $this->provider();
        $muted = $this->provider(['pro_notifications_email' => false]);
        $this->travelTo('2026-10-20 10:00:00');
        $this->profileView($muted, 'a:visiteur-9');
        $this->travelTo('2026-11-12 05:30:00');
        foreach ([$provider, $idle, $muted] as $subscriber) {
            $this->subscribe($subscriber);
        }

        $this->artisan('pro:send-activity-reports')->assertSuccessful();
        $this->artisan('pro:send-activity-reports')->assertSuccessful();

        $sent = fn (User $user) => $user->notifications()->where('type', MonthlyActivityReportNotification::class)->get();
        $this->assertCount(1, $sent($provider), 'Un seul rapport par mois.');
        $this->assertSame('2026-10', $sent($provider)->first()->data['month']);
        $this->assertStringContainsString('Vues de votre profil : 3', $sent($provider)->first()->data['message']);
        $this->assertStringContainsString(route('pro.report', ['mois' => '2026-10']), $sent($provider)->first()->data['action_url']);
        $this->assertCount(0, $sent($other), 'Pas de rapport sans abonnement.');
        $this->assertCount(0, $sent($idle), 'Pas de rapport pour un mois sans activité mesurée.');
        $this->assertCount(1, $sent($muted));

        $report = app(ProviderActivityReport::class)->forMonth($provider, now()->subMonth());
        $notification = new MonthlyActivityReportNotification($report);
        $this->assertSame(['database', 'mail'], $notification->via($provider));
        $this->assertSame(['database'], $notification->via($muted), 'La préférence e-mail est respectée.');
        $mail = implode("\n", $notification->toMail($provider)->introLines);
        $this->assertStringContainsString('Montant versé : 90,00 €', $mail);
        $this->assertStringNotContainsString('Devis acceptés', $mail, 'Les chiffres nuls ne figurent pas dans l’e-mail.');

        // Le mois suivant, un nouveau rapport part seulement s'il y a eu de l'activité.
        $this->travelTo('2026-11-20 10:00:00');
        $this->profileView($provider, 'a:visiteur-novembre');
        $this->travelTo('2026-12-02 05:30:00');
        $this->artisan('pro:send-activity-reports')->assertSuccessful();
        $this->assertCount(2, $sent($provider));
        $this->assertCount(1, $sent($muted));
    }

    public function test_report_command_is_scheduled_every_morning_in_mayotte_time(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'pro:send-activity-reports'));

        $this->assertNotNull($event);
        $this->assertSame('30 8 * * *', $event->expression);
        $this->assertSame('Indian/Mayotte', (string) $event->timezone);
    }

    /** @return array{0: User, 1: User, 2: User} prestataire suivi, autre prestataire, client */
    private function seedActivity(): array
    {
        $this->travelTo('2026-06-01 10:00:00');
        $provider = $this->provider();
        $other = $this->provider();
        $client = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);

        $this->travelTo('2026-09-10 10:00:00');
        $this->profileView($provider, 'a:visiteur-1');
        $this->profileView($provider, 'u:'.$client->id);
        $this->proposal($this->ad($client, 'demande'), $provider);

        $this->travelTo('2026-10-15 10:00:00');
        foreach (['a:visiteur-1', 'a:visiteur-2', 'u:'.$client->id] as $key) {
            $this->profileView($provider, $key);
        }
        foreach (range(1, 5) as $i) {
            $this->profileView($other, 'a:autre-'.$i);
        }
        $offer = $this->ad($provider, 'offre');
        SavedAd::create(['user_id' => $client->id, 'ad_id' => $offer->id]);
        SavedAd::create(['user_id' => $provider->id, 'ad_id' => $offer->id]);

        $received = Conversation::create(['user1_id' => $client->id, 'user2_id' => $provider->id]);
        Message::create(['conversation_id' => $received->id, 'sender_id' => $client->id, 'content' => 'Bonjour, êtes-vous disponible ?']);
        Message::create(['conversation_id' => $received->id, 'sender_id' => $provider->id, 'content' => 'Oui.']);
        $started = Conversation::create(['user1_id' => $provider->id, 'user2_id' => $other->id]);
        Message::create(['conversation_id' => $started->id, 'sender_id' => $provider->id, 'content' => 'Bonjour.']);

        $this->proposal($this->ad($client, 'demande'), $provider);
        $accepted = $this->proposal($this->ad($client, 'demande'), $provider);
        $accepted->update(['status' => ServiceProposal::STATUS_ACCEPTED, 'responded_at' => now()]);

        $this->order($offer, $client, $provider, 'A', ServiceOrder::STATUS_COMPLETED, ['released_at' => now()]);
        $this->order($offer, $client, $provider, 'B', ServiceOrder::STATUS_PENDING_ACCEPTANCE);
        $this->order($this->ad($other, 'offre'), $client, $other, 'C', ServiceOrder::STATUS_COMPLETED, ['released_at' => now()]);

        Review::create(['reviewer_id' => $client->id, 'reviewed_user_id' => $provider->id, 'rating' => 4, 'comment' => 'Travail soigné.']);
        ProQuote::create(['user_id' => $provider->id, 'quote_number' => 'DEV-TEST-1', 'client_name' => 'Client', 'subject' => 'Pose',
            'items' => [['description' => 'Pose', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 100, 'status' => 'sent', 'sent_at' => now()]);

        return [$provider, $other, $client];
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'email_notifications' => true, 'pro_notifications_email' => true, 'is_active' => true], $attributes));
    }

    private function subscribe(User $user): void
    {
        ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }

    /** Insertion identique à celle de ProfileViewCounter (une ligne par visiteur et par jour). */
    private function profileView(User $profile, string $viewerKey): void
    {
        DB::table('profile_views')->insert(['profile_user_id' => $profile->id, 'viewer_key' => $viewerKey,
            'viewed_on' => now()->toDateString(), 'created_at' => now()]);
    }

    private function ad(User $owner, string $type): Ad
    {
        return Ad::create(['user_id' => $owner->id, 'title' => 'Annonce '.$type.' '.uniqid(), 'description' => 'Description de test.',
            'main_category' => 'Bricolage & Travaux', 'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte',
            'location' => 'Mamoudzou', 'service_type' => $type, 'status' => 'active', 'visibility' => 'public']);
    }

    private function proposal(Ad $demand, User $provider): ServiceProposal
    {
        return ServiceProposal::create(['ad_id' => $demand->id, 'provider_id' => $provider->id, 'amount' => 80,
            'message' => 'Disponible cette semaine.', 'status' => ServiceProposal::STATUS_PENDING]);
    }

    private function order(Ad $ad, User $buyer, User $seller, string $suffix, string $status, array $extra = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge(['order_number' => 'CMD-RAPPORT-'.$suffix, 'ad_id' => $ad->id, 'buyer_id' => $buyer->id,
            'seller_id' => $seller->id, 'amount' => 100, 'commission_amount' => 10, 'seller_amount' => 90, 'status' => $status,
            'payment_status' => $status === ServiceOrder::STATUS_COMPLETED ? ServiceOrder::PAYMENT_RELEASED : 'pending'], $extra));
    }
}
