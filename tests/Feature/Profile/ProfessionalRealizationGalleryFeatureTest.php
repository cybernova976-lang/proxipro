<?php

namespace Tests\Feature\Profile;

use App\Models\ProfessionalRealization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfessionalRealizationGalleryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_upload_and_publicly_display_six_realizations(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $professional = User::factory()->create([
            'account_type' => 'professionnel',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'profile_public' => true,
        ]);

        $photos = collect(range(1, 6))
            ->map(fn (int $index) => $this->photo("chantier-{$index}.png"))
            ->all();

        $this->actingAs($professional)
            ->put(route('profile.update'), [
                'name' => $professional->name,
                'email' => $professional->email,
                'professional_realization_photos' => $photos,
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('professional_realizations', 6);
        $professional->refresh()->load('professionalRealizations');
        foreach ($professional->professionalRealizations as $realization) {
            Storage::disk('public')->assertExists($realization->photo_path);
        }

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee(route('profile.edit').'#profile-realizations', false);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('id="profile-realizations"', false);

        $this->get(route('profile.public', $professional))
            ->assertOk()
            ->assertSee('Réalisations professionnelles')
            ->assertSee('6 exemples de travaux publiés par ce prestataire.')
            ->assertSee('professionalRealizationModal', false)
            ->assertSee(storage_url($professional->professionalRealizations->first()->photo_path), false);
    }

    public function test_professional_gallery_editor_accumulates_multiple_file_selections(): void
    {
        $professional = User::factory()->create([
            'account_type' => 'professionnel',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        $this->actingAs($professional)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('name="professional_realization_photos[]"', false)
            ->assertSee('multiple', false)
            ->assertSee('let professionalGalleryFiles = [];', false)
            ->assertSee('professionalGalleryFiles.push(file);', false)
            ->assertSee('syncProfessionalGalleryInput();', false)
            ->assertSee('Retirer cette photo', false);
    }

    public function test_photo_captions_are_saved_per_image_and_can_be_updated_only_by_the_owner(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        $owner = User::factory()->create(['user_type' => 'professionnel', 'profile_public' => true]);
        $this->actingAs($owner)->put(route('profile.update'), [
            'name' => $owner->name, 'email' => $owner->email,
            'professional_realization_photos' => [$this->photo('one.png'), $this->photo('two.png')],
            'professional_realization_captions' => ['Salle de bain rénovée', 'Pose de carrelage'],
        ])->assertSessionHasNoErrors();
        $photos = $owner->professionalRealizations()->get();
        $this->assertCount(2, $photos);
        $this->assertSame('Salle de bain rénovée', $photos[0]->caption);
        $this->assertSame('Pose de carrelage', $photos[1]->caption);
        $this->get(route('profile.public', $owner))->assertOk()->assertSee('Salle de bain rénovée')->assertSee('Pose de carrelage');

        $other = User::factory()->create(['user_type' => 'professionnel']);
        $this->actingAs($other)->put(route('profile.update'), [
            'name' => $other->name, 'email' => $other->email,
            'realization_captions' => [$photos[0]->id => 'Modification interdite'],
        ])->assertSessionHasErrors('realization_captions');
        $this->assertSame('Salle de bain rénovée', $photos[0]->fresh()->caption);

        $this->actingAs($owner)->put(route('profile.update'), [
            'name' => $owner->name, 'email' => $owner->email,
            'realization_captions' => [$photos[0]->id => '<script>alert(1)</script>'],
        ])->assertSessionHasNoErrors();
        $this->get(route('profile.public', $owner))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_caption_length_is_validated_before_saving_photos(): void
    {
        $owner = User::factory()->create(['user_type' => 'professionnel']);
        $this->actingAs($owner)->put(route('profile.update'), [
            'name' => $owner->name, 'email' => $owner->email,
            'professional_realization_photos' => [$this->photo('one.png')],
            'professional_realization_captions' => [str_repeat('a', 141)],
        ])->assertSessionHasErrors('professional_realization_captions.0');
        $this->assertDatabaseCount('professional_realizations', 0);
    }

    public function test_gallery_rejects_a_seventh_photo_and_non_provider_uploads(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $professional = User::factory()->create([
            'account_type' => 'professionnel',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        foreach (range(1, 6) as $position) {
            ProfessionalRealization::create([
                'user_id' => $professional->id,
                'photo_path' => "professional-realizations/{$professional->id}/existing-{$position}.png",
                'position' => $position,
            ]);
        }

        $this->actingAs($professional)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => $professional->name,
                'email' => $professional->email,
                'professional_realization_photos' => [$this->photo('seventh.png')],
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('professional_realization_photos');

        $this->assertDatabaseCount('professional_realizations', 6);

        $client = User::factory()->create();
        $this->actingAs($client)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => $client->name,
                'email' => $client->email,
                'professional_realization_photos' => [$this->photo('client.png')],
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('professional_realization_photos');
    }

    public function test_owner_can_delete_a_realization_but_another_user_cannot(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $owner = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $other = User::factory()->create();
        $path = "professional-realizations/{$owner->id}/work.png";
        Storage::disk('public')->put($path, 'photo');
        $realization = ProfessionalRealization::create([
            'user_id' => $owner->id,
            'photo_path' => $path,
            'position' => 1,
        ]);

        $this->actingAs($other)
            ->delete(route('profile.realizations.destroy', $realization))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('profile.realizations.destroy', $realization))
            ->assertRedirect();

        $this->assertDatabaseMissing('professional_realizations', ['id' => $realization->id]);
        Storage::disk('public')->assertMissing($path);
    }

    private function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::ONE_PIXEL_PNG));
    }

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
}
