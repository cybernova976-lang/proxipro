<?php

namespace App\Http\Controllers;

use App\Models\ProQuoteTemplate;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/**
 * Services Premium des devis : modèles réutilisables et relance automatique.
 * Supprimer un modèle ou couper une relance reste possible sans abonnement.
 */
class ProQuoteToolsController extends Controller
{
    private const PREMIUM_REQUIRED = 'Cette fonctionnalité fait partie de Premium. Votre devis n’est pas modifié.';

    public function storeTemplate(Request $request, int $id)
    {
        $user = $this->professional($request);
        $quote = $user->proQuotes()->findOrFail($id);
        $this->requirePremium($user);

        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);

        if ($user->proQuoteTemplates()->count() >= ProQuoteTemplate::LIMIT) {
            return back()->with('error', 'Vous avez atteint la limite de '.ProQuoteTemplate::LIMIT.' modèles. Supprimez-en un pour en enregistrer un nouveau.');
        }

        // Un modèle ne reprend jamais les coordonnées du client du devis d'origine.
        $user->proQuoteTemplates()->create([
            'name' => $validated['name'],
            'subject' => $quote->subject,
            'operation_type' => $quote->operation_type ?: 'services',
            // La remise et le taux d'une ligne ne sont repris que s'ils existent.
            'items' => collect($quote->items)->map(fn ($item) => array_filter([
                'description' => (string) ($item['description'] ?? ''),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'discount_percent' => isset($item['discount_percent']) ? (float) $item['discount_percent'] : null,
                'tax_rate' => isset($item['tax_rate']) ? (float) $item['tax_rate'] : null,
            ], fn ($value) => $value !== null))->values()->all(),
            'tax_rate' => $quote->tax_rate ?? 20,
            'deposit_percentage' => $quote->deposit_percentage,
            'is_free' => $quote->is_free ?? true,
            'notes' => $quote->notes,
            'conditions' => $quote->conditions,
        ]);

        return back()->with('success', 'Modèle « '.$validated['name'].' » enregistré. Vous le retrouverez en créant un devis.');
    }

    public function destroyTemplate(Request $request, int $id)
    {
        $template = $this->professional($request)->proQuoteTemplates()->findOrFail($id);
        $name = $template->name;
        $template->delete();

        return back()->with('success', 'Modèle « '.$name.' » supprimé.');
    }

    public function updateReminder(Request $request, int $id)
    {
        $user = $this->professional($request);
        $quote = $user->proQuotes()->findOrFail($id);
        $enabled = $request->boolean('enabled');

        if ($enabled) {
            $this->requirePremium($user);
            if ($quote->status !== 'sent' || ! $quote->sent_to_email) {
                return back()->with('error', 'La relance automatique concerne un devis envoyé par e-mail et encore sans réponse.');
            }
            if ($quote->reminder_sent_at) {
                return back()->with('error', 'Ce devis a déjà été relancé une fois : il n’y a pas de seconde relance automatique.');
            }
        }

        $quote->update(['reminder_enabled' => $enabled]);

        return back()->with('success', $enabled
            ? 'Relance automatique activée pour ce devis.'
            : 'Relance automatique annulée : votre client ne recevra pas de rappel.');
    }

    /** Rappel unique d'une facture impayée : activation (Premium) ou annulation. */
    public function updateInvoiceReminder(Request $request, int $id, \App\Services\InvoiceFollowUpService $followUp)
    {
        $user = $this->professional($request);
        $invoice = $user->proInvoices()->findOrFail($id);
        $enabled = $request->boolean('enabled');

        if ($enabled) {
            if (! $user->hasActiveProSubscription()) {
                return redirect()->route('pro.subscription')
                    ->with('warning', 'Cette fonctionnalité fait partie de Premium. Votre facture n’est pas modifiée.');
            }
            if ($invoice->reminder_sent_at) {
                return back()->with('error', 'Cette facture a déjà fait l’objet d’un rappel : il n’y a pas de second rappel automatique.');
            }
            if (! $followUp->canPlan($invoice)) {
                return back()->with('error', 'Le rappel automatique concerne une facture envoyée par e-mail, avec une échéance, et encore impayée.');
            }
        }

        $invoice->update(['reminder_enabled' => $enabled]);

        return back()->with('success', $enabled
            ? 'Rappel automatique activé pour cette facture.'
            : 'Rappel automatique annulé : votre client ne recevra pas de rappel.');
    }

    /** Devis et factures sont ouverts à tout membre (24/11/2026) : seul l'abonnement compte ici. */
    private function professional(Request $request): User
    {
        return $request->user();
    }

    private function requirePremium(User $user): void
    {
        if (! $user->hasActiveProSubscription()) {
            throw new HttpResponseException(redirect()->route('pro.subscription')->with('warning', self::PREMIUM_REQUIRED));
        }
    }
}
