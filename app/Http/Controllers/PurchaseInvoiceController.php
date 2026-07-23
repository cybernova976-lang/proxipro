<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\ProSubscription;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseInvoiceController extends Controller
{
    /**
     * Generate and download a purchase invoice PDF
     * Supports both point purchases (Transaction) and subscriptions (ProSubscription)
     */
    public function download(Request $request)
    {
        $user = Auth::user();
        $type = $request->query('type');
        $id = $request->query('id');

        if (!$type || !$id) {
            abort(404, 'Paramètres manquants');
        }

        if ($type === 'points') {
            return $this->generatePointsInvoice($user, $id);
        } elseif ($type === 'subscription') {
            return $this->generateSubscriptionInvoice($user, $id);
        }

        abort(404, 'Type de facture inconnu');
    }

    /**
     * Generate invoice for a points purchase
     */
    private function generatePointsInvoice($user, $transactionId)
    {
        $transaction = Transaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->firstOrFail();

        $invoiceNumber = 'PP-PTS-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);
        $date = $transaction->created_at->format('d/m/Y');
        $amount = (float) $transaction->amount;
        $itemDescription = 'Achat de points Lunamars';
        $itemDetail = $transaction->description ?? ('Pack de points - ' . $amount . '€');
        $transactionRef = $transaction->stripe_session_id;

        $pdf = Pdf::loadView('invoices.purchase-invoice-pdf', [
            'user' => $user,
            'invoiceNumber' => $invoiceNumber,
            'date' => $date,
            'amount' => $amount,
            'itemDescription' => $itemDescription,
            'itemDetail' => $itemDetail,
            'transactionRef' => $transactionRef,
        ]);

        return $pdf->download('Facture-' . $invoiceNumber . '.pdf');
    }

    /**
     * Generate invoice for a subscription
     */
    private function generateSubscriptionInvoice($user, $subscriptionId)
    {
        $subscription = ProSubscription::where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $invoiceNumber = 'PP-ABO-' . str_pad($subscription->id, 6, '0', STR_PAD_LEFT);
        $date = $subscription->starts_at->format('d/m/Y');
        $amount = (float) $subscription->amount;
        $planLabel = $subscription->getPlanLabel();
        $itemDescription = 'Abonnement Pro Lunamars - ' . $planLabel;
        $period = $subscription->starts_at->format('d/m/Y') . ' au ' . $subscription->ends_at->format('d/m/Y');
        $itemDetail = 'Période : ' . $period;
        $transactionRef = null;

        $pdf = Pdf::loadView('invoices.purchase-invoice-pdf', [
            'user' => $user,
            'invoiceNumber' => $invoiceNumber,
            'date' => $date,
            'amount' => $amount,
            'itemDescription' => $itemDescription,
            'itemDetail' => $itemDetail,
            'transactionRef' => $transactionRef,
        ]);

        return $pdf->download('Facture-' . $invoiceNumber . '.pdf');
    }
}
