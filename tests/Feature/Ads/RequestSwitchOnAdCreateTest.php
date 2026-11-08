<?php

namespace Tests\Feature\Ads;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /ads/create : un particulier qui cherche un prestataire est aiguillé vers
 * le parcours de demande, par un lien en contour (pas un bouton plein).
 */
class RequestSwitchOnAdCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_and_professional_members_see_the_switch_once(): void
    {
        $members = [
            User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]),
            User::factory()->create(['user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true, 'profession' => 'Plombier']),
        ];

        foreach ($members as $member) {
            $response = $this->actingAs($member)->get(route('ads.create'));

            $response->assertOk();
            $html = $response->getContent();
            $this->assertSame(1, substr_count($html, 'Vous cherchez un prestataire ?'));
            $this->assertStringContainsString('class="pk-btn-outline pk-request-switch__link" href="'.route('demand.create').'"', $html);
            $this->assertStringContainsString(asset('css/demand-guided.css'), $html);
            $this->assertStringContainsString('aria-labelledby="pkRequestSwitchTitle"', $html);
        }
    }
}
