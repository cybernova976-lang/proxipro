<?php

namespace Tests\Feature\Account;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tableau de bord du particulier : les pages de l'espace regroupées par nature,
 * des compteurs réellement mesurés, et les alertes que l'on arrête d'un clic.
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
        $this->actingAs($member)->get(route('client.dashboard'))->assertOk()
            ->assertSee('Tableau de bord')
            ->assertViewHas('section', 'activite')
            ->assertSee(route('home'), false)->assertSee(route('messages.index'), false)
            ->assertSee(route('saved-ads.index'), false)->assertSee(route('service-orders.index'), false)
            ->assertDontSee('pkBoardAlertsTitle')->assertDontSee('data-client-space', false)
            ->assertDontSee('id="sidebarNav"', false)->assertSee('class="pk-tabbar"', false);
        $this->get(route('client.dashboard', ['rubrique' => 'compte']))->assertOk()
            ->assertSee(route('employment.territory', ['territoire' => 'mayotte']), false)
            ->assertSee('Proposer mes services')->assertSee('Comment les offres sont classées')
            ->assertSee(route('billing.index'), false)->assertSee(route('profile.show'), false);
        $this->get(route('client.dashboard', ['rubrique' => 'alertes']))->assertOk()
            ->assertSee(route('saved-searches.index'), false)->assertSee(route('notifications.index'), false)
            ->assertSee(route('settings.index').'#notifications', false);
        $this->get(route('client.dashboard', ['rubrique' => 'inconnue']))->assertOk()->assertViewHas('section', 'activite');

        foreach (['activite', 'alertes', 'compte'] as $section) {
            $url = route('client.dashboard', $section === 'activite' ? [] : ['rubrique' => $section]);
            $response = $this->get($url)->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//*[@data-client-dashboard]//a/@href');
            $urls = array_map(fn ($attr) => $attr->value, iterator_to_array($links));
            $this->assertSame($urls, array_values(array_unique($urls)));
            $current = $xpath->query('//*[@data-client-dashboard]//nav//a[@aria-current="page"]');
            $this->assertCount(1, $current);
            $this->assertSame($url, $current->item(0)->getAttribute('href'));
        }
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

        $this->actingAs($member)->get(route('client.dashboard'))->assertOk()
            ->assertViewHas('requestsTotal', 1)->assertViewHas('unreadMessages', 0)->assertViewHas('favoritesCount', 1)
            ->assertSee('Réparer une fuite')->assertSee('En attente de propositions');
        $alertsUrl = route('client.dashboard', ['rubrique' => 'alertes']);
        $this->get($alertsUrl)->assertOk()->assertViewHas('activeAlertCount', 1)
            ->assertSee('Nouvelles offres à Mayotte')->assertSee('Alerte en pause')->assertSee('En pause');

        // Le nombre total reste exact quand seules trois alertes sont affichées.
        foreach (range(1, 4) as $i) {
            $member->savedSearches()->create(['name' => 'Recherche '.$i, 'is_active' => true, 'service_type' => 'offres', 'filters' => []]);
        }
        $this->get($alertsUrl)->assertViewHas('activeAlertCount', 5)
            ->assertViewHas('savedSearches', fn ($searches) => $searches->count() === 3);
        $this->actingAs($this->member())->get(route('client.dashboard'))->assertOk()
            ->assertViewHas('requestsTotal', 0)->assertViewHas('favoritesCount', 0)
            ->assertSee('Aucune demande en cours.');
        $this->get($alertsUrl)->assertOk()->assertSee('Aucune alerte pour le moment')
            ->assertSee('Me prévenir des nouvelles offres à Mayotte');
    }

    public function test_an_alert_is_stopped_from_the_dashboard_in_one_step(): void
    {
        $member = $this->member();
        $alert = $member->savedSearches()->create(['name' => 'Plombier à Mamoudzou', 'service_type' => 'offres',
            'is_active' => true, 'filters' => []]);
        $foreign = User::factory()->create()->savedSearches()->create(['name' => 'Alerte du voisin',
            'service_type' => 'offres', 'is_active' => true, 'filters' => []]);

        $alertsUrl = route('client.dashboard', ['rubrique' => 'alertes']);
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
