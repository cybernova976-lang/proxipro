<?php

namespace Tests\Feature;

use App\Models\Ad;
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
            ->assertDontSee('Les dernières annonces publiées');

        $this->assertArrayNotHasKey('totalUsers', $response->viewData());
        $this->assertFalse(Cache::has('homepage.stats'));
    }

    public function test_only_public_active_providers_are_shown_before_the_compact_ad_selection(): void
    {
        $attributes = [
            'user_type' => 'particulier',
            'is_service_provider' => true,
            'profile_public' => true,
            'is_active' => true,
            'profession' => 'Plombier',
            'city' => 'Mamoudzou',
        ];
        $provider = User::factory()->create($attributes);
        User::factory()->create(array_merge($attributes, ['profile_public' => false]));
        User::factory()->create(array_merge($attributes, ['is_active' => false]));
        User::factory()->create(array_merge($attributes, ['is_service_provider' => false]));

        foreach (range(1, 5) as $number) {
            Ad::create([
                'user_id' => $provider->id,
                'title' => "Service test $number",
                'description' => 'Présentation du service.',
                'category' => 'Plombier',
                'location' => 'Mamoudzou',
                'service_type' => 'offre',
                'status' => 'active',
                'visibility' => 'public',
            ]);
        }

        $this->get(route('homepage'))->assertOk()
            ->assertViewHas('featuredPros', fn ($profiles) => $profiles->modelKeys() === [$provider->id])
            ->assertViewHas('latestAds', fn ($ads) => $ads->count() === 3)
            ->assertSeeInOrder([
                'Quel service vous rendrait la vie plus simple ?',
                'Des prestataires à découvrir',
                'Les dernières annonces publiées',
                'Comment ça marche ?',
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
            ->assertSee('aria-label="Publier une demande" hidden', false)
            ->assertSee('Quelques réponses, tout simplement.')
            ->assertSee('Les outils d’émission de devis et factures PRO sont réservés aux professionnels');

        $this->assertSame(1, substr_count($response->getContent(), '<h1 '));
        $this->assertSame(5, substr_count($response->getContent(), '<details>'));
        $this->assertStringNotContainsString('id="home-provider-panel" hidden', $response->getContent());
    }

    public function test_homepage_does_not_promote_expired_or_nonpublic_ads_or_inactive_owners(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $attributes = [
            'user_id' => $owner->id,
            'title' => 'Annonce visible',
            'description' => 'Présentation du service.',
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
}
