<?php

namespace Tests\Feature\Site;

use App\Models\Ad;
use App\Models\User;
use App\Notifications\BoostExpiringNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tâches automatiques en production : le démarrage Railway lance bien le
 * planificateur, la purge définitive des comptes reste désactivée par défaut,
 * et l'alerte d'expiration n'utilise aucun opérateur JSON SQL (la colonne
 * notifications.data est du texte : PostgreSQL le refuse, SQLite l'accepte).
 */
class ProductionSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_railway_start_command_runs_migrations_scheduler_and_web_server(): void
    {
        $config = file_get_contents(base_path('railway.toml'));

        $this->assertSame(1, preg_match('/^startCommand = "(.+)"$/m', $config, $match), 'railway.toml doit définir startCommand.');
        $command = $match[1];
        $this->assertStringStartsWith('php artisan migrate --force && ', $command);
        $this->assertStringContainsString('php artisan schedule:work', $command);
        $this->assertStringContainsString('php artisan serve --host=0.0.0.0', $command);
        // Le planificateur tourne en tâche de fond : le serveur web vient après lui.
        $this->assertLessThan(strpos($command, 'php artisan serve'), strpos($command, 'schedule:work'));
    }

    public function test_account_purge_is_scheduled_but_disabled_by_default(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $purge = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'cleanup:orphaned-data'));

        $this->assertNotNull($purge, 'La purge doit rester déclarée dans routes/console.php.');

        config(['marketplace.purge_deleted_accounts' => false]);
        $this->assertFalse($purge->filtersPass($this->app));

        config(['marketplace.purge_deleted_accounts' => true]);
        $this->assertTrue($purge->filtersPass($this->app));
    }

    public function test_draft_reminders_run_every_quarter_hour_but_never_at_night_in_mayotte(): void
    {
        // Laravel calcule la plage horaire au chargement de routes/console.php, comme le
        // fait chaque minute `schedule:work` en production : on le recharge à chaque heure testée.
        $eventAt = function (string $utc) {
            $this->travelTo(\Illuminate\Support\Carbon::parse("2026-11-12 {$utc}:00", 'UTC'));
            $this->app->instance(Schedule::class, new Schedule());
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            require base_path('routes/console.php');

            return collect(app(Schedule::class)->events())
                ->first(fn ($event) => str_contains((string) $event->command, 'demand-drafts:send-reminders'));
        };

        $this->assertSame('*/15 * * * *', $eventAt('10:00')->expression);

        // 04:59 UTC = 07:59 à Mayotte ; 05:00 UTC = 08:00 ; 17:00 UTC = 20:00 ; 17:15 UTC = 20:15.
        foreach (['04:59' => false, '05:00' => true, '10:00' => true, '17:00' => true, '17:15' => false, '23:00' => false] as $utc => $allowed) {
            $this->assertSame($allowed, $eventAt($utc)->filtersPass($this->app), "Passage de {$utc} UTC.");
        }
        $this->travelBack();
    }

    public function test_expiring_alert_is_sent_once_per_day_without_sql_json_operators(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::create(['user_id' => $owner->id, 'title' => 'Plomberie', 'description' => 'Dépannage.',
            'category' => 'Plombier', 'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public',
            'is_boosted' => true, 'boost_end' => now()->addHours(20),
            'is_urgent' => true, 'urgent_until' => now()->addHours(30)]);

        DB::enableQueryLog();
        $this->artisan('boosts:send-expiring-alerts')->assertSuccessful();
        $this->artisan('boosts:send-expiring-alerts')->assertSuccessful();
        $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();

        $this->assertStringNotContainsStringIgnoringCase('json', $queries);
        $this->assertStringNotContainsString('->', $queries);

        $alerts = $owner->notifications()->where('type', BoostExpiringNotification::class)->get();
        $this->assertCount(2, $alerts, 'Une alerte pour le boost et une pour l’option Urgent, pas de doublon au second passage.');
        $this->assertEqualsCanonicalizing(['boost', 'urgent'], $alerts->pluck('data.type')->all());
        $this->assertSame([$ad->id, $ad->id], $alerts->pluck('data.ad_id')->map(fn ($id) => (int) $id)->all());
    }
}
