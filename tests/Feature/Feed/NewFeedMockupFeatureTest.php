<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\ServiceProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NewFeedMockupFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_mockup_routes_redirect_to_the_unified_role_based_feed(): void
    {
        $this->assertFalse(Route::has('feed.mockup.preview'));

        $viewer = User::factory()->create([
            'user_type' => 'professionnel',
            'account_type' => 'professionnel',
            'is_service_provider' => true,
            'pro_service_categories' => ['Plombier'],
            'pro_onboarding_completed' => true,
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
        $ad->forceFill([
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(4),
        ])->save();

        $viewer->savedAds()->attach($ad->id);

        $this->withoutMiddleware()
            ->actingAs($viewer)
            ->get(route('feed.mockup'))
            ->assertRedirect(route('feed'));

        $this->assertSame('/nouveau-feed', route('feed.mockup', [], false));

        $this->withoutMiddleware()
            ->actingAs($viewer)
            ->get(route('feed.mockup.legacy'))
            ->assertRedirect(route('feed'));

        $this->assertFileExists(public_path('css/feed.css'));
        $this->assertFileExists(public_path('js/feed.js'));

        $feed = $this->withoutMiddleware()
            ->actingAs($viewer)
            ->get(route('feed'));

        $feed
            ->assertOk()
            ->assertViewIs('feed.index')
            ->assertSee('id="pkFeed"', false)
            ->assertSee('Demande test prioritaire')
            ->assertSee('data-pk-save="'.$ad->id.'"', false)
            ->assertSee('aria-pressed="true"', false)
            ->assertSee(route('ads.create', ['type' => 'service']), false)
            ->assertSee(asset('css/feed.css'), false)
            ->assertSee(asset('js/feed.js'), false);
    }

    public function test_client_view_starts_with_the_active_request_and_its_real_proposal_count(): void
    {
        $client = User::factory()->create([
            'user_type' => 'particulier',
            'account_type' => 'particulier',
            'is_service_provider' => false,
        ]);
        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'account_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        $requestAd = Ad::create([
            'title' => 'Réparer la porte du garage',
            'description' => 'La porte reste bloquée et doit être diagnostiquée.',
            'category' => 'Bricolage',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $client->id,
        ]);

        ServiceProposal::create([
            'ad_id' => $requestAd->id,
            'provider_id' => $provider->id,
            'amount' => 95,
            'message' => 'Je peux intervenir demain matin.',
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($client)
            ->get(route('feed'))
            ->assertOk()
            ->assertSee('pk-state--active-request', false)
            ->assertSee('pk-state__request-head', false)
            ->assertSee('pk-state__status', false)
            ->assertSee('Votre demande en cours')
            ->assertSee('Réparer la porte du garage')
            ->assertSee('1 prestataire')
            ->assertSee('vous a répondu')
            ->assertSee('Voir les 1 réponse')
            ->assertSee(route('proposals.compare', $requestAd), false);

        $html = $response->getContent();
        preg_match('/<section\b[^>]*pk-state--active-request[^>]*>(.*?)<\/section>/s', $html, $stateCard);

        $this->assertNotEmpty($stateCard, 'La demande en cours doit posseder son propre traitement visuel.');
        $this->assertStringContainsString('pk-state__request-head', $stateCard[1]);
        $this->assertStringContainsString('pk-state__status', $stateCard[1]);

        $css = file_get_contents(public_path('css/feed.css'));
        preg_match('/\.pk-state--active-request\s*\{([^}]*)\}/s', $css, $activeRequestRule);
        $this->assertNotEmpty($activeRequestRule, 'Le modificateur de la demande en cours ne possede aucune regle CSS.');
        $this->assertMatchesRegularExpression(
            '/(?:background|border|box-shadow)\s*:/',
            $activeRequestRule[1],
            'La demande en cours doit se distinguer visuellement des autres blocs du feed.'
        );
    }

    public function test_client_with_an_unanswered_request_gets_a_concrete_recovery_action(): void
    {
        $client = User::factory()->create([
            'user_type' => 'particulier',
            'account_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $requestAd = Ad::create([
            'title' => 'Réparer une fuite restée sans réponse',
            'description' => 'La demande est assez ancienne pour proposer une action corrective.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $client->id,
            'expires_at' => now()->addDays(30),
        ]);
        $requestAd->forceFill([
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ])->save();

        $this->withoutMiddleware()
            ->actingAs($client)
            ->get(route('feed'))
            ->assertOk()
            ->assertSee('Toujours aucune réponse')
            ->assertSee('Améliorer ma demande')
            ->assertSee(route('ads.edit', $requestAd), false)
            ->assertSee('Consulter les prestataires');
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
