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
        $this->assertStringStartsWith('verifications-temp/' . $user->id, $verification->document_front);

        $page = $this->actingAs($user)->get(route('verification.index'));

        $page->assertOk();
        $page->assertSee('Finaliser le paiement');
        $page->assertSee('Payer par carte');
        $page->assertDontSee('Demande en cours de traitement');
    }

    public function test_paid_verification_is_sent_to_admin_review_after_points_payment(): void
    {
        $diskName = config('filesystems.default', 'public');
        Storage::fake($diskName);

        $user = User::factory()->create([
            'available_points' => IdentityVerification::getVerificationPointsCost('profile_verification'),
            'identity_verified' => false,
            'is_verified' => false,
        ]);

        $frontPath = 'verifications-temp/' . $user->id . '/front.jpg';
        $selfiePath = 'verifications-temp/' . $user->id . '/selfie.jpg';
        Storage::disk($diskName)->put($frontPath, 'front');
        Storage::disk($diskName)->put($selfiePath, 'selfie');

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'type' => 'profile_verification',
            'document_type' => 'id_card',
            'document_front' => $frontPath,
            'document_front_status' => 'pending',
            'selfie' => $selfiePath,
            'selfie_status' => 'pending',
            'payment_amount' => IdentityVerification::getVerificationPrice('profile_verification'),
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('verification.pay.points'), [
            'verification_id' => $verification->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $verification->refresh();

        $this->assertSame('pending', $verification->status);
        $this->assertSame('paid', $verification->payment_status);
        $this->assertStringStartsWith('verifications/' . $user->id, $verification->document_front);
        Storage::disk($diskName)->assertExists($verification->document_front);
    }

    public function test_profile_pages_show_public_verification_badge_and_owner_action(): void
    {
        $owner = User::factory()->create([
            'identity_verified' => false,
            'is_verified' => false,
        ]);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('profile.public', $owner->id))
            ->assertOk()
            ->assertSee('Profil non vérifié');

        $this->actingAs($owner)
            ->get(route('profile.public', $owner->id))
            ->assertOk()
            ->assertSee('Profil non vérifié')
            ->assertSee('Vérifier mon profil');
    }
}
