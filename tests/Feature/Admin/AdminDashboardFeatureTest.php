<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_cards_and_export_have_real_destinations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.users'), false);
        $response->assertSee(route('admin.ads'), false);
        $response->assertSee(route('admin.users', ['status' => 'verified']), false);
        $response->assertSee(route('admin.verifications'), false);
        $response->assertSee(route('admin.service-orders.index'), false);
        $response->assertSee(route('admin.export'), false);
    }

    public function test_admin_can_export_the_platform_data_as_csv(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrateur Export',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Type;ID;', $content);
        $this->assertStringContainsString('Administrateur Export', $content);
    }

    public function test_non_admin_cannot_export_platform_data(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.export'))
            ->assertForbidden();
    }
}
