<?php

namespace Tests\Feature\Account;

use App\Models\Ad;
use App\Models\ProfessionalRealization;
use App\Models\User;
use App\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Signalement du 24/11/2026 : depuis son profil, un prestataire doit pouvoir
 * modifier ses compétences, remplacer ou supprimer ses réalisations et gérer
 * toutes ses annonces, avec un vocabulaire clair (demande / offre / annonce).
 */
class OwnPublicationsAndSkillsManagementTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_profile_links_to_skill_photo_and_ad_management(): void
    {
        $provider = $this->provider();
        $this->skill($provider, 'Électricien');
        ProfessionalRealization::create(['user_id' => $provider->id, 'photo_path' => 'professional-realizations/x.png', 'position' => 1]);

        $this->actingAs($provider)->get(route('profile.show'))->assertOk()
            ->assertSee('Mes compétences')
            ->assertSee('href="'.route('service-provider.mes-services').'"', false)
            ->assertSee('Modifier les photos')
            ->assertSee('Gérer mes annonces')
            ->assertSee('href="'.route('ads.myads').'"', false)
            ->assertDontSee('Mes autres compétences');
    }

    public function test_a_realization_photo_can_be_replaced_in_place_by_its_owner_only(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        $provider = $this->provider();
        Storage::disk('public')->put('professional-realizations/old.png', 'x');
        $realization = ProfessionalRealization::create(['user_id' => $provider->id,
            'photo_path' => 'professional-realizations/old.png', 'position' => 2, 'caption' => 'Tableau neuf']);

        $this->actingAs($this->provider())->post(route('profile.realizations.replace', $realization), ['photo' => $this->photo()])
            ->assertForbidden();

        $this->actingAs($provider)->post(route('profile.realizations.replace', $realization), ['photo' => $this->photo()])
            ->assertRedirect(route('profile.edit').'#profile-realizations');

        $realization->refresh();
        $this->assertNotSame('professional-realizations/old.png', $realization->photo_path);
        $this->assertSame(2, (int) $realization->position);
        $this->assertSame('Tableau neuf', $realization->caption);
        Storage::disk('public')->assertExists($realization->photo_path);
        Storage::disk('public')->assertMissing('professional-realizations/old.png');

        $this->get(route('profile.edit'))->assertOk()
            ->assertSee('Remplacer')->assertSee('Supprimer la réalisation 1')
            ->assertSee(route('profile.realizations.replace', $realization), false);

        $this->delete(route('profile.realizations.destroy', $realization));
        $this->assertDatabaseMissing('professional_realizations', ['id' => $realization->id]);
    }

    public function test_skills_can_be_edited_and_deleted_but_never_the_last_one(): void
    {
        $provider = $this->provider();
        $electrician = $this->skill($provider, 'Électricien');
        $plumber = $this->skill($provider, 'Plombier');

        $this->actingAs($provider)->get(route('service-provider.mes-services'))->assertOk()
            ->assertSee('Mes compétences')->assertSee('Modifier')->assertSee('Mettre en pause')->assertSee('Supprimer');

        $this->putJson(route('service-provider.update-service', $electrician->id), ['experience_years' => 7, 'description' => 'Mise aux normes'])
            ->assertOk();
        $this->assertSame(7, (int) $electrician->fresh()->experience_years);
        $this->putJson(route('service-provider.update-service', $electrician->id), ['experience_years' => null])->assertOk();
        $this->assertSame(0, (int) $electrician->fresh()->experience_years);

        $this->deleteJson(route('service-provider.delete-service', $plumber->id))->assertOk();
        $this->assertDatabaseMissing('user_services', ['id' => $plumber->id]);

        $this->deleteJson(route('service-provider.delete-service', $electrician->id))->assertStatus(422);
        $this->assertDatabaseHas('user_services', ['id' => $electrician->id]);
        $this->assertTrue((bool) $provider->fresh()->is_service_provider);
    }

    public function test_my_ads_lists_offers_and_requests_with_edit_offline_and_delete(): void
    {
        $provider = $this->provider();
        $offer = $this->ad($provider, 'Dépannage électrique', 'offre');
        $this->ad($provider, 'Cherche aide pour déménagement', 'demande', ['status' => 'inactive']);

        $this->actingAs($provider)->get(route('ads.myads'))->assertOk()
            ->assertSee('Mes annonces')->assertSee('Dépannage électrique')->assertSee('Cherche aide pour déménagement')
            ->assertSee('Mettre hors ligne')->assertSee('Remettre en ligne')->assertSee('Supprimer')
            ->assertSee(route('ads.edit', $offer), false);

        $this->get(route('ads.myads', ['type' => 'offre']))->assertOk()
            ->assertSee('<h1>Mes offres</h1>', false)->assertSee('Publier une offre')
            ->assertSee('Dépannage électrique')->assertDontSee('Cherche aide pour déménagement');

        $this->delete(route('ads.destroy', $offer))->assertRedirect();
        $this->assertDatabaseMissing('ads', ['id' => $offer->id]);
    }

    public function test_navigation_uses_offer_and_request_vocabulary(): void
    {
        $provider = $this->provider();
        $html = $this->actingAs($provider)->get(route('feed', ['mode' => 'provider']))->assertOk()->getContent();
        $this->assertStringContainsString('data-header-offers', $html);
        $this->assertStringContainsString('Mes offres', $html);
        $this->assertStringContainsString('Gérer mes annonces', $html);

        $client = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
        $this->actingAs($client)->get(route('ads.myads'))->assertOk()
            ->assertSee('Gérer mes annonces')->assertSee('Publier une demande')
            ->assertDontSee('btn btn-outline-primary">Publier une offre', false)->assertDontSee('aria-label="Type d’annonce"', false);
    }

    private function provider(): User
    {
        return User::factory()->create(['user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true]);
    }

    private function skill(User $user, string $name): UserService
    {
        return UserService::create(['user_id' => $user->id, 'main_category' => 'Bricolage & Travaux',
            'subcategory' => $name, 'experience_years' => 0, 'is_active' => true]);
    }

    private function ad(User $user, string $title, string $type, array $attributes = []): Ad
    {
        return Ad::create(array_merge(['user_id' => $user->id, 'title' => $title, 'description' => 'Description de test.',
            'main_category' => 'Bricolage & Travaux', 'category' => 'Plombier', 'service_type' => $type,
            'location' => 'Paris', 'status' => 'active', 'expires_at' => now()->addDays(10)], $attributes));
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('nouvelle.png', base64_decode(self::ONE_PIXEL_PNG));
    }
}
