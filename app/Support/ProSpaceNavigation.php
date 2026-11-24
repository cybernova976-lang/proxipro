<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Menu unique de l'espace Pro.
 *
 * Quatre groupes nommés d'après ce que le prestataire veut faire (trouver des
 * clients, gérer son activité, suivre ses résultats, régler son entreprise),
 * une seule couleur, et un compteur seulement quand il appelle une action.
 * Les raccourcis déjà présents dans l'en-tête (messages, retour à l'accueil)
 * ne sont pas répétés ici.
 */
class ProSpaceNavigation
{
    /**
     * @return array<int, array{label: ?string, items: array<int, array{key: string, label: string, icon: string, url: string, active: bool, badge: ?string, tone: string, hint: ?string}>}>
     */
    public static function groups(Request $request, User $user): array
    {
        // Un membre qui ne propose pas encore de services n'a qu'une page utile :
        // le parcours pour devenir prestataire. Pas de menu vers des outils fermés.
        if (! $user->isProfessionnel() && ! $user->isServiceProvider()) {
            return [];
        }

        $isProfessional = $user->isProfessionnel();
        // Devis et factures exigent un statut professionnel : le lien mène
        // alors au changement de statut au lieu d'une redirection surprise.
        $statusUrl = route('pro.account-status');
        $unpaidInvoices = $isProfessional ? $user->proInvoices()->whereIn('status', ['sent', 'overdue'])->count() : 0;
        $subscription = $user->proSubscription;

        $groups = [
            ['label' => null, 'items' => [
                self::item($request, 'home', 'Accueil', 'fas fa-house', route('pro.dashboard'), ['pro.dashboard']),
            ]],
            ['label' => 'Trouver des clients', 'items' => [
                self::item($request, 'opportunities', 'Demandes à examiner', 'fas fa-magnifying-glass', route('pro.opportunities'), ['pro.opportunities']),
                self::item($request, 'ads', ClientSpaceNavigation::OFFERS_LABEL, 'fas fa-bullhorn', route('ads.myads', ['type' => 'offre']), ['ads.myads']),
            ]],
            ['label' => 'Gérer mon activité', 'items' => [
                self::item($request, 'clients', 'Clients', 'fas fa-users', route('pro.clients'), ['pro.clients*']),
                $isProfessional
                    ? self::item($request, 'quotes', 'Devis', 'fas fa-file-lines', route('pro.quotes'), ['pro.quotes*', 'pro.quote-templates*'])
                    : self::item($request, 'quotes', 'Devis', 'fas fa-lock', $statusUrl, [], hint: 'Statut professionnel requis'),
                $isProfessional
                    ? self::item($request, 'invoices', 'Factures', 'fas fa-file-invoice', route('pro.invoices'), ['pro.invoices*'],
                        badge: $unpaidInvoices > 0 ? (string) $unpaidInvoices : null, tone: 'alert')
                    : self::item($request, 'invoices', 'Factures', 'fas fa-lock', $statusUrl, [], hint: 'Statut professionnel requis'),
                self::item($request, 'agenda', 'Agenda', 'fas fa-calendar-days', route('pro.agenda'), ['pro.agenda*']),
            ]],
            ['label' => 'Suivre mes résultats', 'items' => [
                self::item($request, 'analytics', 'Statistiques', 'fas fa-chart-line', route('pro.analytics'), ['pro.analytics*']),
                self::item($request, 'report', 'Rapport mensuel', 'fas fa-chart-column', route('pro.report'), ['pro.report']),
            ]],
            ['label' => 'Mon entreprise', 'items' => [
                self::item($request, 'profile', 'Profil professionnel', 'fas fa-id-card', route('pro.profile'), ['pro.profile*']),
                self::item($request, 'status', 'Statut et conformité', 'fas fa-shield-halved', $statusUrl, ['pro.account-status*', 'pro.compliance*'],
                    badge: ! $isProfessional ? 'Passer pro' : (! $user->canIssueCommercialDocuments() ? 'À faire' : null),
                    tone: $isProfessional ? 'alert' : 'info'),
                self::item($request, 'documents', 'Justificatifs', 'fas fa-folder-open', route('pro.documents'), ['pro.documents*']),
                self::item($request, 'subscription', 'Abonnement', 'fas fa-crown', route('pro.subscription'), ['pro.subscription*', 'pro.onboarding*'],
                    badge: $subscription ? 'Actif' : null, tone: 'ok'),
            ]],
        ];

        return $groups;
    }

    /** Titre de la page active, affiché dans la barre du haut. */
    public static function activeLabel(array $groups): string
    {
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if ($item['active']) {
                    return $item['label'];
                }
            }
        }

        return 'Espace Pro';
    }

    private static function item(Request $request, string $key, string $label, string $icon, string $url, array $matches,
        ?string $badge = null, string $tone = 'neutral', ?string $hint = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'active' => $matches !== [] && $request->routeIs(...$matches),
            'badge' => $badge,
            'tone' => $tone,
            'hint' => $hint,
        ];
    }
}
