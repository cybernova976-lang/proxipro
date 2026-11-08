<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aperçu de partage (WhatsApp, Facebook) d'une annonce : la première photo
 * JPEG, PNG ou WebP d'une annonce publique et active ; sinon l'image du site.
 */
class AdShareMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_active_ad_shares_its_first_photo(): void
    {
        $ad = $this->ad(['photos' => ['ads/x.jpg', 'ads/y.png']]);

        $this->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.e(storage_url('ads/x.jpg')).'">', false)
            ->assertSee('<meta property="og:title" content="'.e($ad->title.' - Prokejem').'">', false);

        $this->assertStringStartsWith('http', storage_url('ads/x.jpg'));
    }

    public function test_ad_without_photo_keeps_the_site_card(): void
    {
        $ad = $this->ad(['photos' => null]);

        $this->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('images/social-card.png').'">', false);
    }

    public function test_photo_that_is_not_a_shareable_image_is_ignored(): void
    {
        $ad = $this->ad(['photos' => ['ads/plan.svg', 'ads/photo.webp']]);

        $this->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.e(storage_url('ads/photo.webp')).'">', false)
            ->assertDontSee(storage_url('ads/plan.svg').'">', false);
    }

    public function test_private_or_expired_ad_does_not_share_its_photo(): void
    {
        $private = $this->ad(['photos' => ['ads/prive.jpg'], 'visibility' => 'private']);
        $expired = $this->ad(['photos' => ['ads/expiree.jpg'], 'expires_at' => now()->subDay()]);

        foreach ([$private, $expired] as $ad) {
            // Le propriétaire voit encore son annonce, mais l'aperçu de partage n'expose pas la photo.
            $this->actingAs($ad->user)->get(route('ads.show', $ad))
                ->assertOk()
                ->assertDontSee('content="'.e(storage_url($ad->photos[0])).'"', false)
                ->assertSee('<meta property="og:image" content="'.asset('images/social-card.png').'">', false);
        }
    }

    private function ad(array $attributes = []): Ad
    {
        $owner = User::factory()->create(['is_active' => true]);

        return Ad::create(array_merge([
            'title' => 'Réparation d’une fuite à Mamoudzou',
            'description' => 'Une fuite sous l’évier.',
            'category' => 'Plombier',
            'main_category' => 'Bricolage & Travaux',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $owner->id,
            'expires_at' => now()->addDays(10),
        ], $attributes));
    }
}
