<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\ServiceReminder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceReminderController extends Controller
{
    public function store(Request $request, ServiceOrder $serviceOrder)
    {
        $this->authorizeCompletedBuyerOrder($request, $serviceOrder);
        $validated = $this->validatedReminder($request);

        ServiceReminder::updateOrCreate(
            ['service_order_id' => $serviceOrder->id],
            [
                'user_id' => $request->user()->id,
                ...$validated,
                'send_email' => $request->boolean('send_email'),
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Votre rappel a été enregistré. Aucune annonce ne sera publiée automatiquement.');
    }

    public function update(Request $request, ServiceReminder $serviceReminder)
    {
        $this->authorizeOwner($request, $serviceReminder);
        $this->authorizeCompletedBuyerOrder($request, $serviceReminder->serviceOrder);
        $validated = $this->validatedReminder($request);

        $serviceReminder->update([
            ...$validated,
            'send_email' => $request->boolean('send_email'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Votre rappel a été mis à jour.');
    }

    public function toggle(Request $request, ServiceReminder $serviceReminder)
    {
        $this->authorizeOwner($request, $serviceReminder);
        if (! $serviceReminder->is_active) {
            $this->authorizeCompletedBuyerOrder($request, $serviceReminder->serviceOrder);
            if ($serviceReminder->next_reminder_at->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'next_reminder_at' => 'Choisissez une nouvelle date future et enregistrez le rappel pour le réactiver.',
                ]);
            }
        }
        $serviceReminder->update(['is_active' => ! $serviceReminder->is_active]);

        return back()->with('success', $serviceReminder->is_active ? 'Rappel réactivé.' : 'Rappel mis en pause.');
    }

    public function destroy(Request $request, ServiceReminder $serviceReminder)
    {
        $this->authorizeOwner($request, $serviceReminder);
        $serviceReminder->delete();

        return back()->with('success', 'Le rappel a été supprimé.');
    }

    private function validatedReminder(Request $request): array
    {
        return $request->validate([
            'next_reminder_at' => ['required', 'date', 'after:now'],
            'frequency' => ['required', Rule::in(ServiceReminder::FREQUENCIES)],
            'send_email' => ['nullable', 'boolean'],
        ], [
            'next_reminder_at.after' => 'Choisissez une date future pour votre prochain rappel.',
            'next_reminder_at.date' => 'Indiquez une date et une heure valides.',
        ]);
    }

    private function authorizeCompletedBuyerOrder(Request $request, ServiceOrder $serviceOrder): void
    {
        abort_unless(
            $serviceOrder->buyer_id === $request->user()->id
                && $serviceOrder->status === ServiceOrder::STATUS_COMPLETED,
            403
        );
    }

    private function authorizeOwner(Request $request, ServiceReminder $serviceReminder): void
    {
        abort_unless($serviceReminder->user_id === $request->user()->id, 403);
    }
}
