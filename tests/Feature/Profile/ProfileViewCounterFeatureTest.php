<?php

namespace Tests\Feature\Profile;

use App\Models\ProfileView;
use App\Models\User;
use App\Services\ProfileViewCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProfileViewCounterFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_views_are_deduplicated_per_visitor_and_day(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');

        try {
            $profile = User::factory()->create();
            $viewer = User::factory()->create();
            $request = $this->profileRequest('203.0.113.10');
            $counter = app(ProfileViewCounter::class);

            $this->assertTrue($counter->record($profile, $viewer, $request));
            $this->assertFalse($counter->record($profile, $viewer, $request));
            $this->assertSame(1, $counter->countThisMonth($profile));

            Carbon::setTestNow('2026-08-25 10:00:00');

            $this->assertTrue($counter->record($profile, $viewer, $request));
            $this->assertSame(2, $counter->countThisMonth($profile));
            $this->assertSame(2, $profile->fresh()->profile_views);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_own_profile_and_bot_visits_are_not_counted(): void
    {
        $profile = User::factory()->create();
        $counter = app(ProfileViewCounter::class);

        $this->assertFalse($counter->record($profile, $profile, $this->profileRequest('203.0.113.11')));
        $this->assertFalse($counter->record(
            $profile,
            null,
            $this->profileRequest('203.0.113.12', 'Googlebot/2.1')
        ));

        $this->assertDatabaseCount('profile_views', 0);
        $this->assertSame(0, $profile->fresh()->profile_views);
    }

    public function test_expired_profile_views_can_be_pruned(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');

        try {
            $profile = User::factory()->create();
            ProfileView::create([
                'profile_user_id' => $profile->id,
                'viewer_key' => 'a:expired',
                'viewed_on' => now()->subMonthsNoOverflow(14)->toDateString(),
            ]);
            ProfileView::create([
                'profile_user_id' => $profile->id,
                'viewer_key' => 'a:recent',
                'viewed_on' => now()->subMonth()->toDateString(),
            ]);

            $this->artisan('profile-views:prune', ['--months' => 13])
                ->expectsOutput('1 vue(s) de profil supprimee(s).')
                ->assertSuccessful();

            $this->assertDatabaseMissing('profile_views', ['viewer_key' => 'a:expired']);
            $this->assertDatabaseHas('profile_views', ['viewer_key' => 'a:recent']);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function profileRequest(string $ip, string $userAgent = 'Mozilla/5.0 ProkejemTest'): Request
    {
        return Request::create('/profil/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);
    }
}
