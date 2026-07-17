<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePresentationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cropper_waits_for_the_visible_modal_before_measuring_the_viewport(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk()
            ->assertSee('id="cropLoader"', false)
            ->assertSee('Préparation de la photo…')
            ->assertSee("cropModalEl.addEventListener('shown.bs.modal'", false)
            ->assertSee('window.requestAnimationFrame(initializeCropViewport)', false)
            ->assertSee('Photo cadrée. Enregistrez le profil', false);

        $html = $response->getContent();
        $openCropperStart = strpos($html, 'function openCropper(dataUrl)');
        $openCropperEnd = strpos($html, 'function onDragStart', $openCropperStart);
        $openCropper = substr($html, $openCropperStart, $openCropperEnd - $openCropperStart);

        $this->assertStringContainsString('cropModal.show()', $openCropper);
        $this->assertStringNotContainsString('getBoundingClientRect()', $openCropper);
        $this->assertStringNotContainsString('baseScale =', $openCropper);
    }

    public function test_public_profile_uses_the_new_trust_first_responsive_presentation(): void
    {
        $user = User::factory()->create([
            'name' => 'Amina Martin',
            'profession' => 'Électricienne',
            'bio' => 'J’interviens avec soin pour les installations et dépannages électriques.',
            'avatar' => 'avatars/amina.jpg',
            'city' => 'Lyon',
            'country' => 'France',
            'email_verified_at' => now(),
            'profile_public' => true,
            'pro_intervention_radius' => 35,
        ]);

        $response = $this->get(route('profile.public', $user));

        $response->assertOk()
            ->assertSee('class="container py-4 public-profile-page"', false)
            ->assertSee('class="profile-portrait"', false)
            ->assertSee('Confiance et repères')
            ->assertSee('E-mail confirmé')
            ->assertSee('Zone de 35 km')
            ->assertSee('profile-trust-grid', false)
            ->assertSee('@media (max-width: 575.98px)', false)
            ->assertSee('Annonces de Amina Martin')
            ->assertSee('Avis vérifiés');
    }

    public function test_own_profile_routes_photo_changes_through_the_cropper_and_shows_readiness(): void
    {
        $user = User::factory()->create([
            'avatar' => 'avatars/me.jpg',
            'profile_public' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk()
            ->assertSee('État de mon profil')
            ->assertSee('own-profile-readiness-grid', false)
            ->assertSee('Changer et recadrer ma photo', false)
            ->assertSee(route('profile.edit').'#profile-photo-section', false)
            ->assertDontSee('id="avatarUploadInput"', false);
    }
}
