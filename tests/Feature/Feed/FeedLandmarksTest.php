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
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Offres à Mayotte', 'service_type' => 'offre', 'country' => 'Mayotte', 'territory_code' => '976']);
        $user->forceFill(['is_verified' => true])->save();
        $this->assertTrue($user->fresh()->hasVerifiedProfileBadge());

        $zone = $this->landmarks($user->fresh());
        $this->assertStringNotContainsString('À faire', $zone);
        $this->assertStringContainsString(route('saved-ads.index'), $zone);
        $this->assertStringContainsString('Mes favoris', $zone);
    }

    public function test_zone_has_no_counter_and_no_inline_style(): void
    {
        $zone = $this->landmarks($this->client());
        $visible = preg_replace('/<details class="pk-landmarks__how".*$/s', '', $zone);

        $this->assertDoesNotMatchRegularExpression('/>[^<]*\d[^<]*</', strip_tags($visible, '<a><span><li><h3><summary><button>'));
        $this->assertStringNotContainsString('style=', $zone);
    }
}
