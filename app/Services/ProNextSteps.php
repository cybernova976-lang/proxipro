<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\User;

/**
 * « Que dois-je faire maintenant ? » pour l'accueil de l'espace Pro.
 *
 * Chaque étape vient d'une donnée mesurée (commande, message, devis, facture,
 * statut). Rien n'est affiché quand le compteur est nul : la liste est vide
 * quand il n'y a rien à faire, et l'accueil le dit.
 */
class ProNextSteps
{
    public const MAX_STEPS = 4;

    /**
     * @param  array{new_opportunities: int, has_services: bool}  $context
     * @return array<int, array{key: string, title: string, detail: string, url: string, cta: string}>
     */
    public function for(User $user, array $context): array
    {
        $isProfessional = $user->isProfessionnel();
        $steps = [];

        $ordersToAccept = ServiceOrder::query()
            ->where('seller_id', $user->id)
            ->where('status', ServiceOrder::STATUS_PENDING_ACCEPTANCE)
            ->count();
        if ($ordersToAccept > 0) {
            $steps[] = $this->step('orders', $this->plural($ordersToAccept, 'commande attend', 'commandes attendent').' votre réponse',
                'Le client a réservé : acceptez ou déclinez pour qu’il puisse payer.', route('service-orders.index'), 'Répondre');
        }

        $unread = (int) $user->unreadMessagesCount();
        if ($unread > 0) {
            $steps[] = $this->step('messages', $this->plural($unread, 'message non lu', 'messages non lus'),
                'Répondre vite augmente vos chances d’être choisi.', route('messages.index'), 'Lire');
        }

        if (! $context['has_services']) {
            $steps[] = $this->step('services', 'Indiquez votre métier pour recevoir des demandes',
                'Sans métier déclaré, aucune demande ne peut vous être proposée.', route('service-provider.mes-services'), 'Ajouter mon métier');
        } elseif ($context['new_opportunities'] > 0) {
            $steps[] = $this->step('opportunities', $this->plural($context['new_opportunities'], 'demande correspond', 'demandes correspondent').' à votre métier',
                'Envoyez une proposition aux clients qui cherchent quelqu’un comme vous.', route('pro.opportunities'), 'Examiner');
        }

        if (! $isProfessional) {
            $steps[] = $this->step('status', 'Passez en professionnel',
                'Auto-entrepreneur ou société : émettez vos devis et factures à votre nom.', route('pro.account-status'), 'Déclarer mon statut');
        } elseif (! $user->canIssueCommercialDocuments()) {
            $missing = $user->proCommercialMissingRequirements();
            $steps[] = $this->step('compliance', 'Terminez votre conformité pour émettre devis et factures',
                $this->plural(count($missing), 'étape restante', 'étapes restantes').' : '.$missing[0].'.', route('pro.compliance'), 'Continuer');
        }

        if ($isProfessional) {
            $unpaid = $user->proInvoices()->whereIn('status', ['sent', 'overdue'])->count();
            if ($unpaid > 0) {
                $steps[] = $this->step('invoices', $this->plural($unpaid, 'facture envoyée non réglée', 'factures envoyées non réglées'),
                    'Vérifiez les paiements reçus ou relancez vos clients.', route('pro.invoices'), 'Suivre');
            }

            $drafts = $user->proQuotes()->where('status', 'draft')->count();
            if ($drafts > 0) {
                $steps[] = $this->step('drafts', $this->plural($drafts, 'devis en brouillon', 'devis en brouillon'),
                    'Finalisez-les et envoyez-les à vos clients.', route('pro.quotes'), 'Finaliser');
            }

            $awaiting = $user->proQuotes()->where('status', 'sent')->count();
            if ($awaiting > 0) {
                $steps[] = $this->step('awaiting', $this->plural($awaiting, 'devis en attente de réponse', 'devis en attente de réponse'),
                    'Marquez-les acceptés ou refusés dès que le client répond.', route('pro.quotes'), 'Voir');
            }
        }

        return array_slice($steps, 0, self::MAX_STEPS);
    }

    private function step(string $key, string $title, string $detail, string $url, string $cta): array
    {
        return compact('key', 'title', 'detail', 'url', 'cta');
    }

    private function plural(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count > 1 ? $plural : $singular);
    }
}
