<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deux demandes du fondateur du 24/11/2026 :
 *  - dans les formulaires d'inscription et de publication, la localisation commence par
 *    « France », puis « Mayotte », puis « La Réunion » ;
 *  - le bloc « Repères » de l'accueil fait connaître l'Espace Pro aux membres qui ne sont
 *    pas prestataires (devis et factures gratuits, sans abonnement).
 */
class LocationOrderAndProPromoTest extends TestCase
{
    use RefreshDatabase;

    private const HEAD = ['France', 'Mayotte', 'La Réunion', 'Guadeloupe', 'Martinique', 'Guyane'];

    /** Valeurs des options d'un menu déroulant, dans l'ordre, sans l'option vide. */
    private function optionValues(string $html, string $selectId): array
    {
        $this->assertSame(1, preg_match('#<select[^>]*id="'.$selectId.'"[^>]*>(.*?)</select>#s', $html, $select), $selectId);
        preg_match_all('#<option value="([^"]*)"#u', $select[1], $values);

        return array_values(array_filter($values[1], fn ($value) => $value !== ''));
    }

    public function test_france_mayotte_and_la_reunion_open_every_location_list(): void
    {
        $this->assertSame(self::HEAD, array_slice(array_keys(config('locations.countries')), 0, 6));

        // Inscription.
        $this->assertSame(self::HEAD, array_slice($this->optionValues($this->get(route('register'))->assertOk()->getContent(), 'country'), 0, 6));

        // Publication d'une demande (formulaire complet).
        $member = User::factory()->create(['country' => null, 'city' => null, 'postal_code' => null]);
        $demand = $this->actingAs($member)->get(route('demand.create'))->assertOk()->getContent();
        $this->assertSame(self::HEAD, array_slice($this->optionValues($demand, 'demandCountry'), 0, 6));

        // Publication d'une offre.
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $offer = $this->actingAs($provider)->get(route('ads.create'))->assertOk()->getContent();
        $this->assertSame(self::HEAD, array_slice($this->optionValues($offer, 'country'), 0, 6));

        // Aucun pays n'a disparu en réordonnant.
        $this->assertCount(55, config('locations.countries'));
    }

    public function test_landmarks_promote_the_pro_space_to_members_who_are_not_providers(): void
    {
        $member = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false,
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'postal_code' => '97600']);

        $this->actingAs($member)->get(route('feed'))->assertOk()
            ->assertSee('data-pk-pro-promo', false)
            ->assertSee('href="'.route('quote-tool.landing').'"', false)
            // Décision du 24/11/2026 : 2 devis et 2 factures offerts à tout membre, sans
            // justificatif ; plus besoin d'activer un profil prestataire.
            ->assertSee('Deux devis et deux factures offerts')
            ->assertSee('Sans justificatif ni abonnement, que vous soyez particulier ou professionnel.')
            ->assertDontSee('Il suffit d’activer votre profil prestataire.')
            ->assertSee('Découvrir l’Espace Pro');

        // Le texte en lettres suit le quota réellement appliqué.
        $this->assertSame(['quote' => 2, 'invoice' => 2], \App\Services\CommercialDocumentAllowance::FREE);

        // La page vers laquelle elle mène dit la même chose, et mène à ses devis.
        $this->get(route('quote-tool.landing'))->assertOk()
            ->assertSee('sans crédits à acheter')->assertSee('2 devis et 2 factures offerts, sans justificatif')
            ->assertSee(route('pro.quotes'), false);

        // Un prestataire a déjà son Espace Pro : pas de promotion, même côté « Je cherche un service ».
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $this->actingAs($provider)->get(route('feed', ['mode' => 'client']))->assertOk()
            ->assertDontSee('data-pk-pro-promo', false);

        // Aucun style dans la vue : il vit dans public/css/feed.css.
        $this->assertStringContainsString('.pk-landmarks__pro {', file_get_contents(public_path('css/feed.css')));
        $this->assertStringNotContainsString('<style', file_get_contents(resource_path('views/feed/partials/landmarks.blade.php')));
    }
}
