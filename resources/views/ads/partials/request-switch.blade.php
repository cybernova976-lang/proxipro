{{-- Aiguillage : une personne qui cherche un prestataire décrit son besoin dans le parcours de demande. --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/demand-guided.css') }}?v={{ @filemtime(public_path('css/demand-guided.css')) ?: 1 }}">
@endpush
<aside class="pk-request-switch" aria-labelledby="pkRequestSwitchTitle">
    <div class="pk-request-switch__text">
        <h2 id="pkRequestSwitchTitle">Vous cherchez un prestataire ?</h2>
        <p>Décrivez votre besoin en quelques touches : les prestataires vous répondent.</p>
    </div>
    <a class="pk-btn-outline pk-request-switch__link" href="{{ route('demand.create') }}">Décrire mon besoin</a>
</aside>
