@php($pkPremium = $user->hasActiveProSubscription())
@once
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) ?: 1 }}">
    @endpush
@endonce
<section class="pk-templates" aria-labelledby="pkTemplatesTitle">
    <div class="pk-templates__head">
        <h2 id="pkTemplatesTitle">Modèles de devis</h2>
        <span>Premium</span>
    </div>
    @if($quoteTemplates->isEmpty())
        <p>
            @if($pkPremium)
                Aucun modèle pour l’instant. Ouvrez un devis et choisissez « Enregistrer comme modèle » : ses lignes, sa TVA et ses conditions seront reprises en un clic.
            @else
                Enregistrez vos devis types et repartez-en en un clic, sans ressaisir les lignes ni les conditions.
                <a href="{{ route('pro.subscription') }}">Voir Premium</a>
            @endif
        </p>
    @else
        @unless($pkPremium)
            <p class="mb-2">Vos modèles sont conservés. Leur utilisation fait partie de Premium. <a href="{{ route('pro.subscription') }}">Voir Premium</a></p>
        @endunless
        <ul class="pk-templates__list">
            @foreach($quoteTemplates as $quoteTemplate)
                <li>
                    <div>
                        <strong>{{ $quoteTemplate->name }}</strong>
                        <small>{{ $quoteTemplate->subject }} · {{ count($quoteTemplate->items) }} ligne{{ count($quoteTemplate->items) > 1 ? 's' : '' }}</small>
                    </div>
                    <div class="pk-templates__actions">
                        @if($pkPremium)
                            <a class="pk-templates__use" href="{{ route('pro.quotes.create', ['modele' => $quoteTemplate->id]) }}">Utiliser ce modèle</a>
                        @endif
                        <form method="POST" action="{{ route('pro.quote-templates.destroy', $quoteTemplate->id) }}" onsubmit="return confirm('Supprimer ce modèle ? Les devis déjà créés ne sont pas modifiés.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="pk-templates__delete pk-templates__delete--icon" aria-label="Supprimer le modèle {{ $quoteTemplate->name }}" title="Supprimer ce modèle"><i class="fas fa-trash-can" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
