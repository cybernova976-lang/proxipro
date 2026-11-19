@php
    // Rappel unique d'une facture impayée (avantage Premium).
    $pkFollowUp = app(\App\Services\InvoiceFollowUpService::class);
    $pkPremium = $user->hasActiveProSubscription();
    $pkPlanned = $pkFollowUp->plannedFor($invoice);
    $pkCanPlan = $pkFollowUp->canPlan($invoice);
@endphp
@if($invoice->reminder_sent_at || $pkPlanned || $pkCanPlan)
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) ?: 1 }}">
    @endpush
    <section class="pk-templates mt-3" aria-labelledby="pkInvoiceReminderTitle" data-invoice-reminder>
        <div class="pk-templates__head">
            <h2 id="pkInvoiceReminderTitle">Rappel de paiement</h2>
            <span>Premium</span>
        </div>
        @if($invoice->reminder_sent_at)
            <p><i class="fas fa-check-circle" aria-hidden="true"></i> Rappel envoyé à {{ $invoice->sent_to_email }} le {{ $invoice->reminder_sent_at->format('d/m/Y') }}. Il n’y a pas de second rappel automatique.</p>
        @elseif($pkPlanned)
            <form method="POST" action="{{ route('pro.invoices.reminder', $invoice->id) }}">
                @csrf @method('PUT')
                <input type="hidden" name="enabled" value="0">
                <p><i class="far fa-clock" aria-hidden="true"></i> Rappel automatique prévu le {{ $pkPlanned->format('d/m/Y') }} à {{ $invoice->sent_to_email }}, si la facture n’est pas payée.
                    <button type="submit" class="pk-templates__delete">Annuler le rappel</button></p>
            </form>
        @elseif($pkPremium)
            <form method="POST" action="{{ route('pro.invoices.reminder', $invoice->id) }}">
                @csrf @method('PUT')
                <input type="hidden" name="enabled" value="1">
                <p>Aucun rappel prévu pour cette facture.
                    <button type="submit" class="pk-templates__use">Activer le rappel automatique</button></p>
            </form>
        @else
            <p>Faites rappeler automatiquement à votre client une facture impayée, {{ \App\Services\InvoiceFollowUpService::DELAY_DAYS }} jours après son échéance.
                <a href="{{ route('pro.subscription') }}">Voir Premium</a></p>
        @endif
    </section>
@endif
