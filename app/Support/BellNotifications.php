<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AdCandidatureNotification;
use App\Notifications\BoostExpiringNotification;
use App\Notifications\InvoiceReminderSentNotification;
use App\Notifications\MonthlyActivityReportNotification;
use App\Notifications\NewAdMatchingNotification;
use App\Notifications\QuoteAnsweredNotification;
use App\Notifications\QuoteReminderSentNotification;
use App\Notifications\ReferralRewardNotification;
use App\Notifications\SavedSearchMatchNotification;
use App\Notifications\ServiceProposalReceivedNotification;
use App\Notifications\ServiceProposalStatusNotification;

/**
 * Ce que le membre choisit de voir dans la cloche de l'en-tête (demande du fondateur,
 * 24/11/2026 : un interrupteur par type, tout activé par défaut).
 *
 * Seules les catégories ci-dessous se coupent. Les messages, les commandes et leurs
 * paiements, les rappels de service, l'abonnement et ses factures, le compte, la
 * vérification d'identité et les réponses de l'assistance restent toujours affichés :
 * un membre ne doit jamais manquer une commande ou un paiement.
 *
 * Le réglage ne concerne que la cloche (canal « database ») : les e-mails ont leurs
 * propres réglages, et les notifications de l'appareil se règlent sur chaque appareil.
 */
final class BellNotifications
{
    /**
     * @return array<string, array{label: string, hint: string, providers_only: bool, notifications: array<int, class-string>}>
     */
    public static function categories(): array
    {
        return [
            'new_ads' => [
                'label' => 'Nouvelles annonces',
                'hint' => 'Les annonces qui correspondent à mes alertes de recherche ou à mes métiers.',
                'providers_only' => false,
                'notifications' => [SavedSearchMatchNotification::class, NewAdMatchingNotification::class],
            ],
            'proposals' => [
                'label' => 'Propositions et candidatures',
                'hint' => 'Une proposition reçue sur ma demande, la réponse à ma proposition, une candidature à mon offre.',
                'providers_only' => false,
                'notifications' => [ServiceProposalReceivedNotification::class, ServiceProposalStatusNotification::class, AdCandidatureNotification::class],
            ],
            'boosts' => [
                'label' => 'Mise en avant de mes annonces',
                'hint' => 'La fin prochaine d’un boost ou de l’option Urgent.',
                'providers_only' => false,
                'notifications' => [BoostExpiringNotification::class],
            ],
            'referral' => [
                'label' => 'Parrainage',
                'hint' => 'Un bonus de parrainage débloqué.',
                'providers_only' => false,
                'notifications' => [ReferralRewardNotification::class],
            ],
            'quotes' => [
                'label' => 'Devis et factures',
                'hint' => 'La réponse d’un client à mon devis, les relances envoyées par l’Espace Pro.',
                'providers_only' => true,
                'notifications' => [QuoteAnsweredNotification::class, QuoteReminderSentNotification::class, InvoiceReminderSentNotification::class],
            ],
            'reports' => [
                'label' => 'Rapport d’activité mensuel',
                'hint' => 'Le bilan du mois écoulé dans l’Espace Pro.',
                'providers_only' => true,
                'notifications' => [MonthlyActivityReportNotification::class],
            ],
        ];
    }

    /** Ce qui reste toujours dans la cloche, dit au membre sous les interrupteurs. */
    public const ALWAYS_SHOWN = 'messages, commandes et paiements, rappels de service, abonnement et factures, compte et vérification d’identité, réponses de l’assistance';

    /** Catégories proposées à ce membre (celles de l'Espace Pro seulement à un prestataire). */
    public static function categoriesFor(User $user): array
    {
        $isProvider = ClientSpaceNavigation::proEntry($user)['is_provider'];

        return array_filter(self::categories(), fn (array $category) => $isProvider || ! $category['providers_only']);
    }

    /** Catégorie d'une notification, ou null si elle est toujours affichée. */
    public static function categoryOf(object $notification): ?string
    {
        foreach (self::categories() as $key => $category) {
            if (in_array($notification::class, $category['notifications'], true)) {
                return $key;
            }
        }

        return null;
    }

    public static function isMuted(User $user, string $category): bool
    {
        return in_array($category, (array) ($user->muted_bell_categories ?? []), true);
    }

    /** La notification doit-elle entrer dans la cloche de ce destinataire ? */
    public static function allows(object $notifiable, object $notification): bool
    {
        if (! $notifiable instanceof User) {
            return true;
        }

        $category = self::categoryOf($notification);

        return $category === null || ! self::isMuted($notifiable, $category);
    }
}
