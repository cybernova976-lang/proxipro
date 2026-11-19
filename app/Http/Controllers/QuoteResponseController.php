<?php

namespace App\Http\Controllers;

use App\Models\ProQuote;
use App\Notifications\QuoteAnsweredNotification;
use App\Support\SafeNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Réponse du client à un devis, sans compte : il ouvre son lien personnel,
 * lit le devis, puis l'accepte (« Bon pour accord ») ou le refuse. La réponse
 * est horodatée, porte le nom saisi, et le prestataire est prévenu.
 *
 * Seul un devis émis peut être consulté ; seule une réponse est possible, tant
 * que le devis est « envoyé » et que sa validité n'est pas dépassée.
 */
class QuoteResponseController extends Controller
{
    public function show(string $token)
    {
        $quote = $this->quote($token);

        return response()
            ->view('quotes.respond', ['quote' => $quote, 'seller' => $this->seller($quote)])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function download(string $token)
    {
        $quote = $this->quote($token);
        $pdf = Pdf::loadView('pro.quote-pdf', ['user' => $quote->user, 'quote' => $quote]);
        $pdf->setPaper('a4');

        return $pdf->download('Devis_'.$quote->quote_number.'.pdf');
    }

    public function store(Request $request, string $token)
    {
        $quote = $this->quote($token);
        $data = $request->validate([
            'decision' => 'required|in:accept,refuse',
            'name' => 'required|string|min:2|max:150',
            'agree' => 'required_if:decision,accept|accepted_if:decision,accept',
        ], [
            'name.required' => 'Indiquez votre nom et votre prénom.',
            'name.min' => 'Indiquez votre nom et votre prénom.',
            'agree.required_if' => 'Cochez la case pour donner votre accord.',
            'agree.accepted_if' => 'Cochez la case pour donner votre accord.',
        ]);

        // Verrou : deux envois simultanés ne produisent qu'une réponse.
        $answered = DB::transaction(function () use ($quote, $data) {
            $locked = ProQuote::query()->lockForUpdate()->findOrFail($quote->id);
            if (! $locked->isAwaitingResponse()) {
                return null;
            }

            $accepted = $data['decision'] === 'accept';
            $locked->update([
                'status' => $accepted ? 'accepted' : 'refused',
                $accepted ? 'accepted_at' : 'refused_at' => now(),
                'response_name' => trim($data['name']),
                'response_channel' => 'online',
                'reminder_enabled' => false,
            ]);

            return $locked;
        });

        if (! $answered) {
            return redirect()->route('quotes.respond', $token)
                ->with('error', 'Ce devis n’attend plus de réponse.');
        }

        if ($answered->user) {
            SafeNotifier::send($answered->user, new QuoteAnsweredNotification($answered));
        }

        return redirect()->route('quotes.respond', $token)->with('success', $answered->status === 'accepted'
            ? 'Votre accord est enregistré. '.$this->seller($answered)['name'].' en est informé.'
            : 'Votre refus est enregistré. '.$this->seller($answered)['name'].' en est informé.');
    }

    private function quote(string $token): ProQuote
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/D', $token) === 1, 404);

        $quote = ProQuote::with('user')->where('public_token', $token)->firstOrFail();
        // Un brouillon n'est jamais consultable par un lien.
        abort_if($quote->status === 'draft' || $quote->issued_at === null || ! $quote->user, 404);

        return $quote;
    }

    /** @return array{name: string} + identité figée à l'émission */
    private function seller(ProQuote $quote): array
    {
        $seller = $quote->seller_snapshot ?: $quote->user->commercialIdentitySnapshot();
        $seller['name'] = (string) ($seller['company_name'] ?? $seller['name'] ?? $quote->user->name);

        return $seller;
    }
}
