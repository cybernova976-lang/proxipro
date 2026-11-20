<?php

namespace App\Support;

/**
 * Modèles de messages du studio e-mail pour l'ouverture officielle.
 *
 * Deux messages distincts, parce qu'ils n'ont pas les mêmes destinataires :
 *  - « lancement-service » : remerciement et mode d'emploi, sans argument de
 *    vente. Message de service, adressé à tous les comptes actifs.
 *  - « lancement-offres » : pourquoi payer alors que publier est gratuit, avec
 *    le comparatif. Information commerciale, réservée aux inscrits à la newsletter.
 *
 * Un modèle ne fait que préremplir le studio : l'administrateur relit, corrige
 * et valide avant tout envoi. Aucun chiffre n'y est écrit en dur ; les prix
 * viennent du bloc « comparatif » (App\Support\OfferComparison).
 */
class LaunchEmailTemplates
{
    /** @return array<string, array{label: string, type: string, subject: string, eyebrow: string, headline: string, blocks: list<array<string, string>>}> */
    public static function all(): array
    {
        return [
            'lancement-service' => [
                'label' => 'Ouverture : remerciement et mode d’emploi',
                'type' => ManagedEmailAudience::SERVICE,
                'subject' => 'Prokejem ouvre officiellement : votre mode d’emploi',
                'eyebrow' => 'Ouverture officielle',
                'headline' => 'Merci d’être là depuis le début',
                'blocks' => [
                    self::text(
                        "Bonjour,\n\n"
                        ."Vous avez créé votre compte sur Prokejem avant son ouverture officielle. Merci pour cette confiance : c’est avec les premiers inscrits que la plateforme a été mise au point.\n\n"
                        .'Prokejem ouvre maintenant officiellement. Voici comment vous en servir, en quelques étapes.'
                    ),
                    self::text(
                        "VOUS CHERCHEZ UN PRESTATAIRE\n\n"
                        ."1. Décrivez votre besoin en publiant une demande. C’est gratuit.\n"
                        ."2. Prokejem vous propose les prestataires qui correspondent à votre demande ; ceux qui sont intéressés peuvent aussi vous faire une proposition.\n"
                        ."3. Vous consultez leurs profils et vous échangez par la messagerie.\n"
                        .'4. Vous convenez ensemble du service, du prix et de la date avant de commencer.'
                    ),
                    self::text(
                        "VOUS PROPOSEZ VOS SERVICES\n\n"
                        ."1. Complétez votre profil, puis activez votre profil prestataire. L’activation comprend un contrôle d’identité, sans frais.\n"
                        ."2. Publiez vos offres. La publication est gratuite, dans la limite d’annonces actives prévue pour votre compte.\n"
                        ."3. Répondez aux demandes des clients et suivez vos échanges dans la messagerie.\n"
                        .'4. Si vous êtes un professionnel, l’espace Pro vous permet de créer vos devis et vos factures. Votre client peut accepter un devis en ligne, sans créer de compte.'
                    ),
                    self::text(
                        "LE PRIX DE LA PRESTATION\n\n"
                        .'Le client et le prestataire fixent le prix ensemble. Lorsqu’une commande est réglée par Prokejem, une commission est déduite du montant versé au prestataire : son taux est indiqué sur la page « Offres et tarifs », et le détail de la commande est présenté avant validation.'
                    ),
                    self::text(
                        "Une question ? Écrivez-nous depuis la page « Contact » du site.\n\n"
                        .'L’équipe Prokejem'
                    ),
                    self::button('Ouvrir mon espace Prokejem', route('feed')),
                ],
            ],
            'lancement-offres' => [
                'label' => 'Offres : pourquoi payer alors que publier est gratuit',
                'type' => ManagedEmailAudience::NEWSLETTER,
                'subject' => 'Publier est gratuit sur Prokejem. Alors, pourquoi payer ?',
                'eyebrow' => 'Offres Prokejem',
                'headline' => 'Ce que vous obtenez gratuitement, et ce que le payant ajoute',
                'blocks' => [
                    self::text(
                        "Bonjour,\n\n"
                        ."Sur Prokejem, publier vos offres et vos demandes et échanger par la messagerie sont gratuits.\n\n"
                        .'Ce que vous payez, c’est d’être vu avant les autres et de gagner du temps. Une offre gratuite s’affiche à sa place, parmi toutes les autres. Une offre mise en avant porte la mention « Sponsorisé » et passe avant les offres non sponsorisées.'
                    ),
                    ['type' => 'comparison'],
                    self::text(
                        "DEUX FAÇONS DE PAYER, SELON VOTRE ACTIVITÉ\n\n"
                        ."À la carte : vous mettez en avant une offre précise, pour une durée choisie, sans abonnement. C’est adapté si vous publiez de temps en temps.\n\n"
                        ."Premium : un abonnement mensuel, sans engagement de durée, qui met en avant toutes vos offres et ajoute les outils de l’espace Pro : modèles de devis, relance des devis sans réponse, votre logo sur vos documents, rappel des factures impayées et rapport d’activité mensuel. C’est adapté si vous vivez de votre activité.\n\n"
                        .'Vous pouvez arrêter le renouvellement de Premium à tout moment depuis votre compte : vous gardez vos avantages jusqu’à la fin de la période payée, puis votre compte reste gratuit.'
                    ),
                    self::button('Comparer les offres', route('public.provider-offers')),
                ],
            ],
        ];
    }

    public static function get(?string $key): ?array
    {
        return $key !== null ? (self::all()[$key] ?? null) : null;
    }

    private static function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    private static function button(string $label, string $url): array
    {
        return ['type' => 'button', 'label' => $label, 'url' => $url, 'align' => 'center'];
    }
}
