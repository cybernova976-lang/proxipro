<?php

namespace Tests\Feature\SavedSearch;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Le 18/11/2026, deux alertes ont été supprimées en production par le bouton
 * « Supprimer », sans que le fondateur ait voulu les supprimer : le bouton
 * agissait au premier clic, sans confirmation, à côté de « Ouvrir ».
 *
 * Toute suppression d'alerte demande maintenant une confirmation, et le script
 * qui la pose est chargé une seule fois, par le gabarit, pour toutes les pages.
 */
class AlertDeletionConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_deleting_an_alert_from_my_alerts_asks_for_confirmation(): void
    {
        $reader = $this->reader();
        $this->actingAs($reader)->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte']);
        $alert = SavedSearch::sole();

        $html = $this->get(route('saved-searches.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<form method="POST" action="'.preg_quote(route('saved-searches.destroy', $alert), '#').'"[^>]*'
            .'data-pk-confirm="Supprimer l’alerte « Offres à Mayotte » \? Vous ne serez plus prévenu\."#u',
            $html
        );
        $this->assertSame(1, substr_count($html, 'js/confirm-submit.js'));
    }

    public function test_stopping_a_provider_alert_asks_for_confirmation_on_the_feed_and_on_the_offer(): void
    {
        $reader = $this->reader();
        $provider = User::factory()->create(['name' => 'Atelier Baobab', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
        $offer = Ad::create(['user_id' => $provider->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
        $this->actingAs($reader)->post(route('saved-searches.store'), ['provider' => $provider->id]);

        $question = 'data-pk-confirm="Ne plus être prévenu des nouvelles offres de Atelier Baobab ?"';

        $feed = $this->get(route('feed'))->assertOk()->assertSee($question, false)->getContent();
        $this->assertSame(1, substr_count($feed, 'js/confirm-submit.js'));
        // Créer une alerte reste un clic simple : seule la suppression est confirmée.
        $this->assertSame(1, substr_count($feed, 'data-pk-confirm="Ne plus être prévenu'));

        $this->get(route('ads.show', $offer))->assertOk()->assertSee($question, false);
    }

    public function test_the_confirmation_script_is_loaded_once_on_every_page_that_needs_it(): void
    {
        $member = $this->reader();

        foreach (['feed', 'saved-searches.index', 'service-orders.index', 'proposals.index', 'ads.index'] as $route) {
            $html = $this->actingAs($member)->get(route($route))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'js/confirm-submit.js'), "Script de confirmation sur {$route}.");
        }
    }

    public function test_no_page_script_asks_the_question_a_second_time(): void
    {
        // L'accueil posait lui-même la question : avec le script commun, elle serait posée deux fois.
        $this->assertStringNotContainsString('window.confirm', File::get(public_path('js/feed.js')));
        $this->assertStringContainsString('pkConfirmSubmitReady', File::get(public_path('js/confirm-submit.js')));

        // Aucune vue ne recharge le script de son côté.
        $views = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php') && str_contains($file->getContents(), 'confirm-submit.js'))
            ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))->values()->all();
        $this->assertSame(['layouts/app.blade.php'], $views);
    }

    private function reader(): User
    {
        return User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);
    }
}
