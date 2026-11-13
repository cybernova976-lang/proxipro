@php
    $pkPremium = $user->hasActiveProSubscription();
    $pkPlanned = app(\App\Services\QuoteReminderService::class)->plannedFor($quote);
    $pkCanRemind = $quote->status === 'sent' && $quote->sent_to_email && ! $quote->reminder_sent_at;
@endphp
@push('head')
    <link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) ?: 1 }}">
@endpush
<section class="pk-templates mt-3" aria-labelledby="pkQuoteToolsTitle">
    <div class="pk-templates__head">
        <h2 id="pkQuoteToolsTitle">Modèle et relance</h2>
        <span>Premium</span>
    </div>

    @if($quote->reminder_sent_at)
        <p class="mb-3"><i class="fas fa-check-circle" aria-hidden="true"></i> Relance envoyée à {{ $quote->sent_to_email }} le {{ $quote->reminder_sent_at->format('d/m/Y') }}. Il n’y a pas de seconde relance automatique.</p>
    @elseif($pkPlanned)
        <form method="POST" action="{{ route('pro.quotes.reminder', $quote->id) }}" class="pk-templates__list mb-3">
            @csrf @method('PUT')
            <input type="hidden" name="enabled" value="0">
            <p><i class="far fa-clock" aria-hidden="true"></i> Relance automatique prévue le {{ $pkPlanned->format('d/m/Y') }} à {{ $quote->sent_to_email }}, si le devis est toujours sans réponse.
                <button type="submit" class="pk-templates__delete">Annuler la relance</button></p>
        </form>
    @elseif($pkCanRemind && $pkPremium)
        <form method="POST" action="{{ route('pro.quotes.reminder', $quote->id) }}" class="mb-3">
            @csrf @method('PUT')
            <input type="hidden" name="enabled" value="1">
            <p>Aucune relance prévue pour ce devis.
                <button type="submit" class="pk-templates__use">Activer la relance automatique</button></p>
        </form>
    @endif

    @if($pkPremium)
        <form method="POST" action="{{ route('pro.quotes.template.store', $quote->id) }}" class="pk-templates__actions">
            @csrf
            <label for="pkTemplateName" class="visually-hidden">Nom du modèle</label>
            <input type="text" id="pkTemplateName" name="name" class="form-control" maxlength="120" required value="{{ old('name', $quote->subject) }}" style="border-radius: 10px; max-width: 340px;">
            <button type="submit" class="pk-templates__use">Enregistrer comme modèle</button>
        </form>
        <p class="mt-2">Le modèle reprend les lignes, la TVA, l’acompte et les conditions — jamais les coordonnées du client.</p>
    @else
        <p>Enregistrez ce devis comme modèle et faites relancer automatiquement votre client après {{ \App\Services\QuoteReminderService::DELAY_DAYS }} jours sans réponse.
            <a href="{{ route('pro.subscription') }}">Voir Premium</a></p>
    @endif
</section>
