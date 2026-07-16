<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSharingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_exposes_one_complete_social_preview_and_share_dialog(): void
    {
        $user = User::factory()->create([
            'name' => 'Amina Services',
            'profession' => 'Électricienne',
            'bio' => 'Interventions électriques rapides pour les particuliers et les entreprises.',
            'avatar' => 'avatars/amina-profile.jpg',
            'city' => 'Lyon',
            'country' => 'France',
            'profile_public' => true,
        ]);

        $response = $this->get(route('profile.public', $user));

        $response->assertOk()
            ->assertSee('id="sharePublicProfileBtn"', false)
            ->assertSee('id="publicProfileShareModal"', false)
            ->assertSee('Partager avec mon appareil')
            ->assertSee('data-share-platform="whatsapp"', false)
            ->assertSee('data-share-platform="facebook"', false)
            ->assertSee('data-share-platform="linkedin"', false)
            ->assertSee('navigator.share(shareData)', false)
            ->assertSee("document.execCommand('copy')", false)
            ->assertSee('avatars/amina-profile.jpg', false);

        $html = $response->getContent();
        $this->assertStringContainsString(json_encode(route('profile.share.record', $user)), $html);
        $this->assertSame(1, substr_count($html, 'property="og:title"'));
        $this->assertSame(1, substr_count($html, 'property="og:image"'));
        $this->assertSame(1, substr_count($html, 'name="twitter:card"'));
        $this->assertStringContainsString('Amina Services — Électricienne | ProxiPro', $html);
    }

    public function test_a_session_only_counts_once_when_it_shares_the_same_profile_multiple_times(): void
    {
        $user = User::factory()->create([
            'profile_public' => true,
            'profile_shares' => 0,
        ]);

        $this->postJson(route('profile.share.record', $user), ['platform' => 'copy'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'already_shared' => false,
                'profile_shares' => 1,
            ]);

        $this->postJson(route('profile.share.record', $user), ['platform' => 'whatsapp'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'already_shared' => true,
                'profile_shares' => 1,
            ]);

        $this->assertSame(1, $user->fresh()->profile_shares);
    }

    public function test_share_tracking_rejects_unknown_platforms_and_private_profiles(): void
    {
        $publicUser = User::factory()->create(['profile_public' => true]);
        $privateUser = User::factory()->create(['profile_public' => false]);

        $this->postJson(route('profile.share.record', $publicUser), ['platform' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('platform');

        $this->postJson(route('profile.share.record', $privateUser), ['platform' => 'copy'])
            ->assertNotFound();

        $this->actingAs($privateUser)
            ->get(route('profile.public', $privateUser))
            ->assertOk()
            ->assertDontSee('id="sharePublicProfileBtn"', false)
            ->assertSee('Rendez votre profil public dans les paramètres avant de le partager.');
    }

    public function test_professional_dashboard_uses_the_same_share_component(): void
    {
        $professional = User::factory()->create([
            'user_type' => 'professionnel',
            'profile_public' => true,
        ]);

        $this->actingAs($professional)
            ->get(route('pro.profile'))
            ->assertOk()
            ->assertSee('id="shareProfileBtn"', false)
            ->assertSee('id="proProfileShareModal"', false)
            ->assertSee('data-profile-copy-link', false)
            ->assertSee(route('profile.public', $professional), false);
    }
}
