<?php

namespace Tests\Feature\Admin;

use App\Models\ProSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche d'un utilisateur dans l'administration : nomination d'un
 * administrateur, accès Premium, protection du compte principal, et
 * pagination lisible au pied des listes.
 */
class AdminUserProfileActionsTest extends TestCase
{
    use RefreshDatabase;

    private function principalAdmin(): User
    {
        $principal = User::factory()->create(['role' => 'admin']);
        config(['admin.principal_admin.email' => $principal->email]);

        return $principal;
    }

    private function provider(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['is_service_provider' => true])->save();

        return $user;
    }

    public function test_principal_admin_sees_admin_and_premium_actions_on_a_user_profile(): void
    {
        $principal = $this->principalAdmin();
        $provider = $this->provider();

        $response = $this->actingAs($principal)->get(route('admin.users.show', $provider->id));

        $response->assertOk();
        $response->assertSee(route('admin.admins.promote', $provider->id), false);
        $response->assertSee('Nommer administrateur');
        $response->assertSee(route('admin.subscriptions.provider.grant'), false);
        $response->assertSee('Accorder le Premium sans paiement');
    }

    public function test_principal_admin_can_promote_a_user_and_the_admins_page_still_renders(): void
    {
        $principal = $this->principalAdmin();
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($principal)
            ->post(route('admin.admins.promote', $user->id), ['privileges' => ['manage_users', 'view_stats']])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('admin', $user->role);
        $this->assertSame(['manage_users', 'view_stats'], $user->adminPrivilegeList());
        $this->assertTrue($user->hasAdminPrivilege('view_stats'));
        $this->assertFalse($user->hasAdminPrivilege('manage_settings'));

        $this->actingAs($principal)
            ->get(route('admin.admins'))
            ->assertOk()
            ->assertSee(config('admin.privileges.manage_users.label'));

        $profile = $this->actingAs($principal)->get(route('admin.users.show', $user->id));
        $profile->assertOk();
        $profile->assertSee(route('admin.admins.privileges', $user->id), false);
        $profile->assertSee(route('admin.admins.revoke', $user->id), false);
    }

    public function test_privileges_saved_in_the_old_double_encoded_format_are_still_read(): void
    {
        $principal = $this->principalAdmin();
        $legacyAdmin = User::factory()->create(['role' => 'admin']);
        $legacyAdmin->forceFill(['admin_privileges' => json_encode(['manage_ads'])])->save();

        $this->assertSame(['manage_ads'], $legacyAdmin->fresh()->adminPrivilegeList());

        $this->actingAs($principal)
            ->get(route('admin.admins'))
            ->assertOk()
            ->assertSee(config('admin.privileges.manage_ads.label'));
    }

    /** Constaté en production : un simple utilisateur gardait un ancien privilège, précoché sur sa fiche. */
    public function test_stale_privileges_of_a_plain_user_are_not_prechecked_for_a_new_promotion(): void
    {
        $principal = $this->principalAdmin();
        $formerAdmin = User::factory()->create(['role' => 'user']);
        $formerAdmin->forceFill(['admin_privileges' => ['grant_premium']])->save();

        $html = $this->actingAs($principal)->get(route('admin.users.show', $formerAdmin->id))->assertOk()->getContent();

        $this->assertStringContainsString('id="userPrivilege_grant_premium"', $html);
        $this->assertDoesNotMatchRegularExpression('/id="userPrivilege_[a-z_]+"\s+checked/', $html);
    }

    public function test_secondary_admin_cannot_name_admins_nor_touch_the_principal_account(): void
    {
        $principal = $this->principalAdmin();
        $secondary = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $profile = $this->actingAs($secondary)->get(route('admin.users.show', $user->id));
        $profile->assertOk();
        $profile->assertDontSee(route('admin.admins.promote', $user->id), false);

        $this->actingAs($secondary)->put(route('admin.users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'admin',
            'is_active' => '1',
        ]);
        $this->assertNotSame('admin', $user->fresh()->role);

        $this->actingAs($secondary)->put(route('admin.users.update', $principal->id), [
            'name' => 'Compte détourné',
            'email' => $principal->email,
            'role' => 'user',
            'is_active' => '0',
        ]);
        $principal->refresh();
        $this->assertSame('admin', $principal->role);
        $this->assertNotSame('Compte détourné', $principal->name);

        $this->actingAs($secondary)->delete(route('admin.users.delete', $principal->id));
        $this->assertNotNull(User::find($principal->id));
    }

    public function test_an_admin_cannot_delete_their_own_account_from_the_admin(): void
    {
        $principal = $this->principalAdmin();
        $secondary = User::factory()->create(['role' => 'admin']);

        $this->actingAs($secondary)->delete(route('admin.users.delete', $secondary->id));

        $this->assertNotNull(User::find($secondary->id));
    }

    public function test_premium_can_be_granted_then_cancelled_from_the_user_profile(): void
    {
        $principal = $this->principalAdmin();
        $provider = $this->provider();

        $this->actingAs($principal)
            ->from(route('admin.users.show', $provider->id))
            ->post(route('admin.subscriptions.provider.grant'), [
                'email' => $provider->email,
                'plan' => 'monthly',
                'duration' => 30,
            ])
            ->assertRedirect(route('admin.users.show', $provider->id));

        $this->assertTrue($provider->fresh()->isPremium());
        $subscription = ProSubscription::where('user_id', $provider->id)->firstOrFail();

        $profile = $this->actingAs($principal)->get(route('admin.users.show', $provider->id));
        $profile->assertOk();
        $profile->assertSee('Premium actif');
        $profile->assertSee(route('admin.subscriptions.provider.cancel', $subscription), false);
        $profile->assertDontSee('Accorder le Premium sans paiement');
    }

    public function test_premium_grant_is_explained_rather_than_offered_for_a_non_provider(): void
    {
        $principal = $this->principalAdmin();
        $client = User::factory()->create();

        $profile = $this->actingAs($principal)->get(route('admin.users.show', $client->id));

        $profile->assertOk();
        $profile->assertDontSee('Accorder le Premium sans paiement');
        $profile->assertSee('profil prestataire');
    }

    public function test_admin_lists_use_a_readable_french_pagination(): void
    {
        $principal = $this->principalAdmin();
        User::factory()->count(30)->create();

        $response = $this->actingAs($principal)->get(route('admin.users'));

        $response->assertOk();
        $response->assertSee('class="pagination', false);
        $response->assertSee('page-item', false);
        $response->assertSee('Affichage de');
        $response->assertDontSee('Showing');
        $response->assertDontSee('relative inline-flex', false);
    }

    public function test_admin_menu_can_be_scrolled_on_a_small_screen(): void
    {
        $principal = $this->principalAdmin();

        $response = $this->actingAs($principal)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('id="adminSidebar"', false);
        $this->assertMatchesRegularExpression(
            '/\.sidebar\s*\{[^}]*overflow-y:\s*auto/',
            $response->getContent()
        );
    }
}
