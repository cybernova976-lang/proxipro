<?php

namespace App\Services\Feed;

/**
 * Bouton dominant de l'accueil client : un seul bouton plein par écran.
 *
 * Priorité : une action attendue (proposition reçue, paiement, litige) passe
 * avant la reprise d'un brouillon, qui passe avant « Décrire mon besoin ».
 */
final class ClientPrimaryActionResolver
{
    /**
     * @param  array<string, mixed>|null  $clientActivity  App\Services\ClientActivityService::summary()
     * @return array{state: string, label: string, url: string}
     */
    public function resolve(?array $clientActivity, bool $hasDraft): array
    {
        $primary = $clientActivity['primary'] ?? null;

        if (is_array($primary) && (int) ($primary['priority'] ?? 99) <= 2 && ! empty($primary['action_url'])) {
            return [
                'state' => 'action',
                'label' => (string) ($primary['short_action'] ?? 'Voir'),
                'url' => (string) $primary['action_url'],
            ];
        }

        if ($hasDraft) {
            // /demande recharge le brouillon enregistré du compte (DemandController::create).
            return ['state' => 'draft', 'label' => 'Reprendre ma demande', 'url' => route('demand.create')];
        }

        return ['state' => 'publish', 'label' => 'Décrire mon besoin', 'url' => route('demand.create')];
    }
}
