<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ces signaux éphémères ne constituent pas un historique de connexions.
Schedule::call(fn () => DB::table('session_user_activity')->where('last_interaction_at', '<', now()->subDays(3)->timestamp)->delete())
    ->name('session-activity:prune')->daily()->withoutOverlapping();

Schedule::call(fn () => DB::table('user_presence_leases')->where('expires_at', '<', now()->subDay())->delete())
    ->name('presence:prune')->hourly()->withoutOverlapping();

Artisan::command('mail:test {to?}', function () {
    $to = $this->argument('to') ?: config('mail.from.address');

    Mail::raw('Email de test depuis Prokejem.', function ($message) use ($to) {
        $message->to($to)->subject('Email de test Prokejem');
    });

    $this->info("Mail sent to {$to}");
})->purpose('Send a test email via SMTP');

// ─── Nettoyage automatique des utilisateurs soft-deleted ───
// Exécuté tous les jours à 3h du matin
// Désactivé par défaut (config marketplace.purge_deleted_accounts) : la purge
// est définitive, sans délai de grâce, et emporte les commandes du compte.
Schedule::command('cleanup:orphaned-data --force')
    ->when(fn () => (bool) config('marketplace.purge_deleted_accounts'))
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

// ─── Remontée automatique des offres ───
// Toutes les heures : remontées achetées arrivées à échéance et remontée
// hebdomadaire incluse dans l'abonnement Premium. Sans effet tant que les
// nouvelles règles de mise en avant ne sont pas en vigueur.
Schedule::command('ads:bump')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ads-bump.log'));

// ─── Alertes d'expiration boost/urgent ───
// Exécuté toutes les 6 heures pour notifier les utilisateurs dont les boosts/urgents expirent bientôt
Schedule::command('boosts:send-expiring-alerts')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/boosts-alerts.log'));

// Les abonnements récurrents sont synchronisés par webhook Stripe. Ce nettoyage
// concerne uniquement les accès manuels accordés par un administrateur.
Schedule::command('subscriptions:expire-pro')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pro-subscriptions-cleanup.log'));

// Paiements d'abonnement refusés : rappels pendant le délai de régularisation,
// puis résiliation et retour au compte gratuit quand le délai est écoulé.
Schedule::command('subscriptions:enforce-payment-grace')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pro-subscriptions-payment-grace.log'));

// Retire chaque nuit les annonces arrivées au terme de leur durée de publication.
Schedule::command('ads:expire')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ads-expire.log'));

// Les compteurs d'audience sont uniquement agrégés et conservés au maximum 25 mois.
Schedule::command('usage:prune --months=25')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/usage-prune.log'));

// Les vues de profil detaillees sont conservees 13 mois : de quoi comparer un
// mois a celui de l'annee precedente, sans garder d'historique inutile.
Schedule::command('profile-views:prune --months=13')
    ->dailyAt('02:45')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/profile-views-prune.log'));

// Envoie uniquement les rappels volontairement programmés par les clients.
// Cette tâche ne republie jamais une annonce et ne déclenche aucun paiement.
Schedule::command('service-reminders:send')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/service-reminders.log'));

// Un seul rappel par brouillon de demande, envoyé automatiquement à un compte
// vérifié qui l'accepte. Jamais la nuit : entre 8 h et 20 h, heure de Mayotte
// (6 h–18 h à Paris en hiver, 9 h–21 h à La Réunion).
Schedule::command('demand-drafts:send-reminders')
    ->everyFifteenMinutes()
    ->timezone('Indian/Mayotte')
    ->between('8:00', '20:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/demand-draft-reminders.log'));

// Rapport d'activité du mois écoulé pour les abonnés Premium. La commande passe
// chaque matin mais n'envoie qu'une fois par mois et par abonné : un passage
// manqué (redémarrage) est rattrapé le lendemain, et un nouvel abonné reçoit
// son rapport dès le matin suivant sa souscription.
Schedule::command('pro:send-activity-reports')
    ->timezone('Indian/Mayotte')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pro-activity-reports.log'));

// Rappelle au client de confirmer la fin d'une prestation payée : sans cela le
// prestataire n'est pas versé. La tâche ne verse rien elle-même. Jamais la nuit.
Schedule::command('service-orders:remind-release')
    ->hourly()
    ->timezone('Indian/Mayotte')
    ->between('8:00', '20:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/release-reminders.log'));

// Relance unique des devis Premium restés sans réponse depuis 7 jours, à la
// demande du prestataire. Elle écrit à ses clients : jamais la nuit.
Schedule::command('pro-quotes:send-reminders')
    ->hourly()
    ->timezone('Indian/Mayotte')
    ->between('8:00', '20:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pro-quote-reminders.log'));

// Factures : passage « En retard » des factures échues (tous les comptes) et
// rappel unique des factures Premium impayées. Il écrit aux clients : jamais la nuit.
Schedule::command('pro-invoices:follow-up')
    ->hourly()
    ->timezone('Indian/Mayotte')
    ->between('8:00', '20:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pro-invoice-follow-up.log'));
