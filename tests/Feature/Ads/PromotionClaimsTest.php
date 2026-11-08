<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use App\Support\AdPromotionCatalog;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Piste C du lot 1 : les ventes de boost et d'abonnement ne promettent que
 * des effets réellement appliqués par le code.
 */
class PromotionClaimsTest extends TestCase
{
    use RefreshDatabase;

    /** Promesses retirées : non mesurées, ou contraires au classement réel. */
    private const FORBIDDEN = [
        '/garanti/iu' => '« garanti »',
        '/\bx\s?10\b/iu' => '« x10 »',
        '/\b5\s?x\b/iu' => '« 5x »',
        '/Professionnels Premium/iu' => '« Professionnels Premium »',
        '/\bVIP\b/u' => '« VIP »',
        '/inspire confiance/iu' => '« inspire confiance »',
        '/Priorité dans les suggestions/iu' => '« Priorité dans les suggestions »',
    ];

    /** Avantages annoncés qui n'existent pas dans le code. */
    private const NOT_IMPLEMENTED = [
        '/Position prioritaire/iu',
        '/Priorité renforcée/iu',
        '/Mise en avant pendant 12 mois/iu',
        '/Support prioritaire/iu',
        '/Export comptable/iu',
        '/Badge .{0,3}Premium annuel/iu',
        '/Badge .{0,3}Premium.{0,3} doré/iu',
        '/notifications/iu',
        '/Meilleur rapport/iu',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_boost_catalog_promises_only_the_sponsored_mention_and_feed_priority(): void
    {
        foreach (AdPromotionCatalog::boosts() as $key => $package) {
            $text = implode("\n", [$package['name'], $package['description'], ...$package['features']]);
            $this->assertNoForbiddenClaim($text, 'catalogue '.$key);
            $this->assertNoUnimplementedClaim($text, 'catalogue '.$key);
            $this->assertStringContainsString('Sponsorisé', $text);
            $this->assertStringContainsString($package['duration_days'].' jours', $package['description']);
        }
    }

    public function test_subscription_plans_promise_only_implemented_benefits(): void
    {
        foreach (ProviderSubscriptionPlans::all() as $key => $plan) {
            $text = implode("\n", [$plan['description'] ?? '', ...($plan['features'] ?? [])]);
            $this->assertNoForbiddenClaim($text, 'abonnement '.$key);
            $this->assertNoUnimplementedClaim($text, 'abonnement '.$key);
        }
    }

    public function test_rendered_boost_page_has_no_unmeasured_promise_and_no_refresh_offer(): void
    {
        [$owner, $ad] = $this->ownerWithOffer();
        $owner->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        // Boost expiré : c'est le cas où l'ancienne page proposait la remontée.
        $ad->forceFill(['is_boosted' => false, 'boost_end' => now()->subDay(), 'boost_type' => 'boost_3'])->save();

        $response = $this->actingAs($owner)->get(route('boost.show', $ad))->assertOk();
        $content = $this->between($response->getContent(), '<div class="boost-container">', '<footer class="pk-legal-footer">');

        $this->assertNoForbiddenClaim($content, 'page boost');
        $this->assertNoUnimplementedClaim($content, 'page boost');
        $this->assertStringContainsString('Sponsorisé', $content);
        $this->assertStringContainsString(route('legal.platform-rules').'#classement', $content);
        $this->assertStringNotContainsString(route('ads.refresh', $ad), $content);
        $this->assertStringNotContainsString(route('ads.refresh.stripe', $ad), $content);
        $this->assertDoesNotMatchRegularExpression('/rafra[iî]ch/iu', $content);
    }

    public function test_boost_offer_after_publication_has_no_unmeasured_promise(): void
    {
        [$owner, $ad] = $this->ownerWithOffer();

        $response = $this->actingAs($owner)->get(route('boost.after-creation', $ad))->assertOk();
        $content = $this->between($response->getContent(), '<div class="success-container">', '<footer class="pk-legal-footer">');

        $this->assertNoForbiddenClaim($content, 'page après publication');
        $this->assertStringContainsString('Sponsorisé', $content);
    }

    public function test_request_is_never_promised_the_client_home_effect_of_a_boost(): void
    {
        $owner = User::factory()->create(['available_points' => 50]);
        $demand = Ad::create(['user_id' => $owner->id, 'title' => 'Cherche un plombier',
            'description' => 'Fuite sous l’évier de la cuisine.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public']);

        $this->actingAs($owner)->get(route('boost.show', $demand))->assertRedirect(route('ads.show', $demand));
        $response = $this->get(route('boost.after-creation', $demand))->assertOk();
        $content = $this->between($response->getContent(), '<div class="success-container">', '<footer class="pk-legal-footer">');

        $this->assertStringNotContainsString('accueil des clients', $content);
        $this->assertStringNotContainsString('Sponsorisé', $content);
    }

    private function assertNoForbiddenClaim(string $text, string $where): void
    {
        foreach (self::FORBIDDEN as $pattern => $label) {
            $this->assertDoesNotMatchRegularExpression($pattern, $text, $label.' figure encore dans '.$where.'.');
        }
    }

    private function assertNoUnimplementedClaim(string $text, string $where): void
    {
        foreach (self::NOT_IMPLEMENTED as $pattern) {
            $this->assertDoesNotMatchRegularExpression($pattern, $text, 'Avantage non implémenté dans '.$where.'.');
        }
    }

    private function between(string $html, string $start, string $end): string
    {
        $from = strpos($html, $start);
        $this->assertNotFalse($from, 'Le contenu principal est rendu.');
        $to = strpos($html, $end, $from);

        return substr($html, $from, $to === false ? null : $to - $from);
    }

    /** @return array{0: User, 1: Ad} */
    private function ownerWithOffer(): array
    {
        $owner = User::factory()->create(['available_points' => 50]);
        $ad = Ad::create(['user_id' => $owner->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);

        return [$owner, $ad];
    }
}
