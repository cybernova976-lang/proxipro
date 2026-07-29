<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeWebhookEvent;
use App\Models\Transaction;
use App\Services\StripeWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Event;
use Stripe\Stripe;

class PaymentReconciliationController extends Controller
{
    public function __construct(private readonly StripeWebhookService $webhooks) {}

    public function index(): View
    {
        $events = StripeWebhookEvent::query()->latest()->paginate(30, ['*'], 'events');
        $transactions = Transaction::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(30, ['*'], 'transactions');

        return view('admin.payments.index', compact('events', 'transactions'));
    }

    public function retry(StripeWebhookEvent $stripeWebhookEvent): RedirectResponse
    {
        if ($stripeWebhookEvent->status !== StripeWebhookEvent::STATUS_FAILED) {
            return back()->with('error', 'Seuls les webhooks en échec peuvent être relancés.');
        }

        if (! config('services.stripe.secret')) {
            return back()->with('error', 'Stripe n’est pas configuré sur ce serveur.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $event = Event::retrieve($stripeWebhookEvent->event_id);
            $result = $this->webhooks->process($event, true);

            return back()->with('success', 'Webhook relancé avec succès ('.$result.').');
        } catch (\Throwable $exception) {
            Log::error('Manual Stripe webhook retry failed.', [
                'event_id' => $stripeWebhookEvent->event_id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'La relance a échoué. Consultez le détail enregistré puis réessayez.');
        }
    }
}
