<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\InvoiceAdjustments;
use DomainException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/** Avoirs et factures d'acompte de l'espace Pro (règles : App\Services\InvoiceAdjustments). */
class ProInvoiceAdjustmentsController extends Controller
{
    /** Émet un avoir, total ou partiel, sur une facture émise. */
    public function storeCreditNote(Request $request, int $id, InvoiceAdjustments $adjustments)
    {
        $user = $this->professional($request);
        $invoice = $user->proInvoices()->findOrFail($id);
        $this->ensureCanIssue($user);

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
    public function storeDepositInvoice(Request $request, int $id, InvoiceAdjustments $adjustments)
    {
        $user = $this->professional($request);
        $quote = $user->proQuotes()->findOrFail($id);

        try {
            $invoice = $adjustments->draftDepositInvoice($user, $quote);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('pro.invoices.edit', $invoice->id)
            ->with('success', 'Brouillon de facture d’acompte créé. Vérifiez-le, puis envoyez-le : il recevra son numéro à l’émission.');
    }

    private function professional(Request $request): User
    {
        $user = $request->user();
        if (! $user->isProfessionnel()) {
            throw new HttpResponseException(redirect()->route('pro.account-status')->with(
                'warning', 'Les devis et factures professionnels nécessitent un statut professionnel.'
            ));
        }

        return $user;
    }

    private function ensureCanIssue(User $user): void
    {
        if (! $user->canIssueCommercialDocuments()) {
            throw new HttpResponseException(redirect()->route('pro.compliance')->with(
                'warning', 'Un avoir ne peut pas encore être émis. Terminez la checklist de conformité PRO.'
            ));
        }
    }
}
