<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\User;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdPhotoGalleryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MarketplaceCategoryRegistry::storeEnabledIds(array_keys(MarketplaceCategoryRegistry::definitions()));
    }

    public function test_multiple_photos_use_the_responsive_gallery_without_the_old_search_badge(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Cherche aide personne âgée',
            'description' => 'Une demande avec plusieurs photos.',
            'category' => 'Aide aux personnes âgées',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'photos' => [
                'ads/photo-1.webp',
                'ads/photo-2.webp',
                'ads/photo-3.webp',
                'ads/photo-4.webp',
            ],
            'user_id' => $owner->id,
        ]);

        $response = $this->get(route('ads.show', $ad));

        $response->assertOk()
            ->assertSee('ad-photo-gallery--multiple', false)
            ->assertSee('ad-photo--main', false)
            ->assertSee('4 photos')
            ->assertSee('openLightbox(0)', false)
            ->assertSee('id="lightbox-image"', false)
            ->assertSee('max-width: 90%; max-height: 90%; object-fit: contain;', false)
            ->assertDontSee('<span class="badge-type', false);
    }

    public function test_two_photos_are_presented_side_by_side_with_a_discreet_counter(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::create([
            'title' => 'Besoin de travaux de peinture',
            'description' => 'Une demande avec deux photos.',
            'category' => 'Peintre',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'photos' => [
                'ads/photo-1.webp',
                'ads/photo-2.webp',
            ],
            'user_id' => $owner->id,
        ]);

        $this->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('ad-photo-gallery--double', false)
            ->assertSee('2 photos')
            ->assertDontSee('<span class="badge-type', false);
    }
}
