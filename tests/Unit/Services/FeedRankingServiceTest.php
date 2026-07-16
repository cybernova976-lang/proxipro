<?php

namespace Tests\Unit\Services;

use App\Models\Ad;
use App\Services\FeedRankingService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeedRankingServiceTest extends TestCase
{
    public function test_paid_placements_cannot_fill_more_than_one_third_when_organic_ads_exist(): void
    {
        Carbon::setTestNow('2026-07-16 12:00:00');

        $ads = collect();
        foreach (range(1, 12) as $id) {
            $ads->push($this->ad($id, $id, true));
        }
        foreach (range(13, 30) as $id) {
            $ads->push($this->ad($id, $id, false));
        }

        $ranked = (new FeedRankingService)->rank($ads, null, 18);
        $priorityCount = $ranked->filter(fn (Ad $ad) => $ad->is_urgent || $ad->is_boosted)->count();

        $this->assertCount(18, $ranked);
        $this->assertSame(6, $priorityCount);
        $this->assertTrue((bool) $ranked->first()->is_urgent);

        Carbon::setTestNow();
    }

    public function test_first_page_limits_each_publisher_when_enough_alternatives_exist(): void
    {
        Carbon::setTestNow('2026-07-16 12:00:00');

        $ads = collect(range(1, 24))->map(
            fn (int $id) => $this->ad($id, (int) ceil($id / 3), false)
        );

        $ranked = (new FeedRankingService)->rank($ads, null, 12);

        foreach ($ranked->countBy('user_id') as $authorCount) {
            $this->assertLessThanOrEqual(2, $authorCount);
        }

        Carbon::setTestNow();
    }

    private function ad(int $id, int $userId, bool $priority): Ad
    {
        $ad = (new Ad)->forceFill([
            'id' => $id,
            'user_id' => $userId,
            'title' => 'Annonce '.$id,
            'service_type' => 'demande',
            'is_urgent' => $priority,
            'urgent_until' => $priority ? now()->addDay() : null,
            'is_boosted' => false,
            'created_at' => now()->subMinutes($id),
        ]);

        $ad->setRelation('user', null);

        return $ad;
    }
}
