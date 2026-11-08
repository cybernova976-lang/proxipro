<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Accueil client recomposé (lot 3) : navigation téléphone, rail sans contenu
 * payant, et règles de style de CLAUDE.md vérifiées dans les feuilles.
 */
class FeedClientLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function mayotteClient(): User
    {
        return User::factory()->create(['is_service_provider' => false, 'user_type' => 'particulier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
    }

    public function test_feed_renders_the_bottom_tab_bar_and_other_client_pages_do_not(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertSee('class="pk-tabbar"', false)
            ->assertSee('pk-route-feed', false)
            ->assertSee('aria-label="Mon espace particulier"', false);

        $this->actingAs($client)->get(route('messages.index'))->assertOk()
            ->assertDontSee('class="pk-tabbar"', false)
            ->assertDontSee('pk-route-feed', false);
    }

    public function test_rail_never_shows_paid_content(): void
    {
        $client = $this->mayotteClient();
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true,
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600', 'plan' => 'premium',
            'subscription_end' => now()->addMonth()]);
        Ad::create(['user_id' => $provider->id, 'title' => 'Plomberie boostée', 'description' => 'Dépannage.',
            'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public',
            'is_boosted' => true, 'boost_end' => now()->addDays(3)]);

        $html = $this->actingAs($client)->get(route('feed'))->assertOk()->getContent();
        $start = strpos($html, '<aside class="pk-rail"');
        $rail = substr($html, $start, strpos($html, '</aside>', $start) - $start);

        foreach (['Sponsorisé', 'pk-tag--sponsored', 'Boosté', 'Plomberie boostée', 'class="pk-ad'] as $paid) {
            $this->assertStringNotContainsString($paid, $rail, "Le rail ne doit pas contenir « {$paid} ».");
        }
    }

    /** Couleurs et points de rupture des styles client (CLAUDE.md §4). */
    public function test_client_styles_use_the_indigo_ramp_and_the_three_breakpoints(): void
    {
        $feed = file_get_contents(public_path('css/feed.css'));
        $start = strpos($feed, '/* lot3:client:debut */');
        $end = strpos($feed, '/* lot3:client:fin */');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $sheets = [
            'feed.css (section client)' => substr($feed, $start, $end - $start),
            'client-space.css' => file_get_contents(public_path('css/client-space.css')),
        ];

        foreach ($sheets as $name => $css) {
            foreach (['#3a86ff', '#e76f51', '#2563eb', '#0f9f92'] as $banned) {
                $this->assertStringNotContainsStringIgnoringCase($banned, $css, "{$name} contient {$banned}.");
            }
            preg_match_all('/@media[^{]*\((?:min|max)-width:\s*(\d+)px\)/', $css, $matches);
            foreach ($matches[1] as $width) {
                $this->assertContains((int) $width, [639, 640, 1023, 1024, 1179, 1180], "{$name} : point de rupture {$width} px.");
            }
        }
    }
}
