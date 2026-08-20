<?php

namespace Tests\Feature\Analytics;

use App\Models\UsageDailyMetric;
use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsageAnalyticsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_USER_AGENT = 'Mozilla/5.0 (Linux; Android 14; SM-S928B) '
        .'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0 Mobile Safari/537.36';

    public function test_page_views_and_daily_sessions_are_aggregated_without_sensitive_dimensions(): void
    {
        $payload = [
            'event_name' => 'page_view',
            'route_name' => 'homepage',
            'app_mode' => 'pwa',
        ];

        $this->withHeader('User-Agent', self::MOBILE_USER_AGENT)
            ->postJson(route('usage.store'), $payload)
            ->assertNoContent();

        $this->withHeader('User-Agent', self::MOBILE_USER_AGENT)
            ->postJson(route('usage.store'), $payload)
            ->assertNoContent();

        $this->assertDatabaseHas('usage_daily_metrics', [
            'metric_date' => today()->toDateString(),
            'event_name' => 'page_view',
            'route_name' => 'homepage',
            'device_type' => 'mobile',
            'app_mode' => 'pwa',
            'count' => 2,
        ]);

        $this->assertDatabaseHas('usage_daily_metrics', [
            'event_name' => 'session_start',
            'device_type' => 'mobile',
            'app_mode' => 'pwa',
            'count' => 1,
        ]);

        foreach (['user_id', 'ip_address', 'user_agent', 'session_id', 'query', 'metadata'] as $column) {
            $this->assertFalse(Schema::hasColumn('usage_daily_metrics', $column));
        }
    }

    public function test_do_not_track_requests_and_admin_route_names_are_not_profiled(): void
    {
        $this->withHeader('DNT', '1')
            ->postJson(route('usage.store'), [
                'event_name' => 'page_view',
                'route_name' => 'homepage',
                'app_mode' => 'browser',
            ])
            ->assertNoContent();

        $this->assertDatabaseCount('usage_daily_metrics', 0);

        $this->withHeader('DNT', '0')
            ->postJson(route('usage.store'), [
                'event_name' => 'page_view',
                'route_name' => 'admin.users',
                'app_mode' => 'browser',
            ])->assertNoContent();

        $this->assertDatabaseHas('usage_daily_metrics', [
            'event_name' => 'page_view',
            'route_name' => 'other',
            'count' => 1,
        ]);
    }

    public function test_admin_can_read_usage_dashboard_but_regular_user_cannot(): void
    {
        $analytics = app(UsageAnalytics::class);
        $analytics->record('page_view', 'homepage', 'mobile', 'pwa');
        $analytics->record('page_view', 'homepage', 'mobile', 'pwa');
        $analytics->record('pwa_install', 'homepage', 'mobile', 'pwa');

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admin.usage', ['period' => 7]));

        $response->assertOk()
            ->assertSee('Utilisation réelle')
            ->assertSee('Pages vues')
            ->assertSee('Accueil public')
            ->assertSee('Installations détectées')
            ->assertSee('Aucune donnée sensible collectée');

        $regularUser = User::factory()->create(['role' => 'user']);
        $this->actingAs($regularUser)
            ->get(route('admin.usage'))
            ->assertForbidden();
    }

    public function test_expired_aggregates_are_pruned_after_twenty_five_months(): void
    {
        $analytics = app(UsageAnalytics::class);
        $analytics->record('page_view', 'homepage', 'desktop', 'browser', today()->subMonthsNoOverflow(26));
        $analytics->record('page_view', 'homepage', 'desktop', 'browser', today());

        $this->artisan('usage:prune --months=25')->assertSuccessful();

        $this->assertSame(1, UsageDailyMetric::query()->count());
        $this->assertTrue(UsageDailyMetric::query()->first()->metric_date->isToday());
    }

    public function test_public_pages_expose_measurement_information_and_device_opt_out(): void
    {
        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSee('Mesure d’audience sur cet appareil')
            ->assertSee('Désactiver la mesure')
            ->assertSee('prokejem_usage_disabled', false)
            ->assertSee('25 mois');

        $homepage = $this->get(route('homepage'));
        $homepage->assertOk()
            ->assertSee("track('page_view')", false);
        $this->assertStringContainsString(
            json_encode(route('usage.store'), JSON_THROW_ON_ERROR),
            $homepage->getContent(),
        );
    }
}
