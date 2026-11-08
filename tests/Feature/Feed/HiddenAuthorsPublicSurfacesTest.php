<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Les comptes internes ou de test (config feed.hidden_author_ids) sont retirés
 * du rail du feed : ils ne doivent pas reparaître dans l'annuaire vers lequel
 * ce rail renvoie, ni dans le plan du site soumis aux moteurs de recherche.
 */
class HiddenAuthorsPublicSurfacesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Aucun appel réseau (géocodage, géolocalisation IP) pendant les tests.
        Http::fake();
    }

    public function test_professional_directory_does_not_list_hidden_accounts(): void
    {
        $internal = $this->provider('Zz Compte interne', 'Wallis-et-Futuna');
        $member = $this->provider('Aa Vrai prestataire', 'Mayotte');
        $viewer = User::factory()->create(['is_active' => true]);

        // Sans réglage, le compte interne figure bien dans l'annuaire.
        $this->actingAs($viewer)->get(route('feed.professionals'))->assertOk()
            ->assertSee('Zz Compte interne');

        config(['feed.hidden_author_ids' => [$internal->id]]);

        $this->actingAs($viewer)->get(route('feed.professionals'))->assertOk()
            ->assertSee('Aa Vrai prestataire')
            ->assertDontSee('Zz Compte interne')
            ->assertViewHas('professionals', fn ($professionals) => collect($professionals->items())
                ->pluck('id')->all() === [$member->id])
            // La liste des pays proposée au filtre ne vient pas non plus du compte masqué.
            ->assertViewHas('directoryCountries', fn ($countries) => $countries->all() === ['Mayotte']);
    }

    public function test_professional_directory_json_does_not_list_hidden_accounts(): void
    {
        $internal = $this->provider('Zz Compte interne', 'Mayotte');
        $member = $this->provider('Aa Vrai prestataire', 'Mayotte');
        $viewer = User::factory()->create(['is_active' => true]);

        $ids = fn () => collect($this->actingAs($viewer)
            ->getJson(route('feed.professionals', ['format' => 'json']))
            ->assertOk()->json('professionals'))->pluck('id');

        $this->assertTrue($ids()->contains($internal->id));

        config(['feed.hidden_author_ids' => [$internal->id]]);

        $visibleIds = $ids();
        $this->assertFalse($visibleIds->contains($internal->id));
        $this->assertTrue($visibleIds->contains($member->id));
    }

    public function test_sitemap_does_not_list_hidden_accounts_ads_or_profiles(): void
    {
        $internal = $this->provider('Zz Compte interne', 'Mayotte');
        $member = $this->provider('Aa Vrai prestataire', 'Mayotte');
        $hiddenAd = $this->offer($internal, 'Offre du compte interne');
        $visibleAd = $this->offer($member, 'Offre d’un vrai membre');
        $loc = fn (string $url) => '<loc>'.$url.'</loc>';

        $body = $this->get(route('sitemap'))->assertOk()->getContent();
        $this->assertStringContainsString($loc(route('ads.show', $hiddenAd)), $body);
        $this->assertStringContainsString($loc(route('profile.public', $internal->id)), $body);

        config(['feed.hidden_author_ids' => [$internal->id]]);

        $body = $this->get(route('sitemap'))->assertOk()->getContent();
        $this->assertStringNotContainsString($loc(route('ads.show', $hiddenAd)), $body);
        $this->assertStringNotContainsString($loc(route('profile.public', $internal->id)), $body);
        $this->assertStringContainsString($loc(route('ads.show', $visibleAd)), $body);
        $this->assertStringContainsString($loc(route('profile.public', $member->id)), $body);
    }

    private function provider(string $name, string $country): User
    {
        return User::factory()->create(['name' => $name, 'is_active' => true, 'profile_public' => true,
            'is_service_provider' => true, 'user_type' => 'professionnel', 'profession' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => $country]);
    }

    private function offer(User $author, string $title): Ad
    {
        return Ad::create(['user_id' => $author->id, 'title' => $title,
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public']);
    }
}
