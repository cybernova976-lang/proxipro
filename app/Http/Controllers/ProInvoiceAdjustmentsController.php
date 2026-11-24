<?php

namespace App\Http\Controllers;

use App\Services\CommercialDocumentAllowance;
use App\Services\InvoiceAdjustments;
use DomainException;
use Illuminate\Http\Request;

/**
 * Avoirs et factures d'acompte de l'espace Pro (règles : App\Services\InvoiceAdjustments).
 * Ouverts à tout membre sur ses propres documents, comme les devis et factures.
 */
class ProInvoiceAdjustmentsController extends Controller
{
    /** Émet un avoir, total ou partiel, sur une facture émise. */
    public function storeCreditNote(Request $request, int $id, InvoiceAdjustments $adjustments)
    {
        // Un avoir corrige une facture déjà émise : il n'est ni compté ni bloqué.
        $user = $request->user();
        $invoice = $user->proInvoices()->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'scope' => 'required|in:total,partial',
            'amount_ht' => 'nullable|required_if:scope,partial|numeric|min:0.01|max:99999999',
            'confirm' => 'accepted',
        ], [
            'reason.required' => 'Indiquez le motif de l’avoir : il figure sur le document.',
            'amount_ht.required_if' => 'Indiquez le montant HT de l’avoir partiel.',
            'confirm.accepted' => 'Confirmez l’émission : un avoir émis ne peut plus être modifié.',
        ]);

        try {
            $creditNote = $adjustments->issueCreditNote(
                $user, $invoice, $validated['reason'],
                $validated['scope'] === 'partial' ? (float) $validated['amount_ht'] : null
            );
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('pro.invoices.show', $creditNote->id)
            ->with('success', 'Avoir '.$creditNote->invoice_number.' émis. Téléchargez-le ou envoyez-le à votre client.');
    }

    /** Prépare la facture d'acompte d'un devis accepté, en brouillon. */
    public function storeDepositInvoice(Request $request, int $id, InvoiceAdjustments $adjustments, CommercialDocumentAllowance $allowance)
    {
        $user = $request->user();
        $quote = $user->proQuotes()->findOrFail($id);
        // Une facture d'acompte est une facture : elle compte dans les factures offertes.
        if (! $allowance->canCreate($user, CommercialDocumentAllowance::INVOICE)) {
            return redirect()->route('pro.invoices')
                ->with('warning', $allowance->limitReachedMessage(CommercialDocumentAllowance::INVOICE));
        }

        try {
            $invoice = $adjustments->draftDepositInvoice($user, $quote);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('pro.invoices.edit', $invoice->id)
            ->with('success', 'Brouillon de facture d’acompte créé. Vérifiez-le, puis envoyez-le : il recevra son numéro à l’émission.');
    }
}
