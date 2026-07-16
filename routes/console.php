<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {to?}', function () {
    $to = $this->argument('to') ?: config('mail.from.address');

    Mail::raw('Email de test depuis ProxiPro.', function ($message) use ($to) {
        $message->to($to)->subject('Email de test ProxiPro');
    });

    $this->info("Mail sent to {$to}");
})->purpose('Send a test email via SMTP');

// ─── Nettoyage automatique des utilisateurs soft-deleted ───
// Exécuté tous les jours à 3h du matin
Schedule::command('cleanup:orphaned-data --force')
    ->daily()
    ->at('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cleanup.log'))
    ->onSuccess(function () {
        \Log::info('Nettoyage automatique des utilisateurs supprimés terminé avec succès.');
    })
    ->onFailure(function () {
        \Log::error('Échec du nettoyage automatique des utilisateurs supprimés.');
    });

// ─── Nettoyage des boosts/urgents expirés ───
// Exécuté toutes les heures pour désactiver les boosts/urgents expirés
Schedule::command('boosts:cleanup')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/boosts-cleanup.log'));

// ─── Alertes d'expiration boost/urgent ───
// Exécuté toutes les 6 heures pour notifier les utilisateurs dont les boosts/urgents expirent bientôt
Schedule::command('boosts:send-expiring-alerts')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/boosts-alerts.log'));

// Remet à zéro les compteurs de points quotidiens avant une nouvelle journée.
Schedule::command('points:daily-reset')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/points-daily-reset.log'));

// Retire chaque nuit les annonces arrivées au terme de leur durée de publication.
Schedule::call(function () {
    \App\Models\Ad::query()
        ->where('status', 'active')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', now())
        ->update(['status' => 'expired']);
})->name('ads:expire')->dailyAt('00:15')->withoutOverlapping();
