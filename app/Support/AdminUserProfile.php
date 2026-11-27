<?php

namespace App\Support;

use App\Models\IdentityVerification;
use App\Models\TermsAcceptance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Informations d'un membre présentées sur sa fiche dans l'administration.
 *
 * Seules les valeurs réellement enregistrées sont affichées : un champ vide
 * n'apparaît pas. Les données techniques ou sensibles (mot de passe chiffré,
 * codes de confirmation, adresse IP de l'acceptation des conditions,
 * coordonnées GPS) ne sont jamais présentées.
 */
class AdminUserProfile
{
    private const ANNUAIRE = 'https://annuaire-entreprises.data.gouv.fr';

    private const BUSINESS_TYPES = ['entreprise' => 'Entreprise (société)', 'auto_entrepreneur' => 'Auto-entrepreneur'];

    private const VERIFICATION_STATUSES = [
        'awaiting_payment' => 'Commencée, non envoyée',
        'pending' => 'En cours d’examen',
        'returned' => 'Renvoyée au membre pour compléments',
        'approved' => 'Approuvée',
        'rejected' => 'Refusée',
    ];

    private const SOCIAL_NETWORKS = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok'];

    /**
     * @return array<string, array{title: string, icon: string, empty: string, rows: list<array{label: string, value: string, href?: string, link?: string, note?: string, warning?: bool}>}>
     */
    public static function sections(User $user): array
    {
        return [
            'company' => ['title' => 'Entreprise', 'icon' => 'fa-building', 'empty' => 'Aucune information d’entreprise renseignée.', 'rows' => self::company($user)],
            'provider' => ['title' => 'Profil prestataire', 'icon' => 'fa-briefcase', 'empty' => 'Aucun profil prestataire renseigné.', 'rows' => self::provider($user)],
            'location' => ['title' => 'Localisation', 'icon' => 'fa-location-dot', 'empty' => 'Aucune localisation renseignée.', 'rows' => self::location($user)],
            'account' => ['title' => 'Compte et consentements', 'icon' => 'fa-user-lock', 'empty' => '', 'rows' => self::account($user)],
            'logins' => ['title' => 'Dernières connexions', 'icon' => 'fa-clock-rotate-left', 'empty' => 'Aucune connexion enregistrée pour ce compte.', 'rows' => self::logins($user)],
        ];
    }

    private static function company(User $user): array
    {
        $rows = [];
        if (filled($user->business_type)) {
            $rows[] = ['label' => 'Statut', 'value' => self::BUSINESS_TYPES[$user->business_type] ?? Str::headline($user->business_type)];
        }
        self::push($rows, 'Raison sociale', $user->company_name);
        if (filled($user->siret)) {
            $rows[] = self::registrationNumber((string) $user->siret);
        }
        self::push($rows, 'Numéro de TVA', $user->tva_number);
        self::push($rows, 'Secteur', $user->business_sector);
        self::push($rows, 'Métier', $user->profession);
        self::push($rows, 'Numéro d’assurance', $user->insurance_number);

        return $rows;
    }

    /** SIRET (14 chiffres) ou SIREN (9 chiffres), avec le contrôle de sa clé et un lien vers l'annuaire officiel. */
    public static function registrationNumber(string $raw): array
    {
        $digits = preg_replace('/\s+/', '', $raw);

        if (preg_match('/^\d{14}$/', $digits)) {
            $row = ['label' => 'SIRET', 'value' => substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6, 3).' '.substr($digits, 9),
                'href' => self::ANNUAIRE.'/etablissement/'.$digits, 'link' => 'Vérifier dans l’annuaire des entreprises'];
        } elseif (preg_match('/^\d{9}$/', $digits)) {
            $row = ['label' => 'SIREN', 'value' => substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6),
                'href' => self::ANNUAIRE.'/entreprise/'.$digits, 'link' => 'Vérifier dans l’annuaire des entreprises'];
        } else {
            return ['label' => 'SIRET', 'value' => $raw, 'note' => 'Format inhabituel : un SIRET compte 14 chiffres, un SIREN 9.', 'warning' => true];
        }

        if (! self::validKey($digits)) {
            $row['note'] = 'La clé de contrôle de ce numéro ne correspond pas : il contient probablement une erreur de saisie.';
            $row['warning'] = true;
        }

        return $row;
    }

    /** Clé de Luhn des numéros SIREN et SIRET (les établissements de La Poste suivent une règle à part). */
    private static function validKey(string $digits): bool
    {
        // Établissements de La Poste : la somme des chiffres est un multiple de 5 (le siège suit la règle générale).
        if (strlen($digits) === 14 && str_starts_with($digits, '356000000') && array_sum(str_split($digits)) % 5 === 0) {
            return true;
        }

        $sum = 0;
        foreach (array_reverse(str_split($digits)) as $position => $digit) {
            $value = (int) $digit * ($position % 2 === 1 ? 2 : 1);
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return $sum % 10 === 0;
    }

    private static function provider(User $user): array
    {
        $rows = [];
        if ($user->service_provider_since) {
            $rows[] = ['label' => 'Prestataire depuis le', 'value' => $user->service_provider_since->format('d/m/Y')];
        }
        $categories = self::categoryNames((array) ($user->pro_service_categories ?? []));
        self::push($rows, 'Catégories de services', $categories);
        self::push($rows, 'Catégorie principale', $user->service_category);
        self::push($rows, 'Sous-catégories', self::listOf($user->service_subcategories));
        self::push($rows, 'Spécialités', self::listOf($user->specialties));
        if ($user->years_experience !== null) {
            $rows[] = ['label' => 'Expérience', 'value' => $user->years_experience.' '.($user->years_experience > 1 ? 'ans' : 'an')];
        }
        if ($user->hourly_rate !== null) {
            $rows[] = ['label' => 'Taux horaire', 'value' => number_format((float) $user->hourly_rate, 2, ',', ' ').' €',
                'note' => $user->show_hourly_rate ? 'Affiché sur le profil public.' : 'Masqué sur le profil public.'];
        }
        if ($user->pro_intervention_radius !== null && $user->is_service_provider) {
            $rows[] = ['label' => 'Zone d’intervention', 'value' => $user->pro_intervention_radius.' km'];
        }
        if (filled($user->website_url)) {
            $rows[] = ['label' => 'Site web', 'value' => (string) $user->website_url, 'href' => self::safeUrl($user->website_url), 'link' => 'Ouvrir'];
        }
        foreach ((array) ($user->social_links ?? []) as $network => $url) {
            if (filled($url) && ($href = self::safeUrl($url))) {
                $rows[] = ['label' => self::SOCIAL_NETWORKS[$network] ?? Str::headline((string) $network), 'value' => (string) $url, 'href' => $href, 'link' => 'Ouvrir'];
            }
        }

        return $rows;
    }

    private static function location(User $user): array
    {
        $rows = [];
        self::push($rows, 'Ville', $user->city);
        self::push($rows, 'Code postal', $user->postal_code);
        self::push($rows, 'Pays ou territoire', $user->country);
        $detected = collect([$user->detected_city, $user->detected_country])->filter()->implode(', ');
        if ($detected !== '') {
            $rows[] = ['label' => 'Position détectée', 'value' => $detected,
                'note' => $user->geo_detected_at ? 'Relevée le '.\Illuminate\Support\Carbon::parse($user->geo_detected_at)->format('d/m/Y').'.' : null];
        }

        return array_map(fn (array $row) => array_filter($row, fn ($value) => $value !== null), $rows);
    }

    private static function account(User $user): array
    {
        $rows = [];
        $rows[] = ['label' => 'Connexion', 'value' => filled($user->provider) ? 'Compte '.Str::headline((string) $user->provider) : 'Adresse e-mail et mot de passe'];
        $rows[] = $user->email_verified_at
            ? ['label' => 'Adresse e-mail', 'value' => 'Confirmée le '.$user->email_verified_at->format('d/m/Y')]
            : ['label' => 'Adresse e-mail', 'value' => 'Non confirmée', 'warning' => true];

        $acceptance = $user->accepted_terms_version_id
            ? TermsAcceptance::with('version')->where('user_id', $user->id)->where('terms_version_id', $user->accepted_terms_version_id)->first()
            : null;
        $rows[] = $acceptance
            ? ['label' => 'Conditions d’utilisation', 'value' => 'Version '.($acceptance->version?->version ?? $user->accepted_terms_version_id).' acceptée le '.$acceptance->accepted_at->format('d/m/Y')]
            : ['label' => 'Conditions d’utilisation', 'value' => 'Aucune acceptation enregistrée'];
        if ($user->pro_terms_accepted_at) {
            $rows[] = ['label' => 'Conditions professionnelles', 'value' => 'Version '.($user->pro_terms_version ?: '—').' acceptée le '.$user->pro_terms_accepted_at->format('d/m/Y')];
        }

        $verification = IdentityVerification::where('user_id', $user->id)->latest('id')->first();
        if ($verification) {
            $rows[] = ['label' => 'Vérification du profil', 'value' => self::VERIFICATION_STATUSES[$verification->status] ?? Str::headline((string) $verification->status),
                'note' => 'Demande du '.$verification->created_at->format('d/m/Y').'.',
                'href' => route('admin.verifications.show', $verification->id), 'link' => 'Voir la demande et les pièces'];
        }

        if (filled($user->stripe_connect_account_id)) {
            $rows[] = ['label' => 'Paiements reçus (Stripe Connect)', 'value' => (string) $user->stripe_connect_account_id,
                'note' => ($user->stripe_connect_payouts_enabled ? 'Versements activés' : 'Versements non activés').' · '
                    .($user->stripe_connect_charges_enabled ? 'encaissements activés.' : 'encaissements non activés.'),
                'warning' => ! $user->stripe_connect_payouts_enabled];
        } elseif ($user->is_service_provider) {
            $rows[] = ['label' => 'Paiements reçus (Stripe Connect)', 'value' => 'Non configuré'];
        }
        self::push($rows, 'Client Stripe', $user->stripe_id);

        self::push($rows, 'Code de parrainage', $user->referral_code);
        if ($user->referred_by_user_id) {
            $referrer = $user->referrer;
            $rows[] = $referrer
                ? ['label' => 'Parrainé par', 'value' => $referrer->name, 'href' => route('admin.users.show', $referrer->id), 'link' => 'Voir sa fiche']
                : ['label' => 'Parrainé par', 'value' => 'Compte supprimé'];
        }

        $rows[] = ['label' => 'Confidentialité', 'value' => implode(' · ', [
            $user->profile_public === false ? 'Profil privé' : 'Profil public',
            $user->show_email ? 'e-mail affiché' : 'e-mail masqué',
            $user->show_phone ? 'téléphone affiché' : 'téléphone masqué',
        ])];
        $rows[] = ['label' => 'E-mails', 'value' => implode(' · ', [
            $user->email_notifications === false ? 'Notifications coupées' : 'Notifications reçues',
            $user->newsletter_subscribed ? 'abonné à la lettre d’information' : 'non abonné à la lettre d’information',
        ])];

        return $rows;
    }

    private static function logins(User $user): array
    {
        return UserLoginHistory::recent($user)->map(function ($login): array {
            $date = Carbon::parse($login->logged_in_at, 'UTC');

            return [
                'label' => $login->method === 'google' ? 'Google' : 'E-mail et mot de passe',
                'value' => $date->format('d/m/Y').' à '.$date->format('H:i:s').' UTC',
            ];
        })->all();
    }

    /** @param array<int, string> $keys */
    private static function categoryNames(array $keys): string
    {
        if ($keys === []) {
            return '';
        }

        $names = [];
        foreach (MarketplaceCategoryRegistry::enabledServices() as $name => $data) {
            $names[Str::slug($name, '_')] = $name;
        }

        return collect($keys)->filter()->map(fn ($key) => $names[$key] ?? Str::headline((string) $key))->implode(', ');
    }

    private static function listOf(mixed $values): string
    {
        return collect(is_array($values) ? $values : [])->filter(fn ($value) => is_scalar($value) && filled($value))->implode(', ');
    }

    /** Lien cliquable seulement pour une adresse web : jamais de « javascript: » ou autre schéma. */
    private static function safeUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        return preg_match('#^https?://#i', $url) ? $url : null;
    }

    private static function push(array &$rows, string $label, mixed $value): void
    {
        if (is_scalar($value) && filled($value)) {
            $rows[] = ['label' => $label, 'value' => (string) $value];
        }
    }
}
