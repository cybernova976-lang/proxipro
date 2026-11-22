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

    public function test_every_client_space_page_swaps_tabs_for_the_bottom_bar_on_phones(): void
    {
        $client = $this->mayotteClient();

        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertSee('class="pk-tabbar"', false)
            ->assertSee('pk-route-feed', false)
            ->assertSee('aria-label="Mon espace particulier"', false);

        // Même modèle hors du feed : les deux navigations sont rendues, la feuille choisit.
        $this->actingAs($client)->get(route('messages.index'))->assertOk()
            ->assertSee('class="pk-tabbar"', false)
            ->assertSee('aria-label="Mon espace particulier"', false)
            ->assertDontSee('pk-route-feed', false);

        $css = preg_replace('/\s+/', ' ', file_get_contents(public_path('css/client-space.css')));
        $this->assertStringContainsString('@media (max-width: 639px) { .has-client-space .pk-client-space { display: none; }', $css);
        $this->assertStringContainsString('@media (min-width: 640px) { .has-client-space .pk-tabbar, .has-client-space .pk-tabbar-spacer { display: none; } }', $css);
        $this->assertStringContainsString('.has-client-space .pk-messaging { height: calc(100dvh - 68px); padding: 0 0 calc(80px + env(safe-area-inset-bottom)); }', $css,
            'Sur téléphone, la zone de saisie des messages doit rester au-dessus de la barre du bas.');
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

    public function test_a_free_slot_shows_an_offer_from_another_territory_before_falling_back_to_the_filler(): void
    {
        $client = $this->mayotteClient();
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $offer = fn (string $title, string $city, string $country) => Ad::create(['user_id' => $provider->id, 'title' => $title,
            'description' => 'Dépannage.', 'category' => 'Plombier', 'city' => $city, 'country' => $country, 'location' => $city,
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
        foreach (range(1, 5) as $i) {
            $offer("Offre locale {$i}", 'Mamoudzou', 'Mayotte');
        }
        $elsewhere = $offer('Offre de Rennes', 'Rennes', 'France');

        // 5 offres locales + 1 offre d'ailleurs : la grille est pleine, le bloc d'invitation disparaît.
        $html = $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertSee('Offre de Rennes')->assertSee('Autre territoire')
            ->assertSee('complétée par des offres d’autres territoires, signalées « Autre territoire »')
            ->assertDontSee('pk-feed-filler', false)
            ->getContent();

        // L'offre d'ailleurs vient toujours après les offres du territoire, et elle seule porte la mention.
        $this->assertGreaterThan(strrpos($html, 'Offre locale'), strpos($html, 'Offre de Rennes'));
        $this->assertSame(1, substr_count($html, 'pk-tag--elsewhere'));
        $this->assertSame(6, preg_match_all('/<article class="pk-ad\b/', $html));

        // 4 offres locales : une seule offre d'ailleurs existe, la dernière place garde l'invitation.
        Ad::where('title', 'Offre locale 5')->delete();
        $html = $this->get(route('feed'))->assertOk()->assertSee('Offre de Rennes')
            ->assertSee('class="pk-feed-filler"', false)->getContent();
        $this->assertSame(5, preg_match_all('/<article class="pk-ad\b/', $html));

        // Grille déjà pleine d'offres locales : aucune offre d'ailleurs, aucune mention dans le classement.
        foreach (range(5, 6) as $i) {
            $offer("Offre locale {$i}", 'Mamoudzou', 'Mayotte');
        }
        $this->get(route('feed'))->assertOk()
            ->assertDontSee('Offre de Rennes')->assertDontSee('Autre territoire')
            ->assertDontSee('complétée par des offres d’autres territoires');

        // Trois offres locales ou moins : présentation en liste, sans complément.
        Ad::where('title', 'like', 'Offre locale%')->whereNotIn('title', ['Offre locale 1', 'Offre locale 2'])->delete();
        $this->get(route('feed'))->assertOk()->assertDontSee('Offre de Rennes')->assertDontSee('Autre territoire');

        // Les règles publiées décrivent ce complément.
        $this->get(route('legal.platform-rules'))->assertOk()
            ->assertSee('complétées par des offres d’autres territoires')->assertSee('signalées « Autre territoire »');
        $this->assertNotNull($elsewhere->fresh());
    }

    public function test_an_incomplete_page_gets_a_honest_filler_card(): void
    {
        $client = $this->mayotteClient();
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        foreach (range(1, 5) as $i) {
            Ad::create(['user_id' => $provider->id, 'title' => "Offre {$i}", 'description' => 'Dépannage.',
                'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
                'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
        }

        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertSee('class="pk-feed-filler"', false)
            ->assertSee('Vous ne trouvez pas votre service ?')
            ->assertSee('les prestataires de Mayotte pourront vous faire une proposition');

        Ad::create(['user_id' => $provider->id, 'title' => 'Offre 6', 'description' => 'Dépannage.',
            'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
        $this->actingAs($client)->get(route('feed'))->assertOk()->assertDontSee('pk-feed-filler', false);
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
