<?php

namespace Tests\Feature\Demand;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moins d'obstacles dans le parcours de demande : le territoire est déjà choisi,
 * la liste des pays s'affiche correctement partout, et un visiteur sait qu'il
 * va créer un compte gratuit, sans perdre sa demande.
 */
class DemandFormFrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_is_preselected_from_the_member_territory_and_has_no_flag_emoji(): void
    {
        $member = User::factory()->create(['country' => null, 'city' => null, 'postal_code' => '97600']);

        $html = $this->actingAs($member)->get(route('demand.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="Mayotte"\s+selected[^>]*>\s*Mayotte\s*<\/option>/u', $html);
        // Indicateurs régionaux Unicode : Windows les affiche en lettres (« YT Mayotte »).
        $this->assertSame(0, preg_match('/[\x{1F1E6}-\x{1F1FF}]/u', $html));
    }

    public function test_member_country_and_city_win_over_the_territory(): void
    {
        $member = User::factory()->create(['country' => 'La Réunion', 'city' => 'Saint-Denis', 'postal_code' => '97600']);

        $this->actingAs($member)->get(route('demand.create'))->assertOk()
            ->assertSee('<option value="La Réunion" selected', false)
            ->assertSee('const oldCity = "Saint-Denis"', false);
    }

    public function test_guest_last_step_offers_account_creation_and_keeps_login_one_click_away(): void
    {
        $this->get(route('demand.create'))->assertOk()
            ->assertSee('<span>Créer mon compte</span>', false)
            ->assertSee('Votre demande est conservée pendant la création de votre compte gratuit.')
            ->assertSee('href="'.route('login', ['continue' => 'demand']).'" onclick="saveDemandDraft()">Se connecter et publier</a>', false);

        // La création du compte mémorise la reprise de la demande, comme la connexion.
        $this->get(route('register', ['continue' => 'demand']))->assertOk();
        $this->assertTrue(session(\App\Support\DemandPublicationContinuation::SESSION_KEY));
    }
}
