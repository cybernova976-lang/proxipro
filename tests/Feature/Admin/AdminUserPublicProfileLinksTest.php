<?php

namespace Tests\Feature\Admin;

use App\Models\ProSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dans l'administration, le nom d'un membre ouvre son profil public dans un
 * nouvel onglet. L'administrateur voit aussi un profil privé, et ses visites
 * ne gonflent pas le compteur de vues du membre.
 */
class AdminUserPublicProfileLinksTest extends TestCase
{
    use RefreshDatabase;

    private function publicLink(User $user): string
    {
        return 'href="'.route('profile.public', $user->id).'" target="_blank" rel="noopener"';
    }

    public function test_member_names_open_the_public_profile_from_the_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Membre Lien']);
        $member->forceFill(['is_service_provider' => true])->save();
        ProSubscription::create(['user_id' => $member->id, 'plan' => 'monthly', 'amount' => 0, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addMonth(), 'auto_renew' => false]);

        // Liste des membres et fiche du membre.
        $this->actingAs($admin)->get(route('admin.users'))->assertOk()
            ->assertSee($this->publicLink($member), false);
        $this->actingAs($admin)->get(route('admin.users.show', $member->id))->assertOk()
            ->assertSee($this->publicLink($member), false)
            ->assertSee('Voir le profil public de Membre Lien (nouvel onglet)');

        // Là où le nom menait à la fiche admin, la fiche reste accessible par une icône.
        $this->actingAs($admin)->get(route('admin.subscriptions'))->assertOk()
            ->assertSee($this->publicLink($member), false)
            ->assertSee('href="'.route('admin.users.show', $member->id).'" class="ms-1 text-secondary small"', false)
            ->assertSee('Fiche admin de Membre Lien');
    }

    public function test_a_deleted_account_is_shown_without_a_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Compte Parti']);
        $member->delete();

        $html = view('components.admin.user-link', ['user' => $member->fresh() ?? User::withTrashed()->find($member->id)])->render();
        $this->assertStringContainsString('Compte Parti', $html);
        $this->assertStringNotContainsString('href=', $html);

        $html = $this->blade('<x-admin.user-link :user="null" fallback="Anonyme" />');
        $html->assertSee('Anonyme')->assertDontSee('href=', false);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_the_admin_sees_a_private_profile_and_is_not_counted_as_a_visitor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $visitor = User::factory()->create();
        $member = User::factory()->create(['name' => 'Profil Discret', 'profile_public' => false]);

        $this->actingAs($admin)->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')
            ->get(route('profile.public', $member->id))->assertOk()->assertSee('Profil Discret');
        $this->assertDatabaseCount('profile_views', 0);
        $this->assertSame(0, (int) $member->fresh()->profile_views);

        // Un autre membre ne voit toujours pas un profil privé.
        $this->actingAs($visitor)->get(route('profile.public', $member->id))->assertNotFound();

        // Un profil public visité par un membre est bien compté, une fois.
        $member->forceFill(['profile_public' => true])->save();
        $this->actingAs($visitor)->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')
            ->get(route('profile.public', $member->id))->assertOk();
        $this->assertDatabaseCount('profile_views', 1);
    }
}
