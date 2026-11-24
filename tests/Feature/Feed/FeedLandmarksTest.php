<?php

namespace Tests\Feature\Feed;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Zone 7 du rail client : « À faire » seulement sur données réelles, aucun compteur. */
class FeedLandmarksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function client(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['is_service_provider' => false, 'user_type' => 'particulier'], $attributes));
    }

    private function landmarks(User $user): string
    {
        $html = $this->actingAs($user)->get(route('feed'))->assertOk()->getContent();
        $start = strpos($html, '<details class="pk-rcard pk-landmarks"');
        $this->assertNotFalse($start, 'La zone Repères doit être rendue.');

        return substr($html, $start, strpos($html, '</aside>', $start) - $start);
    }

    public function test_member_without_city_is_asked_for_it(): void
    {
        $zone = $this->landmarks($this->client(['city' => null, 'postal_code' => null]));

        $this->assertStringContainsString('À faire', $zone);
        $this->assertStringContainsString('Indiquer ma commune', $zone);
        $this->assertStringContainsString('Activer les notifications', $zone);
    }

    public function test_push_line_disappears_with_a_subscription(): void
    {
        $user = $this->client(['city' => 'Mamoudzou', 'postal_code' => '97600', 'country' => 'Mayotte']);
        $user->updatePushSubscription('https://push.example.test/abc', 'cle-publique', 'jeton', 'aesgcm');

        $zone = $this->landmarks($user);
        $this->assertStringNotContainsString('Activer les notifications', $zone);
        $this->assertStringNotContainsString('Indiquer ma commune', $zone);
    }

    public function test_nothing_due_hides_the_todo_block_but_keeps_the_links(): void
    {
        $user = $this->client(['city' => 'Mamoudzou', 'postal_code' => '97600', 'country' => 'Mayotte']);
        $user->updatePushSubscription('https://push.example.test/def', 'cle-publique', 'jeton', 'aesgcm');
        // L'alerte du territoire, telle que l'accueil la crée (une alerte sur un prestataire ne la remplace pas).
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Offres à Mayotte', 'service_type' => 'offres', 'country' => 'Mayotte', 'territory_code' => '976', 'is_active' => true]);
        $user->forceFill(['is_verified' => true])->save();
        $this->assertTrue($user->fresh()->hasVerifiedProfileBadge());

        $zone = $this->landmarks($user->fresh());
        $this->assertStringNotContainsString('À faire', $zone);
        $this->assertStringContainsString('href="'.route('profile.show').'"', $zone);
        $this->assertStringContainsString('>Profil</span>', $zone);
        $this->assertStringContainsString('href="'.route('ads.myads').'"', $zone);
        $this->assertStringContainsString('>Mes annonces</span>', $zone);
        $this->assertStringNotContainsString('Mes favoris', $zone);
        $this->assertStringNotContainsString('Mes alertes', $zone);
        $this->assertStringNotContainsString('Comment les offres sont classées', $zone);
    }

    public function test_zone_shows_no_counter_or_inline_style_even_with_active_alerts(): void
    {
        $user = $this->client();
        $zone = $this->landmarks($user);
        $visible = preg_replace('/<details class="pk-landmarks__how".*$/s', '', $zone);

        // Les raccourcis du profil et des annonces n'affichent pas de compteur.
        $this->assertDoesNotMatchRegularExpression('/>[^<]*\d[^<]*</', strip_tags($visible, '<a><span><li><h3><summary><button>'));
        $this->assertStringNotContainsString('style=', $zone);
        $this->assertStringNotContainsString('data-pk-alert-count', $zone);

        // Une alerte existante ne réintroduit pas le raccourci retiré de Repères.
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Offres à Mayotte', 'service_type' => 'offres', 'country' => 'Mayotte', 'territory_code' => '976', 'is_active' => true]);
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Alerte arrêtée', 'is_active' => false]);
        $zone = $this->landmarks($user->fresh());
        $this->assertStringNotContainsString('Mes alertes', $zone);
        $this->assertStringNotContainsString('data-pk-alert-count', $zone);
        $this->assertStringNotContainsString('style=', $zone);
    }
}
