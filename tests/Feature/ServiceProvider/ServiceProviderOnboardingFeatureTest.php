<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceProviderOnboardingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_onboarding_updates_profile_services_without_creating_an_ad(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $this->actingAs($user);

        $profileResponse = $this->post(route('service-provider.update-profile-fields'), [
            'name' => 'Amina Services',
            'phone' => '+262639000000',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'address' => 'Rue du Commerce',
            'bio' => 'Services a domicile pour particuliers.',
            'avatar' => $this->fakeAvatarUpload(),
        ], ['Accept' => 'application/json']);

        $profileResponse->assertOk()->assertJson(['success' => true]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);

        $registerResponse = $this->postJson(route('service-provider.register'), [
            'enforce_profile_completion' => true,
            'services' => [
                [
                    'main_category' => 'Maison & entretien',
                    'subcategory' => 'Menage',
                    'experience_years' => 2,
                    'description' => 'Menage regulier et ponctuel.',
                ],
            ],
        ]);

        $registerResponse->assertOk()
            ->assertJson([
                'success' => true,
                'is_service_provider' => true,
                'has_subscription' => false,
            ]);

        $user->refresh();

        $this->assertTrue($user->is_service_provider);
        $this->assertTrue($user->profile_completed);
        $this->assertTrue($user->pro_onboarding_completed);
        $this->assertSame('Maison & entretien', $user->service_category);
        $this->assertSame(['Menage'], $user->service_subcategories);
        $this->assertSame(['Maison & entretien'], $user->pro_service_categories);
        $this->assertSame('Menage', $user->profession);

        $this->assertDatabaseHas('user_services', [
            'user_id' => $user->id,
            'main_category' => 'Maison & entretien',
            'subcategory' => 'Menage',
            'is_active' => true,
        ]);

        $this->assertSame(1, UserService::where('user_id', $user->id)->count());
        $this->assertSame(0, Ad::where('user_id', $user->id)->count());
    }

    public function test_provider_registration_can_require_completed_profile(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'city' => null,
            'country' => null,
            'avatar' => null,
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('service-provider.register'), [
            'enforce_profile_completion' => true,
            'services' => [
                [
                    'main_category' => 'Maison & entretien',
                    'subcategory' => 'Menage',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertContains('photo de profil', $response->json('missing_profile_fields'));

        $this->assertFalse($user->fresh()->is_service_provider);
    }

    private function fakeAvatarUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar_');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, 'avatar.png', 'image/png', null, true);
    }
}
