<section class="premium-comparison" aria-label="Comparer le compte gratuit et Premium">
    <h2 style="font-size:1.2rem;font-weight:700;">Ce que Premium change pour vous</h2>
    <p style="color:#526176;font-size:.9rem;">Les échanges et les réglages du compte restent accessibles gratuitement. Premium ajoute des avantages de présentation et de visibilité.</p>
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;font-size:.86rem;border-collapse:collapse;">
            <caption style="caption-side:bottom;font-size:.8rem;color:#526176;">Les quotas d’annonces dépendent du statut du compte. Premium ne rend pas les publications illimitées.</caption>
            <thead><tr><th scope="col" style="padding:12px 8px;">Fonctionnalité</th><th scope="col" style="padding:12px 8px;">Gratuit</th><th scope="col" style="padding:12px 8px;">Premium</th></tr></thead>
            <tbody>
                @foreach([
                    ['Rechercher et échanger', 'Inclus', 'Inclus'],
                    ['Photos par offre', '2 photos', '4 photos'],
                    ['Offres sur l’accueil des clients', 'Affichage ordinaire', 'Priorité avec mention Sponsorisé, sans place fixe'],
                    ['Suggestions après une demande', 'Selon la recherche', 'Profil présenté avant les non-abonnés éligibles'],
                    ['Boosts et option Urgent', 'Tarif standard', 'Réduction de 20 %'],
                    ['CRM des prestataires et suivi d’activité', 'Accessibles aux prestataires', 'Accessibles aux prestataires'],
                    ['Notifications, confidentialité et assistance', 'Inclus', 'Inclus'],
                ] as $row)
                    <tr style="border-top:1px solid #e2e8f0;"><th scope="row" style="padding:12px 8px;font-weight:600;">{{ $row[0] }}</th><td style="padding:12px 8px;">{{ $row[1] }}</td><td style="padding:12px 8px;">{{ $row[2] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
