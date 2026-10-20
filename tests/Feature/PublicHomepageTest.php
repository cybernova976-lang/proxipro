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
            ->assertSee('Trouvez le bon service.')
            ->assertSee('Service recherché')
            ->assertSee('Rechercher des annonces')
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
}
