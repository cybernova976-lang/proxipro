<?php

namespace Tests\Feature\SavedSearch;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * « Créer une alerte » doit créer une alerte.
 *
 * Avant : le lien de l'accueil ouvrait « Mes alertes », dont les deux boutons
 * renvoyaient à l'accueil. Un membre dont le territoire avait déjà des offres
 * ne pouvait créer aucune alerte.
 */
class CreateAlertEntryPointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_home_to_do_line_creates_the_alert_even_when_the_territory_has_offers(): void
    {
        $reader = $this->mayotteReader();
        $this->offer($this->mayotteReader());

        $html = $this->actingAs($reader)->get(route('feed'))->assertOk()
            ->assertDontSee('Me prévenir</button>', false)
            ->assertSee('Me prévenir des nouvelles offres à Mayotte')
            ->getContent();

        // Plus de lien vers une page qui ne crée rien : un vrai formulaire.
        $this->assertStringNotContainsString('Créer une alerte pour les nouvelles offres', $html);
        [$action, $fields] = $this->alertForm($html, 'pk-landmarks__form');
        $this->assertSame(route('saved-searches.store'), $action);
        $this->assertSame(['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte'],
            array_diff_key($fields, ['_token' => true]));

        $after = $this->from(route('feed'))->followingRedirects()->post($action, $fields)->assertOk()
            ->assertSee('Alerte enregistrée : vous recevrez une notification dès qu’une offre sera publiée à Mayotte.')
            ->getContent();

        $this->assertSame('976', SavedSearch::where('user_id', $reader->id)->sole()->territory_code);
        // La ligne « À faire » disparaît sans réintroduire le raccourci retiré de Repères.
        $this->assertStringNotContainsString('pk-landmarks__form', $after);
        $this->assertStringNotContainsString('href="'.route('saved-searches.index').'"', $after);
        $this->assertStringContainsString('href="'.route('ads.myads').'"', $after);
    }

    public function test_my_alerts_page_creates_the_alert_instead_of_sending_back_home(): void
    {
        $reader = $this->mayotteReader();

        $html = $this->actingAs($reader)->get(route('saved-searches.index'))->assertOk()
            ->assertSee('Aucune alerte enregistrée')
            ->getContent();

        // Dans la page elle-même, plus aucun bouton qui renvoie à l'accueil.
        $page = substr($html, strpos($html, 'class="saved-searches-page"'));
        $page = substr($page, 0, strpos($page, '</main>'));
        $this->assertStringNotContainsString('Nouvelle alerte', $page);
        $this->assertStringNotContainsString('href="'.route('feed').'"', $page);

        [$action, $fields] = $this->alertForm($html, 'saved-search-create');
        $this->assertSame(route('saved-searches.store'), $action);

        $after = $this->from(route('saved-searches.index'))->followingRedirects()->post($action, $fields)->assertOk()
            ->assertSee('Alerte enregistrée : vous recevrez une notification dès qu’une offre sera publiée à Mayotte.')
            ->assertSee('Offres à Mayotte')
            ->getContent();

        // L'alerte existe : on ne propose plus de la créer une seconde fois.
        $this->assertStringNotContainsString('saved-search-create', $after);
        $this->assertSame(1, SavedSearch::count());
    }

    public function test_my_alerts_page_offers_the_territory_alert_again_once_it_is_deleted(): void
    {
        $reader = $this->mayotteReader();
        $this->actingAs($reader)->post(route('saved-searches.store'), ['type' => 'offres', 'territory' => '976', 'country' => 'Mayotte']);
        // Une autre alerte reste : la liste n'est pas vide, le bouton passe dans l'en-tête.
        $this->actingAs($reader)->post(route('saved-searches.store'), ['type' => 'demandes', 'territory' => '976', 'country' => 'Mayotte']);

        $territoryAlert = SavedSearch::where('service_type', 'offres')->sole();
        $this->actingAs($reader)->from(route('saved-searches.index'))->followingRedirects()
            ->delete(route('saved-searches.destroy', $territoryAlert))->assertOk()
            ->assertSee('Alerte supprimée.')
            ->assertSee('saved-search-create', false);
    }

    public function test_without_a_known_place_the_page_asks_for_the_commune(): void
    {
        $member = User::factory()->create(['city' => null, 'country' => null, 'postal_code' => null,
            'latitude' => null, 'longitude' => null]);

        $this->actingAs($member)->get(route('saved-searches.index'))->assertOk()
            ->assertSee('Indiquer ma commune')
            ->assertDontSee('saved-search-create', false);
    }

    /** @return array{0: string, 1: array<string, string>} adresse et champs du formulaire */
    private function alertForm(string $html, string $class): array
    {
        $this->assertSame(1, preg_match('/<form method="POST" action="([^"]+)" class="'.$class.'">(.*?)<\/form>/s', $html, $form));
        preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]*)"/', $form[2], $inputs, PREG_SET_ORDER);

        $fields = [];
        foreach ($inputs as [, $name, $value]) {
            $fields[$name] = html_entity_decode($value, ENT_QUOTES);
        }

        return [html_entity_decode($form[1], ENT_QUOTES), $fields];
    }

    private function mayotteReader(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600',
            'latitude' => -12.7806, 'longitude' => 45.2279, 'geo_radius' => 5], $attributes));
    }

    private function offer(User $author): Ad
    {
        return Ad::create(['user_id' => $author->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
    }
}
