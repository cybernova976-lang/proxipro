<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\Review;
use App\Models\User;
use App\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceCoherenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_includes_free_providers_but_not_private_inactive_or_client_profiles(): void
    {
        $viewer = User::factory()->create(['user_type' => 'particulier']);
        $professional = $this->provider(['name' => 'Amina sans abonnement', 'is_service_provider' => false]);
        $individual = $this->provider(['name' => 'Benoît prestataire', 'user_type' => 'particulier', 'is_service_provider' => true]);
        $this->provider(['name' => 'Profil confidentiel', 'profile_public' => false]);
        $inactive = $this->provider(['name' => 'Profil désactivé']);
        $inactive->forceFill(['is_active' => false])->save();

        $response = $this->actingAs($viewer)->get(route('feed.professionals'));

        $response->assertOk()->assertViewIs('feed.professionals')
            ->assertDontSee('id="sidebarNav"', false)
            ->assertSee($professional->name)->assertSee($individual->name)
            ->assertDontSee('Profil confidentiel')->assertDontSee('Profil désactivé')
            ->assertSee('Pas encore d’avis vérifié')
            ->assertViewHas('professionals', fn ($profiles) => $profiles->total() === 2);
    }

    public function test_directory_matches_trades_and_active_offers_but_never_a_providers_own_request(): void
    {
        $viewer = User::factory()->create();
        $trade = $this->provider(['name' => 'Métier déclaré', 'profession' => 'Plombier']);
        $skill = $this->provider(['name' => 'Compétence active']);
        UserService::create(['user_id' => $skill->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'is_active' => true]);
        $offer = $this->provider(['name' => 'Offre active']);
        $request = $this->provider(['name' => 'Besoin personnel']);
        $this->ad($offer, 'offre');
        $this->ad($request, 'demande');

        $this->actingAs($viewer)->get(route('feed.professionals', ['subcategory' => 'Plombier']))
            ->assertOk()->assertViewHas('professionals', function ($profiles) use ($trade, $skill, $offer, $request) {
                $ids = $profiles->pluck('id')->all();

                return count($ids) === 3 && in_array($trade->id, $ids) && in_array($skill->id, $ids)
                    && in_array($offer->id, $ids) && ! in_array($request->id, $ids);
            });
    }

    public function test_directory_paginates_all_providers_and_keeps_category_filter(): void
    {
        $viewer = User::factory()->create();
        for ($i = 1; $i <= 13; $i++) {
            $this->provider(['name' => sprintf('Prestataire %02d', $i), 'profession' => 'Plombier']);
        }

        $response = $this->actingAs($viewer)->get(route('feed.professionals', ['category' => 'Bricolage & Travaux']));
        $response->assertOk()->assertSee('Page 1 sur 2')
            ->assertViewHas('professionals', fn ($profiles) => $profiles->total() === 13 && $profiles->count() === 12);
        $nextPage = $response->viewData('professionals')->nextPageUrl();
        $this->assertStringContainsString('category=', $nextPage);
        $this->get($nextPage)->assertOk()->assertSee('Prestataire 13')->assertSee('Page 2 sur 2');
    }

    public function test_unknown_category_does_not_silently_return_every_profile(): void
    {
        $viewer = User::factory()->create();
        $this->provider();
        $this->actingAs($viewer)->get(route('feed.professionals', ['category' => 'Inexistante']))
            ->assertOk()->assertViewHas('professionals', fn ($profiles) => $profiles->isEmpty());
    }

    public function test_unverified_review_is_not_turned_into_a_public_rating(): void
    {
        $viewer = User::factory()->create();
        $provider = $this->provider();
        Review::create(['reviewer_id' => $viewer->id, 'reviewed_user_id' => $provider->id, 'rating' => 5, 'comment' => 'Ancien avis sans prestation']);

        $this->actingAs($viewer)->get(route('feed.professionals'))->assertOk()
            ->assertSee('Pas encore d’avis vérifié')
            ->assertViewHas('professionals', fn ($profiles) => $profiles->first()->reviews_count === 0 && $profiles->first()->reviews_avg_rating === null);
        $this->get(route('profile.public', $provider))->assertOk()
            ->assertSee('Pas encore noté')->assertDontSee('0.0<small>/5</small>', false)
            ->assertDontSee('aria-label="Note de 0.0 sur 5"', false);
    }

    public function test_feed_provider_link_opens_the_directory_instead_of_offers(): void
    {
        $this->actingAs(User::factory()->create());
        $provider = $this->provider();
        $html = view('feed.partials.providers', ['homeProfessionalProfiles' => collect([$provider])])->render();
        $this->assertStringContainsString('href="'.route('feed.professionals').'"', $html);
        $this->assertStringNotContainsString(route('ads.index', ['type' => 'offres']), $html);
    }

    public function test_generic_form_defaults_to_a_request_even_for_a_professional(): void
    {
        $this->actingAs($this->provider());
        foreach ([[], ['type' => 'demande'], ['type' => 'inconnu']] as $query) {
            $this->get(route('ads.create', $query))->assertOk()
                ->assertSee('name="service_type" id="service_type" value="demande"', false);
        }
        foreach (['offre', 'service'] as $type) {
            $this->get(route('ads.create', ['type' => $type]))->assertOk()
                ->assertSee('name="service_type" id="service_type" value="offre"', false);
        }
    }

    public function test_header_request_action_uses_the_guided_journey_for_both_roles(): void
    {
        foreach (['particulier', 'professionnel'] as $role) {
            $this->actingAs(User::factory()->create(['user_type' => $role]));
            $response = $this->get(route('ads.create'))->assertOk()
                ->assertDontSee('id="onb-step-1"', false)
                ->assertSee('const installSurfaceAllowed = false;', false);
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//a[contains(@class,"header-nav-btn-primary")]');
            $this->assertGreaterThan(0, $links->length);
            foreach ($links as $link) {
                $this->assertSame(route('demand.create'), $link->getAttribute('href'));
            }
            $this->get(route('demand.create'))->assertOk()
                ->assertDontSee('id="sidebarNav"', false)
                ->assertDontSee('id="onb-step-1"', false)
                ->assertSee('const installSurfaceAllowed = false;', false);
        }
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'professionnel', 'plan' => 'FREE', 'profile_public' => true,
        ], $attributes));
    }

    private function ad(User $user, string $type): Ad
    {
        return Ad::create([
            'user_id' => $user->id, 'title' => 'Plomberie de test', 'description' => 'Annonce de test',
            'category' => 'Plombier', 'main_category' => 'Bricolage & Travaux', 'location' => 'Mamoudzou',
            'price_type' => 'negotiable', 'service_type' => $type, 'status' => 'active',
        ]);
    }
}
