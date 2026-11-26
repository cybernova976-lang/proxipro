<?php

namespace Tests\Feature\Verification;

use App\Models\IdentityVerification;
use App\Models\IdentityVerificationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La politique de confidentialité promet l'effacement des pièces après la décision :
 * validation et refus les effacent, un renvoi pour correction les garde.
 */
class VerificationDocumentsDeletedAfterDecisionTest extends TestCase
{
    use RefreshDatabase;

    private function submitted(): array
    {
        Storage::fake(config('filesystems.default', 'public'));

        $user = User::factory()->completeForVerification()->create(['identity_verified' => false, 'is_verified' => false]);
        $this->actingAs($user)->post(route('verification.store'), [
            'document_type' => 'id_card',
            'document_front' => UploadedFile::fake()->create('front.jpg', 12, 'image/jpeg'),
            'document_back' => UploadedFile::fake()->create('back.jpg', 12, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $verification = IdentityVerification::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(2, IdentityVerificationDocument::count());

        return [$user, $verification, User::factory()->create(['role' => 'admin'])];
    }

    public function test_approving_document_by_document_deletes_the_documents_and_keeps_the_result(): void
    {
        [$user, $verification, $admin] = $this->submitted();

        $this->actingAs($admin)->post(route('admin.verifications.review-documents', $verification), [
            'document_front_status' => 'approved',
            'document_back_status' => 'approved',
        ])->assertSessionHas('success');

        $verification->refresh();
        $this->assertSame('approved', $verification->status);
        $this->assertSame('approved', $verification->document_front_status);
        $this->assertNull($verification->document_front);
        $this->assertNull($verification->document_back);
        $this->assertNotNull($verification->documents_deleted_at);
        $this->assertSame(0, IdentityVerificationDocument::count());
        $this->assertTrue($user->fresh()->hasVerifiedProfileBadge());

        $this->get(route('admin.verifications.show', $verification))->assertOk()
            ->assertSee('Pièces effacées le')->assertSee('Effacée après la décision');
    }

    public function test_global_approval_and_rejection_delete_the_documents(): void
    {
        [, $verification, $admin] = $this->submitted();
        $this->actingAs($admin)->post(route('admin.verifications.approve', $verification))->assertSessionHas('success');
        $this->assertNotNull($verification->fresh()->documents_deleted_at);
        $this->assertSame(0, IdentityVerificationDocument::count());

        [, $other] = $this->submitted();
        $this->actingAs($admin)->post(route('admin.verifications.reject', $other), ['rejection_reason' => 'Document illisible'])
            ->assertSessionHas('success');
        $other->refresh();
        $this->assertSame('rejected', $other->status);
        $this->assertSame('Document illisible', $other->rejection_reason);
        $this->assertNull($other->document_front);
        $this->assertSame(0, IdentityVerificationDocument::count());
    }

    public function test_a_request_returned_for_correction_keeps_its_documents(): void
    {
        [, $verification, $admin] = $this->submitted();

        $this->actingAs($admin)->post(route('admin.verifications.review-documents', $verification), [
            'document_front_status' => 'approved',
            'document_back_status' => 'rejected',
            'document_back_rejection_reason' => 'Verso flou',
        ])->assertSessionHas('success');

        $verification->refresh();
        $this->assertSame('returned', $verification->status);
        $this->assertNotNull($verification->document_front);
        $this->assertNull($verification->documents_deleted_at);
        $this->assertSame(2, IdentityVerificationDocument::count());
    }

    public function test_the_command_only_counts_unless_forced(): void
    {
        [$user, $verification] = $this->submitted();
        // Demande tranchée avant l'effacement automatique : ses pièces sont encore là.
        $verification->forceFill(['status' => 'approved'])->save();

        $this->artisan('verifications:delete-decided-documents')
            ->expectsOutputToContain('1 demande(s) tranchée(s) gardent encore leurs pièces')
            ->assertSuccessful();
        $this->assertSame(2, IdentityVerificationDocument::count());

        $this->artisan('verifications:delete-decided-documents', ['--force' => true])->assertSuccessful();
        $this->assertSame(0, IdentityVerificationDocument::count());
        $this->assertNotNull($verification->fresh()->documents_deleted_at);
    }
}
