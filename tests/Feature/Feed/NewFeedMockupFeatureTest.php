<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NewFeedMockupFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mockup_route_renders_role_based_feed_without_replacing_current_feed(): void
    {
        $this->assertFalse(Route::has('feed.mockup.preview'));

        $viewer = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        $requester = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $ad = Ad::create([
            'title' => 'Demande test prioritaire',
            'description' => 'Une demande réelle utilisée pour alimenter le nouveau feed.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'price' => 85,
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $requester->id,
            'is_urgent' => true,
            'urgent_until' => now()->addDay(),
        ]);

        $viewer->savedAds()->attach($ad->id);

        $mockup = $this->withoutMiddleware()
            ->actingAs($viewer)
            ->get(route('feed.mockup'));

        $this->assertFileExists(public_path('css/feed-mockup.css'));

        $mockup
            ->assertOk()
            ->assertViewIs('feed.mockup')
            ->assertSee('Nouvelle expérience')
            ->assertDontSee('Maquette fonctionnelle')
            ->assertSee('Vue client')
            ->assertSee('Vue prestataire')
            ->assertSee('Demande test prioritaire')
            ->assertSee('data-mode-panel="client"', false)
            ->assertSee('data-mode-panel="provider"', false)
            ->assertSee('id="mockOpportunitySearch"', false)
            ->assertSee('id="mockRequestDialog"', false)
            ->assertSee('data-save-ad', false)
            ->assertSee('data-ad-id="'.$ad->id.'"', false)
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('/toggle-save', false)
            ->assertSee(route('demand.create'), false)
            ->assertSee(asset('css/feed-mockup.css'), false);

        $this->assertSame('/nouveau-feed', route('feed.mockup', [], false));

        $this->withoutMiddleware()
            ->actingAs($viewer)
            ->get(route('feed.mockup.legacy'))
            ->assertRedirect(route('feed.mockup'));

        $currentFeed = $this->withoutMiddleware()
            ->actingAs($viewer)
            ->get(route('feed'));

        $currentFeed
            ->assertOk()
            ->assertViewIs('feed.index')
            ->assertSee('Découvrez la nouvelle expérience Prokejem')
            ->assertSee('Découvrir le nouveau feed')
            ->assertSee(route('feed.mockup'), false);
    }

    public function test_new_feed_favorite_action_persists_and_removes_the_saved_ad(): void
    {
        $viewer = User::factory()->create();
        $requester = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Demande à enregistrer',
            'description' => 'Cette annonce vérifie le fonctionnement réel du bouton favori.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $requester->id,
        ]);

        $this->actingAs($viewer)
            ->postJson(route('ads.toggle-save', $ad))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'saved' => true,
            ]);

        $this->assertDatabaseHas('saved_ads', [
            'user_id' => $viewer->id,
            'ad_id' => $ad->id,
        ]);

        $this->actingAs($viewer)
            ->postJson(route('ads.toggle-save', $ad))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'saved' => false,
            ]);

        $this->assertDatabaseMissing('saved_ads', [
            'user_id' => $viewer->id,
            'ad_id' => $ad->id,
        ]);
    }
}
