{{-- Parcours guidé « Publier sans écrire » : compteurs agrégés mesurés, sans pourcentage. --}}
<div class="mt-4">
    <h3 class="h6 fw-bold mb-1">Parcours guidé (/demande?situation=…)</h3>
    <p class="small text-muted mb-2">Compteurs bruts sur la période. Aucun texte saisi n’est enregistré.</p>
    <div class="journey-resume-badges">
        @foreach($guidedJourney as $guidedRow)
            <span><strong>{{ number_format($guidedRow['count']) }}</strong> {{ $guidedRow['label'] }}</span>
        @endforeach
    </div>
</div>
