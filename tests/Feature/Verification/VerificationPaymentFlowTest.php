<?php

namespace Tests\Feature\Verification;

use App\Models\IdentityVerification;
use App\Models\IdentityVerificationDocument;
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

        $user = User::factory()->completeForVerification()->create([
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
        $this->assertStringStartsWith('verification-documents/', $verification->document_front);
        $this->assertDatabaseCount('identity_verification_documents', 2);

        $page = $this->actingAs($user)->get(route('verification.index'));

        $page->assertOk();
        $page->assertSee('Confirmer votre demande');
        $page->assertSee('Continuer par carte');
        $page->assertSee('Vos documents ont bien été enregistrés');
        $page->assertSee('verification-success-alert', false);
        $page->assertDontSee('Finaliser le paiement');
        $page->assertDontSee('Paiement requis');
        $page->assertDontSee('Statut de vérification');
        $page->assertDontSee('Demande en cours de traitement');
    }

    public function test_profile_verification_cannot_bypass_the_five_euro_payment_with_points(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create([
            'available_points' => IdentityVerification::getVerificationPointsCost('profile_verification'),
            'identity_verified' => false,
            'is_verified' => false,
        ]);

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'type' => 'profile_verification',
            'document_type' => 'id_card',
            'document_front' => 'verifications-temp/'.$user->id.'/front.jpg',
            'document_front_status' => 'pending',
            'selfie' => 'verifications-temp/'.$user->id.'/selfie.jpg',
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
            'document_front' => 'verifications/'.$user->id.'/passport.jpg',
            'document_front_status' => 'pending',
            'selfie' => 'verifications/'.$user->id.'/selfie.jpg',
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
        $owner = User::factory()->completeForVerification()->create([
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
            ->assertDontSee('Profil non vérifié')
            ->assertSee('Vérifier mon profil')
            ->assertDontSee('Prestataire de devenir')
            ->assertDontSee('profil de vérification mon')
            ->assertSee('Electricien');

        // La modale de partage réutilise le métier dans ses données sociales ;
        // on vérifie ici qu'il n'est affiché qu'une fois dans le contenu visible du profil.
        $this->assertSame(1, substr_count($profilePage->getContent(), '</i>Plombier'));

        $owner->update(['identity_verified' => true]);

        $this->actingAs($owner)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Devenir prestataire')
            ->assertSeeInOrder(['Devenir prestataire', 'Modifier mon profil']);
    }

    public function test_mobile_camera_fields_are_accepted_for_identity_verification(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create([
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

    public function test_submission_does_not_depend_on_the_remote_filesystem(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.key' => null,
            'filesystems.disks.s3.secret' => null,
            'filesystems.disks.s3.bucket' => null,
            'filesystems.disks.s3.endpoint' => 'https://unreachable.invalid',
        ]);

        $user = User::factory()->completeForVerification()->create([
            'identity_verified' => false,
        ]);

        $response = $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'passport',
            'document_front' => UploadedFile::fake()->create('passport.jpg', 256, 'image/jpeg'),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 256, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('verification.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('identity_verification_documents', 2);
    }

    public function test_verification_documents_are_private_to_the_owner_and_admin(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $verification = IdentityVerification::create([
            'user_id' => $owner->id,
            'type' => 'profile_verification',
            'document_type' => 'passport',
            'document_front' => 'verification-documents/123e4567-e89b-12d3-a456-426614174000.jpg',
            'document_front_status' => 'pending',
            'selfie' => 'verification-documents/123e4567-e89b-12d3-a456-426614174001.jpg',
            'selfie_status' => 'pending',
            'payment_amount' => 5,
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
        ]);

        $document = IdentityVerificationDocument::create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'identity_verification_id' => $verification->id,
            'user_id' => $owner->id,
            'field' => 'document_front',
            'original_name' => 'passport.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 13,
            'content' => base64_encode('private-image'),
        ]);

        $this->actingAs($owner)
            ->get(route('verification.documents.show', $document->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertSee('private-image');

        $this->actingAs($otherUser)
            ->get(route('verification.documents.show', $document->id))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('verification.documents.show', $document->id))
            ->assertOk();
    }

    public function test_verification_form_exposes_the_inline_camera_and_mobile_publish_action(): void
    {
        $user = User::factory()->completeForVerification()->create([
            'identity_verified' => false,
        ]);

        $response = $this->actingAs($user)->get(route('verification.index'));

        $response->assertOk();
        $response->assertSee('Publier');
        $response->assertSee('Vos documents seront transmis à l’administration uniquement après le paiement sécurisé de');
        $response->assertSee('5,00 €');
        $response->assertSee('data-camera-target="page_document_front"', false);
        $response->assertSee('id="verificationCameraOverlay"', false);
        $response->assertSee('Page d’identité du passeport');
        $response->assertSee('maxDimension = 1800');
    }

    public function test_passport_submission_does_not_store_a_back_document(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create([
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
        $this->assertSame('pending', $verification->document_back_status);
        $this->assertSame('pending', $verification->professional_document_status);
    }

    public function test_provider_without_company_structure_is_not_forced_to_upload_kbis(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create([
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
        $this->assertSame('pending', $verification->professional_document_status);
    }

    public function test_incomplete_profile_cannot_submit_identity_verification(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->create([
            'phone' => null,
            'avatar' => null,
            'bio' => null,
            'country' => null,
            'city' => null,
            'address' => null,
            'postal_code' => null,
        ]);

        $this->actingAs($user)
            ->get(route('verification.index'))
            ->assertOk()
            ->assertSee('Profil incomplet')
            ->assertSee('Numéro de téléphone')
            ->assertSee('Photo de profil')
            ->assertSee('Présentation du profil')
            ->assertSee('Compléter mon profil')
            ->assertSee('id="submitVerificationBtn" disabled', false);

        $response = $this->actingAs($user)
            ->from(route('verification.index'))
            ->post(route('verification.store'), [
                'document_type' => 'id_card',
                'document_front' => UploadedFile::fake()->create('front.jpg', 12, 'image/jpeg'),
                'selfie' => UploadedFile::fake()->create('selfie.jpg', 12, 'image/jpeg'),
            ]);

        $response->assertRedirect(route('verification.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertDatabaseCount('identity_verification_documents', 0);
    }
}
