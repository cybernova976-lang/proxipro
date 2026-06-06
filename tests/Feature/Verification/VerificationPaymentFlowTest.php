<?php

namespace Tests\Feature\Verification;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_verification_submission_waits_for_payment_before_admin_review(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->create([
            'identity_verified' => false,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'id_card',
            'document_front' => UploadedFile::fake()->create('front.jpg', 12, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 12, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('verification.index'));

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('awaiting_payment', $verification->status);
        $this->assertSame('pending', $verification->payment_status);
        $this->assertSame('5.00', $verification->payment_amount);
        $this->assertNull($verification->submitted_at);
        $this->assertStringStartsWith('verifications-temp/' . $user->id, $verification->document_front);

        $page = $this->actingAs($user)->get(route('verification.index'));

        $page->assertOk();
        $page->assertSee('Finaliser le paiement');
        $page->assertSee('Payer par carte');
        $page->assertDontSee('Demande en cours de traitement');
    }

    public function test_profile_verification_cannot_bypass_the_five_euro_payment_with_points(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->create([
            'available_points' => IdentityVerification::getVerificationPointsCost('profile_verification'),
            'identity_verified' => false,
            'is_verified' => false,
        ]);

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'type' => 'profile_verification',
            'document_type' => 'id_card',
            'document_front' => 'verifications-temp/' . $user->id . '/front.jpg',
            'document_front_status' => 'pending',
            'selfie' => 'verifications-temp/' . $user->id . '/selfie.jpg',
            'selfie_status' => 'pending',
            'payment_amount' => IdentityVerification::getVerificationPrice('profile_verification'),
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('verification.pay.points'), [
            'verification_id' => $verification->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        $verification->refresh();

        $this->assertSame('awaiting_payment', $verification->status);
        $this->assertSame('pending', $verification->payment_status);
        $this->assertSame('5.00', $verification->payment_amount);
    }

    public function test_paid_verification_cannot_be_deleted_from_the_payment_cancel_url(): void
    {
        $user = User::factory()->create();

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'type' => 'profile_verification',
            'document_type' => 'passport',
            'document_front' => 'verifications/' . $user->id . '/passport.jpg',
            'document_front_status' => 'pending',
            'selfie' => 'verifications/' . $user->id . '/selfie.jpg',
            'selfie_status' => 'pending',
            'payment_amount' => 5,
            'payment_status' => 'paid',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(
            route('verification.payment.cancel', ['id' => $verification->id])
        );

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('identity_verifications', [
            'id' => $verification->id,
            'payment_status' => 'paid',
            'status' => 'pending',
        ]);
    }

    public function test_profile_pages_show_public_verification_badge_and_owner_action(): void
    {
        $owner = User::factory()->create([
            'identity_verified' => false,
            'is_verified' => false,
            'profession' => 'Plombier',
            'service_category' => 'Plombier',
            'service_subcategories' => ['Plombier', 'Electricien'],
        ]);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('profile.public', $owner->id))
            ->assertOk()
            ->assertSee('Profil non vérifié');

        $this->actingAs($owner)
            ->get(route('profile.public', $owner->id))
            ->assertOk()
            ->assertDontSee('Profil non vérifié')
            ->assertSee('Vérifier mon profil');

        $profilePage = $this->actingAs($owner)->get(route('profile.show'));

        $profilePage
            ->assertOk()
            ->assertSee('Devenir prestataire')
            ->assertSeeInOrder(['Devenir prestataire', 'Modifier mon profil'])
            ->assertDontSee('Profil non vérifié')
            ->assertSee('Vérifier mon profil')
            ->assertDontSee('Prestataire de devenir')
            ->assertDontSee('profil de vérification mon')
            ->assertSee('Electricien');

        $this->assertSame(1, substr_count($profilePage->getContent(), 'Plombier'));
    }

    public function test_mobile_camera_fields_are_accepted_for_identity_verification(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->create([
            'account_type' => 'particulier',
            'identity_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'id_card',
            'document_front_camera' => UploadedFile::fake()->create('camera-front.jpg', 512, 'image/jpeg'),
            'selfie_camera' => UploadedFile::fake()->create('camera-selfie.jpg', 512, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('verification.index'));

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('id_card', $verification->document_type);
        $this->assertNotNull($verification->document_front);
        $this->assertNotNull($verification->selfie);
        $this->assertSame('awaiting_payment', $verification->status);
    }

    public function test_passport_submission_does_not_store_a_back_document(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->create([
            'account_type' => 'particulier',
            'identity_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'passport',
            'document_front' => UploadedFile::fake()->create('passport.jpg', 512, 'image/jpeg'),
            'document_back' => UploadedFile::fake()->create('unwanted-back.jpg', 512, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 512, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('verification.index'));

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('passport', $verification->document_type);
        $this->assertNull($verification->document_back);
        $this->assertNull($verification->document_back_status);
    }

    public function test_provider_without_company_structure_is_not_forced_to_upload_kbis(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->create([
            'account_type' => 'particulier',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'business_type' => null,
            'identity_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'passport',
            'document_front' => UploadedFile::fake()->create('passport.jpg', 512, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 512, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('verification.index'));
        $response->assertSessionHasNoErrors();

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($verification->professional_document);
        $this->assertNull($verification->professional_document_type);
    }
}
