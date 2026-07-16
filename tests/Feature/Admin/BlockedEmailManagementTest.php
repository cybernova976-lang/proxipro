<?php

namespace Tests\Feature\Admin;

use App\Models\BlockedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedEmailManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_block_and_unblock_an_email_address(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.blocked-emails.store'), [
            'email' => '  Compte.Supprime@Example.com ',
            'reason' => 'Abus répétés constatés par la modération.',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $blockedEmail = BlockedEmail::firstOrFail();

        $this->assertSame('compte.supprime@example.com', $blockedEmail->email);
        $this->assertSame($admin->id, $blockedEmail->blocked_by);
        $this->assertSame('Abus répétés constatés par la modération.', $blockedEmail->reason);

        $this->actingAs($admin)
            ->get(route('admin.blocked-emails.index'))
            ->assertOk()
            ->assertSee('compte.supprime@example.com')
            ->assertSee('Abus répétés constatés par la modération.');

        $this->actingAs($admin)
            ->delete(route('admin.blocked-emails.destroy', $blockedEmail))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('blocked_emails', ['email' => 'compte.supprime@example.com']);
    }

    public function test_admin_can_block_an_email_from_the_deleted_accounts_history(): void
    {
        $admin = $this->admin();
        $deletedUser = User::factory()->create(['email' => 'ancien.compte@example.com']);
        $deletedUser->delete();

        $this->actingAs($admin)
            ->post(route('admin.blocked-emails.from-deleted-account', $deletedUser->id), [
                'reason' => 'Compte frauduleux supprimé par la modération.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('blocked_emails', [
            'email' => 'ancien.compte@example.com',
            'reason' => 'Compte frauduleux supprimé par la modération.',
            'blocked_by' => $admin->id,
            'source_user_id' => $deletedUser->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.deleted-accounts'))
            ->assertOk()
            ->assertSee('E-mail bloqué')
            ->assertSee('ancien.compte@example.com');
    }

    public function test_restoring_a_deleted_account_removes_its_email_block(): void
    {
        $admin = $this->admin();
        $deletedUser = User::factory()->create(['email' => 'compte.a.restaurer@example.com']);
        $deletedUser->delete();

        BlockedEmail::create([
            'email' => 'compte.a.restaurer@example.com',
            'reason' => 'Blocage temporaire.',
            'blocked_by' => $admin->id,
            'source_user_id' => $deletedUser->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.accounts.restore', $deletedUser->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(User::find($deletedUser->id));
        $this->assertDatabaseMissing('blocked_emails', ['email' => 'compte.a.restaurer@example.com']);
    }

    public function test_non_admin_cannot_manage_blocked_emails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.blocked-emails.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.blocked-emails.store'), [
                'email' => 'interdit@example.com',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('blocked_emails', 0);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }
}
