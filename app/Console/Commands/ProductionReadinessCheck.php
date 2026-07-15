<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionReadinessCheck extends Command
{
    protected $signature = 'app:production-check {--strict : Retourne une erreur même hors environnement production}';

    protected $description = 'Vérifie les paramètres indispensables avant une mise en production';

    public function handle(): int
    {
        $checks = [
            'APP_KEY défini' => filled(config('app.key')),
            'APP_DEBUG désactivé' => config('app.debug') === false,
            'APP_URL en HTTPS' => str_starts_with((string) config('app.url'), 'https://'),
            'Base persistante (pas SQLite)' => config('database.default') !== 'sqlite',
            'File de tâches asynchrones' => config('queue.default') !== 'sync',
            'Cookies de session sécurisés' => config('session.secure') === true,
            'Clé Stripe publique' => filled(config('services.stripe.key')),
            'Clé Stripe secrète' => filled(config('services.stripe.secret')),
            'Secret webhook Stripe' => filled(config('services.stripe.webhook_secret')),
            'Adresse e-mail d’envoi' => filled(config('mail.from.address')),
            'Adresse de support' => filled(config('site.support_email')),
            'Raison sociale' => filled(config('legal.entity_name')),
            'Adresse légale' => filled(config('legal.address')),
            'Directeur de publication' => filled(config('legal.publication_director')),
            'Nom de l’hébergeur' => filled(config('legal.host_name')),
            'Adresse de l’hébergeur' => filled(config('legal.host_address')),
        ];

        $rows = collect($checks)->map(fn (bool $passed, string $label) => [
            $passed ? '<fg=green>OK</>' : '<fg=red>MANQUANT</>',
            $label,
        ])->values()->all();

        $this->table(['État', 'Contrôle'], $rows);

        $failures = collect($checks)->filter(fn (bool $passed) => ! $passed)->keys();
        if ($failures->isEmpty()) {
            $this->info('Configuration prête pour la production.');

            return self::SUCCESS;
        }

        $this->warn($failures->count().' contrôle(s) restent à corriger.');

        return (app()->environment('production') || $this->option('strict'))
            ? self::FAILURE
            : self::SUCCESS;
    }
}
