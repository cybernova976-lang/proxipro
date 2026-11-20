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

    public function test_profile_verification_is_free_and_goes_straight_to_admin_review(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create([
            'identity_verified' => false,
            'is_verified' => false,
        ]);

        // Une pièce d'identité suffit : ni paiement, ni selfie.
        $response = $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'id_card',
            'document_front' => UploadedFile::fake()->create('front.jpg', 12, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('verification.index'))->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'a été envoyée'));

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('pending', $verification->status);
        $this->assertSame('paid', $verification->payment_status);
        $this->assertSame('0.00', $verification->payment_amount);
        $this->assertTrue($verification->isFree());
        $this->assertNotNull($verification->submitted_at);
        $this->assertNull($verification->selfie);
        $this->assertNull($verification->paid_at);
        $this->assertStringStartsWith('verification-documents/', $verification->document_front);
        $this->assertDatabaseCount('identity_verification_documents', 1);
        $this->assertDatabaseCount('transactions', 0);

        $page = $this->actingAs($user)->get(route('verification.index'));

        $page->assertOk();
        $page->assertSee('Votre demande est en cours d\'examen', false);
        // (La page porte tout le gabarit du site : on vérifie les libellés du parcours, pas des mots isolés.)
        foreach (['Continuer par carte', 'paiement sécurisé', 'Frais de vérification', '5,00 €', 'Selfie avec document', 'page_selfie'] as $text) {
            $page->assertDontSee($text);
        }

        // Une seconde demande ne s'ajoute pas à la première.
        $this->post(route('verification.store'), [
            'document_type' => 'id_card',
            'document_front' => UploadedFile::fake()->create('front.jpg', 12, 'image/jpeg'),
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('identity_verifications', 1);

        // L'administration la voit dans sa file, notée gratuite, et valide sans selfie.
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.verifications'))->assertOk()
            ->assertSee($user->name)->assertSee('Gratuit');
        $this->get(route('admin.verifications.show', $verification))->assertOk()
            ->assertSee('Vérification gratuite')->assertDontSee('Selfie de vérification');
        $this->post(route('admin.verifications.review-documents', $verification), ['document_front_status' => 'approved'])
            ->assertSessionHas('success');

        $this->assertSame('approved', $verification->fresh()->status);
        $this->assertTrue((bool) $user->fresh()->identity_verified);
    }

    public function test_a_request_saved_before_the_free_verification_is_sent_without_paying(): void
    {
        $user = User::factory()->completeForVerification()->create(['identity_verified' => false]);
        $verification = IdentityVerification::create([
            'user_id' => $user->id, 'type' => 'profile_verification', 'document_type' => 'id_card',
            'document_front' => 'verification-documents/11111111-1111-4111-8111-111111111111.jpg', 'document_front_status' => 'pending',
            'selfie' => 'verification-documents/22222222-2222-4222-8222-222222222222.jpg', 'selfie_status' => 'pending',
            'payment_amount' => 5, 'payment_status' => 'pending', 'status' => 'awaiting_payment',
        ]);

        $this->actingAs($user)->get(route('verification.index'))->assertOk()
            ->assertSee('Envoyer ma demande')->assertSee('désormais gratuite')
            ->assertDontSee('Continuer par carte')->assertDontSee('5,00 €');

        $this->post(route('verification.submit'))->assertRedirect(route('verification.index'))->assertSessionHas('success');

        $verification->refresh();
        $this->assertSame('pending', $verification->status);
        $this->assertSame('paid', $verification->payment_status);
        $this->assertSame('0.00', $verification->payment_amount);
        $this->assertNotNull($verification->submitted_at);
        $this->assertDatabaseCount('transactions', 0);

        // Plus rien à envoyer, et plus aucune route de paiement.
        $this->post(route('verification.submit'))->assertSessionHas('error');
        $this->post('/verification/create-payment', ['verification_id' => $verification->id])->assertNotFound();
    }

    public function test_a_selfie_no_longer_blocks_a_request_in_progress(): void
    {
        $user = User::factory()->completeForVerification()->create(['identity_verified' => false]);
        $make = fn (array $overrides) => IdentityVerification::create(array_merge([
            'user_id' => $user->id, 'type' => 'profile_verification', 'document_type' => 'id_card',
            'document_front' => 'verification-documents/11111111-1111-4111-8111-111111111111.jpg', 'document_front_status' => 'approved',
            'selfie' => 'verification-documents/22222222-2222-4222-8222-222222222222.jpg', 'selfie_status' => 'rejected',
            'selfie_rejection_reason' => 'Photo floue', 'payment_amount' => 5, 'payment_status' => 'paid',
            'status' => 'returned', 'admin_message' => 'Refaites le selfie.', 'submitted_at' => now(),
        ], $overrides));

        $selfieOnly = $make([]);
        $frontToo = $make(['document_front_status' => 'rejected', 'document_front_rejection_reason' => 'Illisible']);
        $closed = $make(['status' => 'rejected']);

        $migration = require database_path('migrations/2026_11_20_120000_release_verifications_blocked_by_a_selfie.php');
        $migration->up();

        // Seul le selfie bloquait : la demande repart en examen.
        $this->assertSame('pending', $selfieOnly->fresh()->status);
        $this->assertSame('pending', $selfieOnly->fresh()->selfie_status);
        $this->assertNull($selfieOnly->fresh()->selfie_rejection_reason);
        $this->assertNotNull($selfieOnly->fresh()->selfie, 'Le selfie déjà enregistré n’est pas supprimé.');
        // Une pièce d'identité refusée reste à corriger, sans redemander de selfie.
        $this->assertSame('returned', $frontToo->fresh()->status);
        $this->assertSame(['document_front'], array_column($frontToo->fresh()->getRejectedDocuments(), 'field'));
        // Une demande clôturée n'est pas touchée.
        $this->assertSame('rejected', $closed->fresh()->status);
        $this->assertSame('rejected', $closed->fresh()->selfie_status);

        $selfieOnly->delete();
        $closed->delete();
        $this->actingAs($user)->get(route('verification.index'))->assertOk()
            ->assertSee('Remplacer les documents refusés')
            ->assertDontSee('Nouveau selfie')->assertDontSee('Selfie avec document')->assertDontSee('resub_selfie', false);
        $this->post(route('verification.resubmit'), [
            'document_front' => UploadedFile::fake()->create('front.jpg', 12, 'image/jpeg'),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('pending', $frontToo->fresh()->status);
    }

    public function test_profile_verification_cannot_bypass_the_five_euro_payment_with_points(): void
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create([
            'available_points' => 1000,
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
            'payment_amount' => 5,
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('verification.pay.points'), [
            'verification_id' => $verification->id,
        ]);

        $response->assertStatus(410);
        $response->assertJsonPath('message', \App\Support\RetiredPoints::MESSAGE);

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
            ->assertSee('Activer mon profil prestataire')
            ->assertSeeInOrder(['Activer mon profil prestataire', 'Modifier mon profil']);
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
        ]);

        $response->assertRedirect(route('verification.index'));

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('id_card', $verification->document_type);
        $this->assertNotNull($verification->document_front);
        $this->assertNull($verification->selfie);
        $this->assertSame('pending', $verification->status);
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
        ]);

        $response->assertRedirect(route('verification.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('identity_verification_documents', 1);
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

    public function test_verification_form_exposes_native_file_and_camera_controls(): void
    {
        $user = User::factory()->completeForVerification()->create([
            'identity_verified' => false,
        ]);

        $response = $this->actingAs($user)->get(route('verification.index'));

        $response->assertOk();
        $response->assertSee('Publier');
        $response->assertSee('La vérification de profil est');
        $response->assertSee('Envoyer ma demande de vérification');
        $response->assertDontSee('5,00 €');
        $response->assertDontSee('paiement sécurisé');
        $response->assertDontSee('Continuer vers le paiement');
        $response->assertSee('class="verification-file-action verification-native-control"', false);
        $response->assertSee('class="verification-file-action is-camera verification-native-control"', false);
        $response->assertSee('class="verification-native-input"', false);
        $response->assertSee('id="page_document_front"', false);
        $response->assertSee('id="page_document_front_camera"', false);
        $response->assertSee('capture="environment"', false);
        $response->assertSee('id="page_document_back"', false);
        $response->assertSee('id="page_document_back_camera"', false);
        $response->assertDontSee('page_selfie', false);
        $response->assertDontSee('Selfie avec document');
        $response->assertDontSee('Photo selfie');
        $response->assertDontSee('capture="user"', false);
        $response->assertSee('touch-action: manipulation', false);
        $this->assertSame(4, substr_count($response->getContent(), 'class="verification-native-input"'));
        $response->assertDontSee('visually-hidden verification-file-input', false);
        $response->assertDontSee('data-file-target=', false);
        $response->assertDontSee('data-camera-target=', false);
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
            ]);

        $response->assertRedirect(route('verification.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertDatabaseCount('identity_verification_documents', 0);
    }
}
