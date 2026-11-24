<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/** Demande du fondateur du 24/11/2026 : on doit pouvoir supprimer sa photo de profil, pas seulement la remplacer. */
class ProfilePhotoRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithPhoto(array $attributes = []): User
    {
        $disk = config('filesystems.default', 'public');
        Storage::fake($disk);
        $path = 'avatars/moi-'.uniqid().'.jpg';
        Storage::disk($disk)->put($path, 'photo');

        return User::factory()->create(['avatar' => $path] + $attributes);
    }

    public function test_the_edit_page_offers_to_remove_the_photo_only_when_there_is_one(): void
    {
        $member = $this->memberWithPhoto();
        $this->actingAs($member)->get(route('profile.edit'))->assertOk()
            ->assertSee('Supprimer la photo')
            ->assertSee(route('profile.avatar.destroy'), false);

        $withoutPhoto = User::factory()->create(['avatar' => null]);
        $this->actingAs($withoutPhoto)->get(route('profile.edit'))->assertOk()
            ->assertDontSee('Supprimer la photo');
    }

    public function test_removing_the_photo_clears_the_profile_and_erases_the_file(): void
    {
        $member = $this->memberWithPhoto();
        $path = $member->avatar;
        $disk = config('filesystems.default', 'public');
        Storage::disk($disk)->assertExists($path);

        $this->actingAs($member)->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.edit').'#profile-photo-section')
            ->assertSessionHas('success', 'Votre photo de profil a été supprimée.');

        $member->refresh();
        $this->assertNull($member->avatar);
        $this->assertNotNull($member->avatar_removed_at);
        Storage::disk($disk)->assertMissing($path);

        // Le profil affiche l'initiale à la place de la photo.
        $this->actingAs($member)->get(route('profile.show'))->assertOk()
            ->assertSee('profileAvatarPlaceholder', false)
            ->assertDontSee('profileAvatarImg', false);
    }

    public function test_a_photo_taken_from_a_social_account_is_removed_without_touching_storage(): void
    {
        Storage::fake(config('filesystems.default', 'public'));
        $member = User::factory()->create(['avatar' => 'https://lh3.example.test/photo.jpg']);

        $this->actingAs($member)->delete(route('profile.avatar.destroy'))->assertRedirect();

        $this->assertNull($member->fresh()->avatar);
    }

    public function test_the_pro_space_also_offers_the_removal_and_returns_there(): void
    {
        $member = $this->memberWithPhoto(['is_service_provider' => true, 'user_type' => 'professionnel']);
        $this->actingAs($member)->get(route('pro.profile.edit'))->assertOk()
            ->assertSee('Supprimer la photo')->assertSee('id="deleteAvatarForm"', false);

        $this->actingAs($member)->delete(route('profile.avatar.destroy'), ['return' => 'pro'])
            ->assertRedirect(route('pro.profile.edit'));
        $this->assertNull($member->fresh()->avatar);
    }

    public function test_guests_cannot_remove_a_photo(): void
    {
        $this->delete(route('profile.avatar.destroy'))->assertRedirect(route('login'));
    }

    public function test_google_login_does_not_bring_back_a_removed_photo(): void
    {
        $member = User::factory()->create(['email' => 'membre.google@example.com', 'avatar' => null,
            'avatar_removed_at' => now(), 'provider' => 'google', 'provider_id' => 'google-42']);

        $socialUser = Mockery::mock();
        $socialUser->shouldReceive('getEmail')->andReturn('membre.google@example.com');
        $socialUser->shouldReceive('getId')->andReturn('google-42');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://lh3.example.test/photo.jpg');
        $socialUser->shouldReceive('getName')->andReturn($member->name);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('social.callback', ['provider' => 'google']))->assertRedirect();

        $this->assertAuthenticatedAs($member);
        $this->assertNull($member->fresh()->avatar);
    }
}
