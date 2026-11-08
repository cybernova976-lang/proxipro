<?php

namespace Tests\Feature\Analytics;

use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mesure du parcours guidé : étapes 1 à 9 sous un nom d'événement distinct,
 * pour que le tableau de l'ancien formulaire (5 étapes) reste juste.
 */
class UsageAnalyticsStepTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_USER_AGENT = 'Mozilla/5.0 (Linux; Android 14; SM-S928B) '
        .'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0 Mobile Safari/537.36';

    public function test_guided_step_seven_is_recorded_under_its_own_prefix(): void
    {
        $this->actingAs(User::factory()->create())
            ->withHeader('User-Agent', self::MOBILE_USER_AGENT)
            ->postJson(route('usage.store'), [
                'event_name' => 'demand_guided_step_view',
                'route_name' => 'demand.create',
                'app_mode' => 'browser',
                'step' => 7,
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('usage_daily_metrics', [
            'event_name' => 'demand_guided_step_view',
            'route_name' => 'demand.guided.step.7.member',
            'count' => 1,
        ]);
    }

    public function test_step_ten_is_rejected(): void
    {
        $this->postJson(route('usage.store'), [
            'event_name' => 'demand_guided_step_view',
            'app_mode' => 'browser',
            'step' => 10,
        ])->assertStatus(422)->assertJsonValidationErrors('step');

        $this->assertDatabaseCount('usage_daily_metrics', 0);
    }

    public function test_free_form_step_six_is_ignored_to_keep_its_dashboard_right(): void
    {
        $this->withHeader('User-Agent', self::MOBILE_USER_AGENT)
            ->postJson(route('usage.store'), [
                'event_name' => 'demand_step_view',
                'app_mode' => 'browser',
                'step' => 6,
            ])
            ->assertNoContent();

        $this->assertDatabaseCount('usage_daily_metrics', 0);
    }

    public function test_unknown_event_is_rejected(): void
    {
        $this->postJson(route('usage.store'), [
            'event_name' => 'demand_text_typed',
            'app_mode' => 'browser',
        ])->assertStatus(422)->assertJsonValidationErrors('event_name');
    }

    public function test_server_only_events_cannot_be_sent_by_the_browser(): void
    {
        foreach (UsageAnalytics::SERVER_EVENTS as $event) {
            $this->postJson(route('usage.store'), [
                'event_name' => $event,
                'route_name' => 'demand.store',
                'app_mode' => 'browser',
            ])->assertStatus(422);
        }

        $this->assertDatabaseCount('usage_daily_metrics', 0);
    }

    public function test_guided_events_are_accepted_from_the_page_and_fit_the_column(): void
    {
        foreach (['demand_answer_unknown', 'demand_photo_added', 'demand_photo_rejected', 'demand_tts_used', 'demand_detail_added', 'demand_share_click'] as $event) {
            $this->withHeader('User-Agent', self::MOBILE_USER_AGENT)
                ->postJson(route('usage.store'), [
                    'event_name' => $event,
                    'route_name' => 'demand.create',
                    'app_mode' => 'browser',
                ])
                ->assertNoContent();

            $this->assertDatabaseHas('usage_daily_metrics', ['event_name' => $event, 'route_name' => 'demand.create']);
        }

        foreach (UsageAnalytics::EVENTS as $event) {
            $this->assertLessThanOrEqual(40, strlen($event), $event);
        }
    }

    public function test_admin_dashboard_lists_the_guided_counters_without_percentages(): void
    {
        $analytics = app(UsageAnalytics::class);
        $analytics->record('demand_guided_step_view', 'demand.guided.step.1.guest', 'mobile');
        $analytics->record('demand_published_guided', 'demand.store', 'mobile');
        $analytics->record('demand_published_guided', 'demand.store', 'mobile');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.usage'))
            ->assertOk()
            ->assertSee('Parcours guidé : étapes vues')
            ->assertSee('Demandes guidées publiées')
            ->assertSee('Doublons redirigés')
            ->assertViewHas('guidedJourney', fn ($rows) => $rows->firstWhere('event', 'demand_published_guided')['count'] === 2
                && $rows->firstWhere('event', 'demand_guided_step_view')['count'] === 1);
    }
}
