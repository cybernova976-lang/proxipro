<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La page des utilisateurs de l'administration sépare les particuliers des
 * professionnels, avec le nombre d'inscrits de chaque liste.
 */
class AdminUsersListsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // L'administrateur est lui-même un compte particulier.
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Test']);
        User::factory()->create(['name' => 'Paul Particulier']);
        User::factory()->create(['name' => 'Fatima Bricoleuse', 'is_service_provider' => true]);
        User::factory()->create(['name' => 'Société Kawéni', 'account_type' => 'professionnel', 'user_type' => 'professionnel', 'business_type' => 'entreprise']);
        User::factory()->create(['name' => 'Ali Plombier', 'account_type' => 'professionnel', 'user_type' => 'professionnel', 'business_type' => 'auto_entrepreneur']);
        // Ancien compte : seul `user_type` indique le statut professionnel.
        User::factory()->create(['name' => 'Ancien Pro', 'user_type' => 'professionnel']);
    }

    public function test_particuliers_are_listed_first_with_both_counters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users'));

        $response->assertOk()
            ->assertSee('data-compteur="particuliers">3<', false)
            ->assertSee('data-compteur="professionnels">3<', false)
            ->assertSee('data-liste="particuliers" aria-current="page"', false)
            ->assertSee('Paul Particulier')->assertSee('Fatima Bricoleuse')->assertSee('Particulier prestataire')
            ->assertDontSee('Société Kawéni')->assertDontSee('Ali Plombier')->assertDontSee('Ancien Pro');
    }

    public function test_professionnels_have_their_own_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users', ['liste' => 'professionnels']));

        $response->assertOk()
            ->assertSee('data-liste="professionnels" aria-current="page"', false)
            ->assertSee('Société Kawéni')->assertSee('Ali Plombier')->assertSee('Ancien Pro')
            ->assertSee('Entreprise')->assertSee('Auto-entrepreneur')
            ->assertDontSee('Paul Particulier')->assertDontSee('Fatima Bricoleuse');
    }

    /** Aucun compte n'est compté deux fois ni oublié. */
    public function test_the_two_counters_add_up_to_every_registered_account(): void
    {
        $this->assertSame(3, User::particuliers()->count());
        $this->assertSame(3, User::professionnels()->count());
        $this->assertSame(User::count(), User::particuliers()->count() + User::professionnels()->count());

        foreach (User::all() as $user) {
            $this->assertSame(
                $user->isRegisteredAsProfessional(),
                User::professionnels()->whereKey($user->id)->exists(),
                'Liste incohérente pour '.$user->name
            );
        }
    }

    public function test_a_search_keeps_the_registered_counters_and_shows_the_matches(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users', ['search' => 'Plombier']));

        $response->assertOk()
            // Les compteurs restent le nombre d'inscrits, pas le résultat de la recherche.
            ->assertSee('data-compteur="particuliers">3<', false)
            ->assertSee('data-compteur="professionnels">3<', false)
            ->assertSee('Aucun ne correspond aux filtres')
            ->assertSee('1 correspond aux filtres')
            ->assertSee('Aucun particulier trouvé')
            // Le passage à l'autre liste conserve la recherche.
            ->assertSee('search=Plombier&amp;liste=professionnels', false);
    }

    public function test_pagination_stays_in_the_list_and_keeps_the_filters(): void
    {
        User::factory()->count(25)->create(['account_type' => 'professionnel', 'user_type' => 'professionnel', 'is_verified' => true]);

        $response = $this->actingAs($this->admin)->get(route('admin.users', ['liste' => 'professionnels', 'status' => 'verified']));

        $response->assertOk()->assertSee('data-compteur="professionnels">28<', false);
        $this->assertMatchesRegularExpression(
            '/href="[^"]*status=verified&amp;liste=professionnels&amp;page=2"/',
            $response->getContent()
        );
    }

    public function test_old_type_links_and_the_admin_search_still_work(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users', ['user_type' => 'professionnel']))
            ->assertOk()->assertSee('Ali Plombier')->assertDontSee('Paul Particulier');

        // La recherche de la page des administrateurs parcourt les deux listes.
        $this->get(route('admin.users', ['liste' => 'tous', 'role' => 'user']))
            ->assertOk()->assertSee('Ali Plombier')->assertSee('Paul Particulier');

        // Cette page est réservée à l'administrateur principal.
        config(['admin.principal_admin.email' => $this->admin->email]);
        $this->get(route('admin.admins'))->assertOk()->assertSee('&role=user&liste=tous', false);
    }
}
