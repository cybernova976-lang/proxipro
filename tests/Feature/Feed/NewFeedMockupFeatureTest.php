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
            ->assertSee(route('ads.create', ['type' => 'offre']), false)
            ->assertSee(asset('css/feed.css'), false)
            ->assertSee(asset('js/feed.js'), false);
    }

    public function test_client_discovery_is_followed_by_a_compact_action_with_its_real_proposal_count(): void
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
            ->assertSee('pk-resume', false)
            ->assertSee('Votre prochaine action')
            ->assertSee('Réparer la porte du garage')
            ->assertSee('1 proposition')
            ->assertSee('à examiner')
            ->assertSee('Comparer')
            ->assertSee(route('proposals.compare', $requestAd), false);

        $html = $response->getContent();
        preg_match('/<section\b[^>]*pk-resume[^>]*>(.*?)<\/section>/s', $html, $stateCard);

        $this->assertNotEmpty($stateCard, 'La proposition à examiner doit posséder un rappel compact.');
        $this->assertStringContainsString('pk-resume__copy', $stateCard[1]);
        $this->assertStringContainsString('pk-resume__label', $stateCard[1]);
        $this->assertGreaterThan(strpos($html, 'id="pkIntentForm"'), strpos($html, 'class="pk-resume"'));

        $css = file_get_contents(public_path('css/feed.css'));
        preg_match('/\.pk-resume\s*\{([^}]*)\}/s', $css, $activeRequestRule);
        $this->assertNotEmpty($activeRequestRule, 'Le rappel de prochaine action ne possède aucune règle CSS.');
        $this->assertMatchesRegularExpression(
            '/(?:background|border|box-shadow)\s*:/',
            $activeRequestRule[1],
            'Le rappel doit se distinguer visuellement des autres blocs du feed.'
        );
    }

    public function test_an_unanswered_request_stays_accessible_without_an_unsupported_recovery_warning(): void
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
            ->assertDontSee('Toujours aucune réponse')
            ->assertDontSee('Améliorer ma demande')
            ->assertSee('1 demande ou mission en cours')
            ->assertSee('Voir mon suivi')
            ->assertSee(route('home'), false)
            ->assertDontSee('data-activity-key="request-'.$requestAd->id.'"', false);
        $this->get(route('home'))->assertOk()->assertSee('En attente de propositions')
            ->assertSee('Réparer une fuite restée sans réponse')->assertSee(route('ads.edit', $requestAd), false);
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
