<?php

namespace Tests\Feature\Analytics;

use App\Models\Ad;
use App\Models\ServiceProposal;
use App\Models\UsageDailyMetric;
use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function test_demand_journey_events_are_aggregated_by_step_audience_and_device_only(): void
    {
        $payload = [
            'event_name' => 'demand_step_view',
            'step' => 2,
            'route_name' => 'a-client-value-that-must-be-ignored',
            'app_mode' => 'browser',
            'description' => 'Ce texte ne doit jamais être enregistré.',
        ];

        $this->withHeader('User-Agent', self::MOBILE_USER_AGENT)
            ->postJson(route('usage.store'), $payload)
            ->assertNoContent();

        $member = User::factory()->create();
        $this->actingAs($member)
            ->postJson(route('usage.store'), [
                'event_name' => 'demand_validation_error',
                'step' => 2,
                'route_name' => 'admin.users',
                'app_mode' => 'pwa',
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('usage_daily_metrics', [
            'event_name' => 'demand_step_view',
            'route_name' => 'demand.step.2.guest',
            'device_type' => 'mobile',
            'app_mode' => 'browser',
            'count' => 1,
        ]);
        $this->assertDatabaseHas('usage_daily_metrics', [
            'event_name' => 'demand_validation_error',
            'route_name' => 'demand.step.2.member',
            'app_mode' => 'pwa',
            'count' => 1,
        ]);
        $this->assertDatabaseMissing('usage_daily_metrics', [
            'route_name' => 'a-client-value-that-must-be-ignored',
        ]);
    }

    public function test_journey_event_without_a_valid_step_is_ignored(): void
    {
        $this->postJson(route('usage.store'), [
            'event_name' => 'demand_step_view',
            'app_mode' => 'browser',
        ])->assertNoContent();

        $this->assertDatabaseCount('usage_daily_metrics', 0);
    }

    public function test_admin_dashboard_reports_each_demand_step_without_claiming_unique_abandonment(): void
    {
        $analytics = app(UsageAnalytics::class);
        $analytics->record('demand_step_view', 'demand.step.1.guest', 'mobile');
        $analytics->record('demand_step_view', 'demand.step.1.member', 'desktop');
        $analytics->record('demand_step_view', 'demand.step.2.guest', 'mobile');
        $analytics->record('demand_validation_error', 'demand.step.2.guest', 'mobile');
        $analytics->record('demand_auth_redirect', 'demand.step.5.guest', 'mobile');
        $analytics->record('demand_draft_resumed', 'demand.step.5.member', 'desktop');

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admin.usage', ['period' => 7]));

        $response->assertOk()
            ->assertSee('Passage étape par étape')
            ->assertSee('pas un utilisateur unique abandonné')
            ->assertSee('passages vers la connexion')
            ->assertSee('brouillons repris');

        $journey = $response->viewData('demandJourney');
        $this->assertSame(2, $journey[0]['views']);
        $this->assertSame(1, $journey[0]['guest_views']);
        $this->assertSame(1, $journey[0]['member_views']);
        $this->assertEquals(50, $journey[0]['next_rate']);
        $this->assertSame(1, $journey[1]['errors']);
        $this->assertSame(1, $response->viewData('summary')['demand_auth_redirects']);
        $this->assertSame(1, $response->viewData('summary')['demand_draft_resumes']);
    }

    public function test_admin_dashboard_measures_the_demand_funnel_and_first_proposal_delay(): void
    {
        DB::table('usage_daily_metrics')->insert([
            'metric_date' => today()->toDateString(),
            'event_name' => 'page_view',
            'route_name' => 'demand.create',
            'device_type' => 'desktop',
            'app_mode' => 'browser',
            'count' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseHas('usage_daily_metrics', [
            'metric_date' => today()->toDateString(),
            'event_name' => 'page_view',
            'route_name' => 'demand.create',
            'count' => 4,
        ]);

        $client = User::factory()->create();
        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);
        $publishedAt = now()->subHours(2)->startOfMinute();
        $ad = Ad::create([
            'title' => 'Demande avec première proposition mesurée',
            'description' => 'Une demande utilisée pour vérifier les indicateurs du tunnel.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'user_id' => $client->id,
        ]);
        $ad->forceFill(['created_at' => $publishedAt, 'updated_at' => $publishedAt])->saveQuietly();

        $proposal = ServiceProposal::create([
            'ad_id' => $ad->id,
            'provider_id' => $provider->id,
            'amount' => 90,
            'message' => 'Je suis disponible et peux intervenir rapidement pour cette mission.',
            'status' => ServiceProposal::STATUS_PENDING,
        ]);
        $firstProposalAt = $publishedAt->copy()->addMinutes(42);
        $proposal->forceFill(['created_at' => $firstProposalAt, 'updated_at' => $firstProposalAt])->saveQuietly();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.usage', ['period' => 7]));

        $response->assertOk()
            ->assertSee('Tunnel de publication des demandes');

        $summary = $response->viewData('summary');
        $this->assertSame(4, $summary['demand_starts']);
        $this->assertSame(1, $summary['demand_publications']);
        $this->assertEquals(25, $summary['demand_completion_rate']);
        $this->assertSame(1, $summary['demands_with_proposal']);
        $this->assertEquals(100, $summary['demand_proposal_rate']);
        $this->assertSame(42, $summary['median_first_proposal_minutes']);
        $response->assertSee('42 min');
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

    public function test_a_new_measurement_applies_retention_without_the_scheduler(): void
    {
        UsageDailyMetric::query()->create([
            'metric_date' => today()->subMonthsNoOverflow(26),
            'event_name' => 'page_view',
            'route_name' => 'homepage',
            'device_type' => 'desktop',
            'app_mode' => 'browser',
            'count' => 1,
        ]);
        Cache::forget('usage_analytics.retention:'.today()->toDateString());

        app(UsageAnalytics::class)->record('page_view', 'homepage', 'desktop', 'browser');

        $this->assertDatabaseMissing('usage_daily_metrics', [
            'metric_date' => today()->subMonthsNoOverflow(26)->toDateString(),
        ]);
        $this->assertDatabaseHas('usage_daily_metrics', [
            'metric_date' => today()->toDateString(),
            'event_name' => 'page_view',
            'count' => 1,
        ]);
    }

    public function test_public_pages_expose_measurement_information_and_device_opt_out(): void
    {
        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSee('Mesure d’audience sur cet appareil')
            ->assertSee('Désactiver la mesure')
            ->assertSee('prokejem_usage_disabled', false)
            ->assertSee('étape du formulaire de demande')
            ->assertSee('ni réponse saisie dans un formulaire')
            ->assertSee('25 mois');

        $homepage = $this->get(route('homepage'));
        $homepage->assertOk()
            ->assertSee("track('page_view')", false);
        $this->assertStringContainsString(
            json_encode(route('usage.store'), JSON_THROW_ON_ERROR),
            $homepage->getContent(),
        );

        $demandForm = $this->get(route('demand.create'));
        $demandForm->assertOk()
            ->assertSee("trackDemandEvent('demand_validation_error'", false)
            ->assertSee("trackDemandEvent('demand_auth_redirect'", false)
            ->assertSee('trackDemandStepView(currentStep)', false);
    }
}
