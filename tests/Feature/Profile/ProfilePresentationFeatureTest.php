<?php

namespace Tests\Feature\Profile;

use App\Models\Ad;
use App\Models\Review;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee("document.addEventListener('DOMContentLoaded'", false)
            ->assertSee('window.bootstrap?.Modal', false)
            ->assertSee("cropModalEl.addEventListener('shown.bs.modal'", false)
            ->assertSee('window.requestAnimationFrame(initializeCropViewport)', false)
            ->assertSee('Photo cadrée. Enregistrez le profil', false)
            ->assertSee('Photo sélectionnée. Enregistrez le profil', false);

        $html = $response->getContent();
        $openCropperStart = strpos($html, 'function openCropper(dataUrl)');
        $openCropperEnd = strpos($html, 'function onDragStart', $openCropperStart);
        $openCropper = substr($html, $openCropperStart, $openCropperEnd - $openCropperStart);

        $this->assertStringContainsString('cropModal.show()', $openCropper);
        $this->assertStringNotContainsString('getBoundingClientRect()', $openCropper);
        $this->assertStringNotContainsString('baseScale =', $openCropper);
    }

    public function test_user_can_upload_a_profile_photo_and_old_file_is_removed_after_success(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old-avatar.jpg', 'old');

        $user = User::factory()->create(['avatar' => 'avatars/old-avatar.jpg']);
        $avatar = UploadedFile::fake()->createWithContent(
            'new-avatar.png',
            base64_decode(self::ONE_PIXEL_PNG)
        );

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatar,
        ]);

        $response->assertRedirect(route('profile.show'))->assertSessionHasNoErrors();

        $storedAvatar = $user->fresh()->avatar;
        $this->assertNotSame('avatars/old-avatar.jpg', $storedAvatar);
        Storage::disk('public')->assertExists($storedAvatar);
        Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    }

    public function test_dashboard_cropper_also_waits_until_its_modal_is_visible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard.profile-edit'));

        $response->assertOk()
            ->assertSee('window.bootstrap?.Modal', false)
            ->assertSee("cropModalEl.addEventListener('shown.bs.modal'", false)
            ->assertSee('window.requestAnimationFrame(resetCrop)', false)
            ->assertSee('width:min(280px, 100%)', false);

        $html = $response->getContent();
        $openCropperStart = strpos($html, 'function openCropper(dataUrl)');
        $openCropperEnd = strpos($html, 'function pointerPosition', $openCropperStart);
        $openCropper = substr($html, $openCropperStart, $openCropperEnd - $openCropperStart);

        $this->assertStringContainsString('cropModal.show()', $openCropper);
        $this->assertStringNotContainsString('getBoundingClientRect()', $openCropper);
    }

    public function test_user_can_save_a_cropped_profile_photo(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $user = User::factory()->create(['avatar' => null]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_cropped' => 'data:image/png;base64,'.self::ONE_PIXEL_PNG,
        ]);

        $response->assertRedirect(route('profile.show'))->assertSessionHasNoErrors();

        $storedAvatar = $user->fresh()->avatar;
        $this->assertStringStartsWith('avatars/', $storedAvatar);
        $this->assertStringEndsWith('.png', $storedAvatar);
        Storage::disk('public')->assertExists($storedAvatar);
    }

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

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

    public function test_public_provider_profile_only_surfaces_active_services_and_real_marketplace_proofs(): void
    {
        $provider = User::factory()->create([
            'name' => 'Amina Services',
            'account_type' => 'professionnel',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'is_verified' => true,
            'pro_verified' => true,
            'profile_public' => true,
        ]);
        $client = User::factory()->create(['name' => 'Client Prokejem']);

        UserService::create([
            'user_id' => $provider->id,
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier',
            'experience_years' => 7,
            'description' => 'Dépannage et rénovation sanitaire.',
            'is_verified' => true,
            'is_active' => true,
        ]);
        UserService::create([
            'user_id' => $provider->id,
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Service masqué',
            'description' => 'Cette compétence ne doit pas être publique.',
            'is_active' => false,
        ]);

        $activeAd = Ad::create([
            'user_id' => $provider->id,
            'title' => 'Dépannage plomberie soigné',
            'description' => 'Intervention sur fuite et installation sanitaire.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);
        Ad::create([
            'user_id' => $provider->id,
            'title' => 'Ancienne offre expirée',
            'description' => 'Cette offre ne doit plus apparaître.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $completedOrder = ServiceOrder::create([
            'order_number' => 'CMD-PROFILE-0001',
            'ad_id' => $activeAd->id,
            'buyer_id' => $client->id,
            'seller_id' => $provider->id,
            'amount' => 150,
            'seller_amount' => 135,
            'status' => ServiceOrder::STATUS_COMPLETED,
            'payment_status' => ServiceOrder::PAYMENT_RELEASED,
            'released_at' => now(),
        ]);
        ServiceOrder::create([
            'order_number' => 'CMD-PROFILE-0002',
            'ad_id' => $activeAd->id,
            'buyer_id' => $client->id,
            'seller_id' => $provider->id,
            'amount' => 80,
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
        ]);
        Review::create([
            'reviewer_id' => $client->id,
            'reviewed_user_id' => $provider->id,
            'ad_id' => $activeAd->id,
            'service_order_id' => $completedOrder->id,
            'rating' => 5,
            'comment' => 'Intervention ponctuelle et très propre.',
        ]);

        $response = $this->actingAs($client)->get(route('profile.public', $provider));

        $response->assertOk()
            ->assertSee('Services et savoir-faire')
            ->assertSee('Dépannage et rénovation sanitaire.')
            ->assertSee('Compétence vérifiée', false)
            ->assertDontSee('Service masqué')
            ->assertDontSee('Cette compétence ne doit pas être publique.')
            ->assertSee('1 prestation finalisée')
            ->assertSee('Statut professionnel vérifié')
            ->assertSee('Dépannage plomberie soigné')
            ->assertDontSee('Ancienne offre expirée')
            ->assertSee('Prestation payée sur Prokejem')
            ->assertSee('id="profileContactModal"', false)
            ->assertSee('Aucun message ne sera envoyé avant votre validation.')
            ->assertSee('name="message"', false)
            ->assertDontSee('type="hidden" name="message"', false);
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
