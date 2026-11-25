@php
    // Filigrane et pied de page des PDF de devis et de factures, repris sur
    // chaque page (éléments fixes : à inclure en tout début de document).
    // Paramètres : $pkOwner (auteur du document), $pkDocument (devis ou facture),
    // $pkSeller (identité affichée), $pkDraftNote (mention de pied d'un brouillon).
    // La règle du filigrane « version gratuite » n'est pas ici : elle est dans
    // App\Services\CommercialDocumentAllowance::carriesFreeWatermark().
    $pkIsDraft = $pkDocument->status === 'draft';
    $pkFreeWatermark = app(\App\Services\CommercialDocumentAllowance::class)->carriesFreeWatermark($pkOwner, $pkDocument);
    // Le pied tient sur une ligne d'identité : la raison sociale complète est dans l'en-tête.
    $pkFooterIdentity = array_filter([
        \Illuminate\Support\Str::limit((string) ($pkSeller['company_name'] ?? $pkSeller['name'] ?? ''), 48) ?: null,
        ! empty($pkSeller['siret']) ? 'SIRET : '.$pkSeller['siret'] : null,
        ! empty($pkSeller['tva_number']) ? 'TVA : '.$pkSeller['tva_number'] : null,
    ]);
@endphp
@if($pkIsDraft)
<div class="pk-watermark pk-watermark-draft" data-watermark="draft">BROUILLON — NON CONTRACTUEL</div>
@elseif($pkFreeWatermark)
<div class="pk-watermark pk-watermark-free" data-watermark="free">{{ \App\Services\CommercialDocumentAllowance::FREE_WATERMARK }}</div>
@endif
<div class="pk-footer">
    <table>
        <tr>
            <td>
                {{ implode(' — ', $pkFooterIdentity) }}<br>
                @if($pkIsDraft) {{ $pkDraftNote }} — @endif Document généré le {{ now()->timezone('Europe/Paris')->format('d/m/Y') }}
                @if($pkFreeWatermark)
                <br><span class="pk-footer-free" data-free-footer>{{ \App\Services\CommercialDocumentAllowance::FREE_FOOTER }}</span>
                @endif
            </td>
            <td class="pk-footer-page">Page <span class="pk-page"></span></td>
        </tr>
    </table>
</div>
