<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModalLayeringFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_subscription_modals_are_hoisted_above_the_bootstrap_backdrop(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['plan' => 'FREE']);

        $usersPage = $this->actingAs($admin)->get(route('admin.users'));

        $usersPage->assertOk()
            ->assertSee('data-bs-target="#editModal'.$member->id.'"', false)
            ->assertSee('id="editModal'.$member->id.'"', false)
            ->assertSee("document.querySelectorAll('.main-content .modal')", false)
            ->assertSee('document.body.appendChild(modal)', false)
            ->assertSee('z-index: auto', false);

        $subscriptionsPage = $this->actingAs($admin)->get(route('admin.subscriptions'));

        $subscriptionsPage->assertOk()
            ->assertSee('data-bs-target="#grantModal'.$member->id.'"', false)
            ->assertSee('id="grantModal'.$member->id.'"', false)
            ->assertSee("document.querySelectorAll('.main-content .modal')", false)
            ->assertSee('document.body.appendChild(modal)', false)
            ->assertSee('z-index: 1060', false);
    }
}
