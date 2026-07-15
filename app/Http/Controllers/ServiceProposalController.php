<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Notifications\ServiceProposalReceivedNotification;
use App\Notifications\ServiceProposalStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceProposalController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $sentProposals = ServiceProposal::with(['ad.user', 'serviceOrder'])
            ->where('provider_id', $user->id)
            ->latest()
            ->paginate(12, ['*'], 'sent_page');

        $receivedProposals = ServiceProposal::with(['ad', 'provider', 'serviceOrder'])
            ->whereHas('ad', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate(12, ['*'], 'received_page');

        return view('proposals.index', compact('sentProposals', 'receivedProposals'));
    }

    public function store(Request $request, Ad $ad)
    {
        $provider = Auth::user();

        abort_unless($ad->service_type === 'demande' && $ad->status === 'active', 422);
        abort_if($ad->user_id === $provider->id, 403);

        if (! $provider->isProfessionnel() && ! $provider->isServiceProvider()) {
            return back()->with('error', 'Activez votre profil prestataire avant d envoyer une proposition.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:999999.99',
            'message' => 'required|string|min:20|max:1500',
            'scheduled_for' => 'nullable|date|after_or_equal:today',
        ]);

        $existing = ServiceProposal::where('ad_id', $ad->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing && $existing->status !== ServiceProposal::STATUS_PENDING) {
            return back()->with('error', 'Cette proposition a deja ete traitee et ne peut plus etre modifiee.');
        }

        $proposal = ServiceProposal::updateOrCreate(
            ['ad_id' => $ad->id, 'provider_id' => $provider->id],
            [
                'amount' => round((float) $validated['amount'], 2),
                'message' => $validated['message'],
                'scheduled_for' => $validated['scheduled_for'] ?? null,
                'status' => ServiceProposal::STATUS_PENDING,
            ]
        );

        $proposal->load(['ad', 'provider']);
        $ad->user?->notify(new ServiceProposalReceivedNotification($proposal));

        return redirect()->route('proposals.index')
            ->with('success', 'Votre proposition chiffree a bien ete envoyee.');
    }

    public function accept(ServiceProposal $proposal)
    {
        $client = Auth::user();
        abort_unless($proposal->ad->user_id === $client->id, 403);

        $refusedProposals = collect();

        $accepted = DB::transaction(function () use ($proposal, $client, &$refusedProposals) {
            $proposal = ServiceProposal::with(['ad', 'provider'])
                ->lockForUpdate()
                ->findOrFail($proposal->id);

            abort_unless($proposal->status === ServiceProposal::STATUS_PENDING, 422);

            $commissionRate = max(0, min(100, (float) config('marketplace.commission_percent', 10))) / 100;
            $amount = round((float) $proposal->amount, 2);
            $commissionAmount = round($amount * $commissionRate, 2);

            $order = ServiceOrder::create([
                'order_number' => 'CMD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'ad_id' => $proposal->ad_id,
                'buyer_id' => $client->id,
                'seller_id' => $proposal->provider_id,
                'amount' => $amount,
                'commission_amount' => $commissionAmount,
                'seller_amount' => round($amount - $commissionAmount, 2),
                'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
                'payment_status' => ServiceOrder::PAYMENT_AWAITING,
                'message' => $proposal->message,
                'scheduled_for' => $proposal->scheduled_for,
                'accepted_at' => now(),
                'metadata' => [
                    'source' => 'service_proposal',
                    'service_proposal_id' => $proposal->id,
                    'ad_title' => $proposal->ad->title,
                ],
            ]);

            $proposal->update([
                'status' => ServiceProposal::STATUS_ACCEPTED,
                'service_order_id' => $order->id,
                'responded_at' => now(),
            ]);

            $refusedProposals = ServiceProposal::with(['ad', 'provider'])
                ->where('ad_id', $proposal->ad_id)
                ->whereKeyNot($proposal->id)
                ->where('status', ServiceProposal::STATUS_PENDING)
                ->get();

            ServiceProposal::whereIn('id', $refusedProposals->pluck('id'))->update([
                'status' => ServiceProposal::STATUS_REFUSED,
                'responded_at' => now(),
            ]);

            return $proposal->fresh(['ad', 'provider', 'serviceOrder']);
        });

        $accepted->provider?->notify(new ServiceProposalStatusNotification($accepted));
        $refusedProposals->each(function (ServiceProposal $refused) {
            $refused->status = ServiceProposal::STATUS_REFUSED;
            $refused->provider?->notify(new ServiceProposalStatusNotification($refused));
        });

        return redirect()->route('service-orders.index')
            ->with('success', 'Proposition acceptee. La commande securisee est prete pour le paiement.');
    }

    public function refuse(ServiceProposal $proposal)
    {
        abort_unless($proposal->ad->user_id === Auth::id(), 403);
        abort_unless($proposal->status === ServiceProposal::STATUS_PENDING, 422);

        $proposal->update([
            'status' => ServiceProposal::STATUS_REFUSED,
            'responded_at' => now(),
        ]);

        $proposal->load(['ad', 'provider']);
        $proposal->provider?->notify(new ServiceProposalStatusNotification($proposal));

        return back()->with('success', 'La proposition a ete refusee.');
    }

    public function withdraw(ServiceProposal $proposal)
    {
        abort_unless($proposal->provider_id === Auth::id(), 403);
        abort_unless($proposal->status === ServiceProposal::STATUS_PENDING, 422);

        $proposal->update([
            'status' => ServiceProposal::STATUS_WITHDRAWN,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Votre proposition a ete retiree.');
    }
}
