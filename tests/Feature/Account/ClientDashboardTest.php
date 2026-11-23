<?php

namespace Tests\Feature\Account;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tableau de bord du particulier : la page n'affiche que du contenu (compteurs
 * mesurés, demandes en cours, alertes que l'on arrête d'un clic) ; les pages de
 * l'espace sont dans le panneau latéral, commun à toutes ces pages.
 */
class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['user_type' => 'particulier', 'is_service_provider' => false,
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600'], $attributes));
    }

    public function test_the_dashboard_is_private_and_groups_every_page_of_the_space(): void
    {
        $this->get(route('client.dashboard'))->assertRedirect(route('login'));

        $member = $this->member();
        $response = $this->actingAs($member)->get(route('client.dashboard'))->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('pkBoardRequestsTitle', false)->assertSee('pkBoardAlertsTitle', false)
            ->assertSee('js/client-space-menu.js', false)
            ->assertDontSee('id="sidebarNav"', false)->assertSee('class="pk-tabbar"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);

        // La page elle-même n'est plus un menu : aucune navigation à l'intérieur, du contenu seulement.
        $this->assertSame(0, $xpath->query('//*[@data-client-dashboard]//nav')->length);
        $this->assertSame(4, $xpath->query('//*[@data-client-dashboard]//a[contains(@class,"pk-board-tile")]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-client-dashboard]//style')->length);

        // Le menu est le panneau latéral du gabarit : rétractable, cinq groupes, la page en cours seule active.
        $this->assertSame(1, $xpath->query('//aside[@id="pkSpaceMenu"][@data-space-menu]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-space-menu-open][@aria-expanded="false"]')->length);
        $this->assertSame(1, $xpath->query('//aside[@data-space-menu]//button[@data-space-menu-close]')->length);
        $titles = array_map(fn ($node) => trim($node->textContent),
            iterator_to_array($xpath->query('//aside[@data-space-menu]//p[contains(@class,"pk-space-menu__title")]')));
        $this->assertSame(['Mon espace', 'Alertes et notifications', 'Emploi', 'Mon compte', 'Aide'], $titles);
        $current = $xpath->query('//aside[@data-space-menu]//a[@aria-current="page"]');
        $this->assertSame(1, $current->length);
        $this->assertSame(route('client.dashboard'), $current->item(0)->getAttribute('href'));

        // Toutes les pages de l'espace y sont, chacune une seule fois.
        $urls = array_map(fn ($attr) => $attr->value, iterator_to_array($xpath->query('//aside[@data-space-menu]//a/@href')));
        $this->assertSame($urls, array_values(array_unique($urls)));
        foreach ([route('home'), route('service-orders.index'), route('messages.index'), route('saved-ads.index'),
            route('saved-searches.index'), route('notifications.index'),
            route('employment.territory', ['territoire' => 'mayotte']), route('service-provider.start'),
            route('profile.show'), route('verification.index'), route('billing.index'), route('settings.index'),
            route('guides.index'), route('legal.platform-rules').'#classement', route('contact.index')] as $url) {
            $this->assertContains($url, $urls);
        }

        // Les anciennes rubriques (?rubrique=…) ne changent plus la page.
        $this->get(route('client.dashboard', ['rubrique' => 'compte']))->assertOk()->assertSee('pkBoardRequestsTitle', false);
    }

    public function test_every_counter_is_measured_on_the_account(): void
    {
        $member = $this->member();
        $author = User::factory()->create();
        Ad::create(['user_id' => $member->id, 'title' => 'Réparer une fuite', 'description' => 'Sous l’évier.',
            'category' => 'Plombier', 'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public',
            'location' => 'Mamoudzou', 'expires_at' => now()->addDays(30)]);
        $offer = Ad::create(['user_id' => $author->id, 'title' => 'Plomberie', 'description' => 'Dépannage.',
            'category' => 'Plombier', 'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public', 'location' => 'Mamoudzou']);
        $member->savedAds()->attach($offer->id);
        $member->savedSearches()->create(['name' => 'Nouvelles offres à Mayotte', 'service_type' => 'offres',
            'is_active' => true, 'territory_code' => '976', 'filters' => []]);
        $member->savedSearches()->create(['name' => 'Alerte en pause', 'service_type' => 'offres', 'is_active' => false, 'filters' => []]);

        $alertsUrl = route('client.dashboard');
        $this->actingAs($member)->get($alertsUrl)->assertOk()
            ->assertViewHas('tiles', fn (array $tiles) => array_column($tiles, 'value', 'label') === [
                'Demandes en cours' => 1, 'Messages non lus' => 0, 'Favoris' => 1, 'Alertes actives' => 1,
            ])
            ->assertViewHas('requestsTotal', 1)->assertViewHas('activeAlertCount', 1)
            ->assertSee('Réparer une fuite')->assertSee('En attente de propositions')
            ->assertSee('Nouvelles offres à Mayotte')->assertSee('Alerte en pause')->assertSee('En pause');

        // Le nombre total reste exact quand seules trois alertes sont affichées.
        foreach (range(1, 4) as $i) {
            $member->savedSearches()->create(['name' => 'Recherche '.$i, 'is_active' => true, 'service_type' => 'offres', 'filters' => []]);
        }
        $this->get($alertsUrl)->assertViewHas('activeAlertCount', 5)
            ->assertViewHas('savedSearches', fn ($searches) => $searches->count() === 3);
        // Compte neuf : des zéros réels, et l'invitation à créer une alerte.
        $this->actingAs($this->member())->get(route('client.dashboard'))->assertOk()
            ->assertViewHas('tiles', fn (array $tiles) => array_sum(array_column($tiles, 'value')) === 0)
            ->assertSee('Aucune demande en cours.')->assertSee('Aucune alerte pour le moment')
            ->assertSee('Me prévenir des nouvelles offres à Mayotte');
    }

    public function test_an_alert_is_stopped_from_the_dashboard_in_one_step(): void
    {
        $member = $this->member();
        $alert = $member->savedSearches()->create(['name' => 'Plombier à Mamoudzou', 'service_type' => 'offres',
            'is_active' => true, 'filters' => []]);
        $foreign = User::factory()->create()->savedSearches()->create(['name' => 'Alerte du voisin',
            'service_type' => 'offres', 'is_active' => true, 'filters' => []]);

        $alertsUrl = route('client.dashboard');
        $this->actingAs($member)->get($alertsUrl)->assertOk()
            ->assertSee(route('saved-searches.destroy', $alert), false)
            ->assertSee('aria-label="Arrêter l’alerte Plombier à Mamoudzou"', false)
            ->assertSee('data-pk-confirm="Arrêter l’alerte', false)
            ->assertSee('data-push-notification-controls', false)
            ->assertSee('data-push-disable', false)
            ->assertDontSee('Alerte du voisin');

        $this->from($alertsUrl)->delete(route('saved-searches.destroy', $alert))
            ->assertRedirect($alertsUrl)->assertSessionHas('success', 'Alerte supprimée.');
        $this->assertNull($alert->fresh());
        $this->get($alertsUrl)->assertOk()->assertDontSee('Plombier à Mamoudzou');

        // L'alerte d'un autre membre ne s'arrête pas d'ici.
        $this->delete(route('saved-searches.destroy', $foreign))->assertForbidden();
        $this->assertNotNull($foreign->fresh());
    }

    public function test_the_home_keeps_its_side_blocks_on_wide_screens_only(): void
    {
        $css = file_get_contents(public_path('css/feed.css'));

        // En dessous de 1180 px, Emploi et Repères ne passent plus sous les offres : ils sont dans le tableau de bord.
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 1179\.98px\) \{\s*\.pk-feed--client \.pk-rail \{ display: none; \}/',
            $css
        );
        $this->actingAs($this->member())->get(route('feed'))->assertOk()
            ->assertSee('class="pk-rail"', false)
            ->assertSee(route('client.dashboard'), false);
    }
}
