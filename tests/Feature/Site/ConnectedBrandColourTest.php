<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une seule couleur de marque, l'indigo (#eef2ff → #312e81), partout où un
 * membre connecté passe : en-tête, publication d'une demande, messagerie.
 * Le bleu #3a86ff de l'ancien en-tête était un accident à ne pas propager.
 */
class ConnectedBrandColourTest extends TestCase
{
    use RefreshDatabase;

    public function test_connected_layout_demand_and_messaging_no_longer_use_the_old_blue(): void
    {
        $sources = [
            'resources/views/layouts/app.blade.php' => ['#3a86ff', '#2667cc', 'rgba(58, 134, 255'],
            'resources/views/demands/create.blade.php' => ['#3b82f6', '#2563eb'],
            'public/css/messaging.css' => ['#2563eb', '#087d86'],
            // Palette commune du feed (côté prestataire compris) et barre du bas.
            'public/css/feed.css' => ['#2563eb', '#3b82f6', '#1d4ed8', '#0f9f92'],
            'public/css/tabbar.css' => ['#2563eb', '#eff6ff'],
            'resources/views/ads/index.blade.php' => ['#2563eb', '#3b82f6', '#28a745', '#17a2b8'],
        ];

        foreach ($sources as $path => $banned) {
            $content = strtolower(file_get_contents(base_path($path)));
            foreach ($banned as $colour) {
                $this->assertStringNotContainsString($colour, $content, "{$path} utilise encore {$colour}.");
            }
        }

        $this->assertStringContainsString('--primary: #4f46e5;', file_get_contents(resource_path('views/layouts/app.blade.php')));
        $this->assertStringContainsString('--pk-600: #4f46e5;', file_get_contents(public_path('css/feed.css')));
    }

    /** Sur téléphone, la barre du bas porte « Publier » : l'en-tête ne le répète pas. */
    public function test_phone_header_does_not_repeat_the_tab_bar_publish_button(): void
    {
        $css = file_get_contents(public_path('css/tabbar.css'));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 900px\)\s*\{[^}]*\.pk-tabbar \{ display: grid; \}.*?body:has\(\.pk-tabbar\) \.mobile-service-request \{ display: none !important; \}/s',
            $css
        );
    }

    public function test_connected_pages_render_with_the_indigo_header(): void
    {
        $user = User::factory()->create();

        foreach (['feed', 'messages.index', 'demand.create'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertDontSee('#3a86ff', false);
        }
    }

    public function test_french_ads_address_redirects_to_the_listing_and_keeps_filters(): void
    {
        $this->get('/annonces?q=plombier')->assertStatus(301)
            ->assertRedirect(route('ads.index').'?q=plombier');
        $this->get('/annonces')->assertRedirect(route('ads.index'));
    }
}
