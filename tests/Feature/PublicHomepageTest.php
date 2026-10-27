<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\ProfessionalRealization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_homepage_prioritizes_actions_without_activity_counters(): void
    {
        $response = $this->get(route('homepage'))->assertOk()
            ->assertSee('Trouvez le bon prestataire,')
            ->assertSee('Service recherché')
            ->assertSee('Voir les services')
            ->assertSee(route('demand.create'), false)
            ->assertSee(route('register'), false)
            ->assertSee('Explorer tous les services')
            ->assertDontSee('Professionnels inscrits')
            ->assertDontSee('Annonces actives')
            ->assertDontSee('Membres actifs')
            ->assertDontSee('data-count', false)
            ->assertDontSee('counterObserver', false)
            ->assertDontSee('Réponse rapide')
            ->assertDontSee('de nouveaux clients chaque jour')
            ->assertDontSee('Des prestataires à découvrir')
            ->assertDontSee('Besoins et services récents');

        $this->assertArrayNotHasKey('totalUsers', $response->viewData());
        $this->assertFalse(Cache::has('homepage.stats'));
    }

    public function test_public_showcase_precedes_categories_with_member_ads_and_complete_providers(): void
    {
        $attributes = [
            'user_type' => 'particulier',
            'is_service_provider' => true,
            'profile_public' => true,
            'is_active' => true,
            'profession' => 'Plombier',
            'city' => 'Mamoudzou',
            'avatar' => 'avatars/provider.jpg',
            'bio' => 'Je présente mes services de plomberie, mes compétences et les interventions que je réalise.',
        ];
        $provider = User::factory()->create($attributes);
        User::factory()->create(array_merge($attributes, ['profile_public' => false]));
        User::factory()->create(array_merge($attributes, ['is_active' => false]));
        User::factory()->create(array_merge($attributes, ['is_service_provider' => false]));

        foreach (range(1, 5) as $number) {
            Ad::create([
                'user_id' => $provider->id,
                'title' => "Service test $number",
                'description' => 'Présentation détaillée du service de plomberie et des interventions proposées.',
                'category' => 'Plombier',
                'location' => 'Mamoudzou',
                'service_type' => 'offre',
                'status' => 'active',
                'visibility' => 'public',
            ]);
        }

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('featuredPros', fn ($profiles) => $profiles->modelKeys() === [$provider->id])
            ->assertViewHas('latestAds', fn ($ads) => $ads->count() === 2)
            ->assertSeeInOrder([
                'Découvrez les annonces des membres',
                'Explorez les services',
                'Comment ça marche ?',
                'Des prestataires à découvrir',
            ])
            ->assertSee(route('profile.public', $provider), false);
    }

    public function test_signed_in_users_still_reach_their_feed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('homepage'))
            ->assertRedirect(route('feed'));
    }

    public function test_guest_discovery_has_server_rendered_seo_and_two_clear_journeys(): void
    {
        $response = $this->get(route('homepage'))->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('name="service_type" value="offre"', false)
            ->assertSee('id="home-service" name="q"', false)
            ->assertSee('id="home-location"', false)
            ->assertSee('Publier mon besoin')
            ->assertSee('Je cherche un service')
            ->assertSee('Je propose mes services')
            ->assertSee('home-client-panel')
            ->assertSee('home-provider-panel')
            ->assertSee('aria-label="Choisir mon parcours" hidden', false)
            ->assertSee('Quelques réponses, tout simplement.')
            ->assertSee('Combien coûte un profil prestataire ?')
            ->assertSee('Y a-t-il des prestataires dans ma ville ?')
            ->assertSee('Les outils d’émission de devis et factures PRO sont réservés aux professionnels');

        $this->assertSame(1, substr_count($response->getContent(), '<h1 '));
        $this->assertStringNotContainsString('id="home-provider-panel" hidden', $response->getContent());
    }

    public function test_homepage_does_not_promote_expired_or_nonpublic_ads_or_inactive_owners(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $attributes = [
            'user_id' => $owner->id,
            'title' => 'Annonce visible',
            'description' => 'Présentation détaillée du service et des interventions proposées.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
        ];
        $publicAd = Ad::create($attributes);
        Ad::create(array_merge($attributes, ['title' => 'Annonce expirée', 'expires_at' => now()->subDay()]));
        Ad::create(array_merge($attributes, ['title' => 'Annonce privée', 'visibility' => 'private']));
        Ad::create(array_merge($attributes, ['title' => 'Annonce suspendue', 'status' => 'inactive']));
        $inactive = User::factory()->create(['is_active' => false]);
        Ad::create(array_merge($attributes, ['title' => 'Compte inactif', 'user_id' => $inactive->id]));

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$publicAd->id])
            ->assertSee('Annonce visible')
            ->assertDontSee('Annonce expirée')
            ->assertDontSee('Annonce privée')
            ->assertDontSee('Annonce suspendue')
            ->assertDontSee('Compte inactif');
    }

    public function test_showcase_keeps_recent_demands_and_active_offers_without_hiding_dates_or_reclassifying_them(): void
    {
        $this->travelTo(now()->startOfSecond());
        $owner = User::factory()->create(['is_active' => true]);
        $boundary = $this->createShowcaseAd($owner, ['created_at' => now()->subDays(30), 'service_type' => 'demande']);
        $old = $this->createShowcaseAd($owner, ['created_at' => now()->subDays(30)->subSecond(), 'service_type' => 'demande']);
        $future = $this->createShowcaseAd($owner, ['created_at' => now()->addDay()]);
        $offer = $this->createShowcaseAd($owner, ['created_at' => now()->subDay(), 'service_type' => 'offre']);
        $request = $this->createShowcaseAd($owner, ['created_at' => now(), 'service_type' => 'demande']);

        $response = $this->get(route('homepage'))->assertOk()
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$request->id, $offer->id, $boundary->id]);

        $this->assertSame(['demande', 'offre', 'demande'], $response->viewData('latestAds')->pluck('service_type')->all());
        $this->assertSame('demande', $request->fresh()->service_type);
        $this->assertSame('offre', $offer->fresh()->service_type);

        $offer->update(['status' => 'inactive']);
        $request->update(['status' => 'inactive']);
        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$boundary->id]);

        $this->assertDatabaseHas('ads', ['id' => $old->id, 'status' => 'active']);
        $this->assertDatabaseHas('ads', ['id' => $future->id, 'status' => 'active']);
    }

    public function test_incomplete_or_unlocated_publications_are_not_used_to_fill_the_homepage(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $valid = $this->createShowcaseAd($owner);
        foreach ([
            ['title' => '   '],
            ['description' => 'Trop court'],
            ['city' => ' ', 'location' => '   '],
            ['service_type' => 'incorrect'],
        ] as $overrides) {
            $this->createShowcaseAd($owner, $overrides);
        }

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$valid->id]);
    }

    public function test_featured_profiles_require_a_real_presentation_and_public_work_but_do_not_judge_display_names(): void
    {
        $valid = $this->createShowcaseProvider(['name' => 'ATELIER CYBER NOVA']);
        $this->createShowcaseAd($valid);
        foreach ([
            ['avatar' => null],
            ['avatar' => '   '],
            ['bio' => str_repeat('é', 39)],
            ['profession' => '   '],
            ['city' => ' '],
            ['is_active' => false],
            ['profile_public' => false],
            ['is_service_provider' => false],
        ] as $overrides) {
            $provider = $this->createShowcaseProvider($overrides);
            $this->createShowcaseAd($provider);
        }

        $withoutPublicWork = $this->createShowcaseProvider();
        $onlyRequest = $this->createShowcaseProvider();
        $this->createShowcaseAd($onlyRequest, ['service_type' => 'demande']);
        foreach ([
            ['visibility' => 'private'],
            ['status' => 'inactive'],
            ['expires_at' => now()->subDay()],
        ] as $overrides) {
            $provider = $this->createShowcaseProvider();
            $this->createShowcaseAd($provider, $overrides);
        }

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('featuredPros', fn ($profiles) => $profiles->modelKeys() === [$valid->id])
            ->assertSee('ATELIER CYBER NOVA');

        $this->assertTrue($withoutPublicWork->fresh()->profile_public);
        $this->assertTrue($onlyRequest->fresh()->is_service_provider);
        $this->assertSame('ATELIER CYBER NOVA', $valid->fresh()->name);
    }

    public function test_three_complete_profiles_with_realizations_can_be_featured_without_an_offer_or_an_invented_badge(): void
    {
        $providers = collect(range(1, 4))->map(function () {
            $provider = $this->createShowcaseProvider([
                'profession' => 'Réparateur de machines anciennes',
                'bio' => str_repeat('é', 40),
            ]);
            ProfessionalRealization::create(['user_id' => $provider->id, 'photo_path' => 'realizations/work.jpg']);

            return $provider;
        });
        $verified = $providers->last();
        $verified->forceFill(['identity_verified' => true])->save();
        $emptyWork = $this->createShowcaseProvider();
        ProfessionalRealization::create(['user_id' => $emptyWork->id, 'photo_path' => '   ']);

        $response = $this->get(route('homepage'))->assertOk()
            ->assertViewHas('featuredPros', fn ($profiles) => $profiles->modelKeys() === [
                $verified->id, $providers[0]->id, $providers[1]->id,
            ]);

        $this->assertFalse($response->viewData('featuredPros')[1]->identity_verified);
        $this->assertSame(0, $response->viewData('featuredPros')[1]->verified_reviews_count);
    }

    public function test_a_complete_provider_with_an_older_active_offer_remains_discoverable_without_promoting_it_as_recent(): void
    {
        $provider = $this->createShowcaseProvider();
        $oldOffer = $this->createShowcaseAd($provider, ['created_at' => now()->subMonths(3)]);

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('featuredPros', fn ($profiles) => $profiles->modelKeys() === [$provider->id])
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$oldOffer->id])
            ->assertSee(route('profile.public', $provider), false)
            ->assertDontSee('Besoins et services récents');

        $this->assertSame('active', $oldOffer->fresh()->status);
        $this->assertTrue($oldOffer->fresh()->created_at->lt(now()->subDays(30)));
    }

    public function test_explicit_title_type_conflicts_are_skipped_before_the_two_card_limit_without_rewriting_publications(): void
    {
        $this->travelTo(now()->startOfSecond());
        $owner = User::factory()->create(['is_active' => true]);
        $offer = $this->createShowcaseAd($owner, [
            'title' => 'Montage de meubles à domicile',
            'created_at' => now()->subMinutes(4),
        ]);
        $request = $this->createShowcaseAd($owner, [
            'title' => 'Cherche serrurier disponible',
            'service_type' => 'demande',
            'created_at' => now()->subMinutes(3),
        ]);
        $ambiguousRequest = $this->createShowcaseAd($owner, [
            'title' => 'Serrurier disponible',
            'service_type' => 'demande',
            'created_at' => now()->subMinutes(2),
        ]);
        $proposedService = $this->createShowcaseAd($owner, [
            'title' => 'JE PROPOSE MES SERVICES',
            'service_type' => 'demande',
            'created_at' => now()->subMinute(),
        ]);
        $ambiguousOffer = $this->createShowcaseAd($owner, [
            'title' => 'RECHERCHE ÉLECTRICIEN DISPONIBLE',
            'service_type' => 'offre',
            'created_at' => now(),
        ]);

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$request->id, $offer->id]);

        $this->assertDatabaseHas('ads', ['id' => $ambiguousRequest->id, 'title' => 'Serrurier disponible', 'service_type' => 'demande', 'status' => 'active']);
        $this->assertDatabaseHas('ads', ['id' => $proposedService->id, 'service_type' => 'demande', 'visibility' => 'public']);
        $this->assertDatabaseHas('ads', ['id' => $ambiguousOffer->id, 'title' => 'RECHERCHE ÉLECTRICIEN DISPONIBLE', 'service_type' => 'offre']);
    }

    public function test_a_service_offer_can_address_a_need_when_its_title_explicitly_proposes_services(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $offer = $this->createShowcaseAd($owner, [
            'title' => 'Besoin d’aide ? Je propose mes services',
            'service_type' => 'offre',
        ]);

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('latestAds', fn ($ads) => $ads->modelKeys() === [$offer->id]);
    }

    public function test_each_type_gets_two_places_even_when_many_offers_are_newer(): void
    {
        $owner = User::factory()->create(['name' => 'Atelier du quartier', 'is_active' => true]);
        foreach (range(1, 6) as $index) {
            $this->createShowcaseAd($owner, ['created_at' => now()->subMinutes($index)]);
        }
        foreach (range(1, 3) as $index) {
            $this->createShowcaseAd($owner, [
                'title' => 'Besoin d’aide pour une fuite', 'service_type' => 'demande',
                'created_at' => now()->subDays($index), 'price_type' => 'fixed', 'price' => 150,
            ]);
        }

        $response = $this->get(route('homepage'))->assertOk();
        $publications = $response->viewData('latestAds');
        $this->assertCount(4, $publications);
        $this->assertCount(2, $publications->where('service_type', 'offre'));
        $this->assertCount(2, $publications->where('service_type', 'demande'));
        $response->assertSee('Présentation détaillée du service de plomberie')
            ->assertSee('Mamoudzou')->assertSee('150 €')->assertSee('Atelier du quartier')
            ->assertSee('Demande de service')->assertSee('Proposer mon aide')
            ->assertDontSee('Exemple illustratif')->assertDontSee('Pour en savoir plus');
        foreach ($publications as $ad) {
            $response->assertSee(route('login', ['continue' => 'ad', 'ad' => $ad->id]));
        }
        $this->assertDatabaseCount('ads', 9);
    }

    private function createShowcaseProvider(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'particulier',
            'is_service_provider' => true,
            'profile_public' => true,
            'is_active' => true,
            'profession' => 'Plombier',
            'city' => 'Mamoudzou',
            'avatar' => 'avatars/provider.jpg',
            'bio' => 'Je présente mes services de plomberie, mes compétences et les interventions que je réalise.',
        ], $overrides));
    }

    private function createShowcaseAd(User $owner, array $overrides = []): Ad
    {
        $ad = new Ad;
        $ad->forceFill(array_merge([
            'user_id' => $owner->id,
            'title' => 'Intervention de plomberie',
            'description' => 'Présentation détaillée du service de plomberie et des interventions proposées.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
        ], $overrides));
        $ad->save();

        return $ad;
    }
}
