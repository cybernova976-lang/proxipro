<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiration_command_marks_only_due_active_ads_as_expired(): void
    {
        $owner = User::factory()->create();
        $due = $this->ad($owner, ['expires_at' => now()->subMinute()]);
        $future = $this->ad($owner, [
            'title' => 'Annonce encore valable',
            'expires_at' => now()->addDay(),
        ]);
        $archived = $this->ad($owner, [
            'title' => 'Annonce déjà archivée',
            'status' => 'inactive',
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('ads:expire')
            ->expectsOutput('1 annonce(s) expirée(s).')
            ->assertExitCode(0);

        $this->assertSame('expired', $due->fresh()->status);
        $this->assertSame('active', $future->fresh()->status);
        $this->assertSame('inactive', $archived->fresh()->status);
    }

    public function test_owner_can_archive_then_republish_an_ad_with_a_new_duration(): void
    {
        $owner = User::factory()->create();
        $ad = $this->ad($owner, ['expires_at' => now()->addDay()]);

        $this->actingAs($owner)
            ->patch(route('ads.archive', $ad))
            ->assertRedirect();

        $this->assertSame('inactive', $ad->fresh()->status);

        $this->actingAs($owner)
            ->post(route('ads.republish', $ad))
            ->assertRedirect(route('ads.show', $ad));

        $ad->refresh();
        $this->assertSame('active', $ad->status);
        $this->assertTrue($ad->expires_at->between(now()->addDays(29), now()->addDays(31)));
    }

    public function test_another_user_cannot_archive_or_republish_an_ad(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ad = $this->ad($owner, [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($other)->patch(route('ads.archive', $ad))->assertForbidden();
        $this->actingAs($other)->post(route('ads.republish', $ad))->assertForbidden();
    }

    public function test_expired_ad_is_private_to_its_owner_and_cannot_receive_a_proposal(): void
    {
        $owner = User::factory()->create();
        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $ad = $this->ad($owner, ['expires_at' => now()->subMinute()]);

        $this->get(route('ads.show', $ad))->assertNotFound();
        $this->actingAs($owner)->get(route('ads.show', $ad))->assertOk();

        $this->actingAs($provider)
            ->post(route('proposals.store', $ad), [
                'amount' => 80,
                'message' => 'Je peux intervenir rapidement avec le matériel nécessaire.',
            ])
            ->assertStatus(422);
    }

    public function test_expired_ad_redirects_a_signed_in_visitor_to_the_feed(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $ad = $this->ad($owner, ['expires_at' => now()->subMinute()]);

        $this->actingAs($visitor)
            ->get(route('ads.show', $ad))
            ->assertRedirect(route('feed'))
            ->assertSessionHas('info');
    }

    public function test_expired_ad_returns_not_found_for_a_guest(): void
    {
        $owner = User::factory()->create();
        $ad = $this->ad($owner, ['expires_at' => now()->subMinute()]);

        $this->get(route('ads.show', $ad))->assertNotFound();
    }

    public function test_deleted_ad_redirects_a_signed_in_visitor_to_the_feed(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $ad = $this->ad($owner);
        $url = route('ads.show', $ad);
        $ad->delete();

        $this->actingAs($visitor)
            ->get($url)
            ->assertRedirect(route('feed'))
            ->assertSessionHas('info', 'Cette annonce n’existe plus.');
    }

    public function test_deleted_ad_returns_not_found_for_a_guest(): void
    {
        $owner = User::factory()->create();
        $ad = $this->ad($owner);
        $url = route('ads.show', $ad);
        $ad->delete();

        $this->get($url)->assertNotFound();
    }

    public function test_role_aliases_filter_the_full_announcements_page(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $this->ad($author, ['title' => 'Demande uniquement visible côté missions']);
        $this->ad($author, [
            'title' => 'Offre uniquement visible côté services',
            'service_type' => 'offre',
            'expires_at' => now()->addDays(90),
        ]);

        $this->actingAs($viewer)
            ->get(route('ads.index', ['type' => 'offres']))
            ->assertOk()
            ->assertSee('Offre uniquement visible côté services')
            ->assertDontSee('Demande uniquement visible côté missions');

        $this->actingAs($viewer)
            ->get(route('ads.index', ['type' => 'demandes']))
            ->assertOk()
            ->assertSee('Demande uniquement visible côté missions')
            ->assertDontSee('Offre uniquement visible côté services');
    }

    private function ad(User $owner, array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'title' => 'Demande de plomberie à traiter',
            'description' => 'Description suffisamment précise pour représenter une annonce réelle.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'publication_domain' => 'services',
            'location' => 'Mamoudzou',
            'country' => 'Mayotte',
            'price_type' => 'negotiable',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $owner->id,
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }
}
