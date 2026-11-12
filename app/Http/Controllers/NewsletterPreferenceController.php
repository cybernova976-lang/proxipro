<?php

namespace App\Http\Controllers;

use App\Models\ManagedEmail;
use App\Models\User;
use App\Services\DemandDraftReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Désinscription sans connexion, depuis le lien signé présent dans chaque
 * e-mail : newsletters et rappels de demande inachevée. Le GET affiche une
 * confirmation sans rien modifier ; le POST désinscrit (formulaire de la page
 * ou bouton « Se désabonner » de la messagerie, RFC 8058).
 */
class NewsletterPreferenceController extends Controller
{
    private const TEXTS = [
        'newsletter' => [
            'page' => 'Mes newsletters',
            'question' => 'Arrêter les newsletters ?',
            'explanation' => 'Vous pouvez vous désinscrire sans vous connecter. Votre compte et vos notifications d’activité restent disponibles.',
            'button' => 'Me désinscrire des newsletters',
            'done_title' => 'Vous êtes désinscrit des newsletters.',
            'done_text' => 'Aucune nouvelle newsletter ne vous sera envoyée. Vos notifications d’activité et votre compte restent disponibles.',
        ],
        DemandDraftReminderService::TYPE => [
            'page' => 'Rappels de demande inachevée',
            'question' => 'Arrêter les rappels de demande inachevée ?',
            'explanation' => 'Vous pouvez les arrêter sans vous connecter. Votre brouillon, votre compte et vos autres notifications restent disponibles.',
            'button' => 'Ne plus recevoir ces rappels',
            'done_title' => 'Vous ne recevrez plus de rappels de demande inachevée.',
            'done_text' => 'Votre brouillon reste disponible. Vous pouvez réactiver ces rappels à tout moment dans vos préférences.',
        ],
    ];

    public function show(Request $request, User $user)
    {
        return $this->page($request, 'newsletter', ! $user->newsletter_subscribed);
    }

    public function unsubscribe(Request $request, User $user)
    {
        $this->optOut($user, 'newsletter', ['newsletter_subscribed' => false]);

        return redirect()->to($request->fullUrl());
    }

    public function showDraftReminders(Request $request, User $user)
    {
        return $this->page($request, DemandDraftReminderService::TYPE, ! $user->draft_reminders_enabled);
    }

    public function unsubscribeDraftReminders(Request $request, User $user)
    {
        $this->optOut($user, DemandDraftReminderService::TYPE, ['draft_reminders_enabled' => false]);

        return redirect()->to($request->fullUrl());
    }

    private function page(Request $request, string $type, bool $unsubscribed)
    {
        return response()->view('settings.email-unsubscribe', [
            'unsubscribeUrl' => $request->fullUrl(),
            'unsubscribed' => $unsubscribed,
            'texts' => self::TEXTS[$type],
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    /** Coupe la communication et annule les envois de ce type encore en attente. */
    private function optOut(User $user, string $type, array $preference): void
    {
        DB::transaction(function () use ($user, $type, $preference) {
            $user->update($preference);
            ManagedEmail::where('user_id', $user->id)->where('type', $type)
                ->whereIn('status', [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED])
                ->update(['status' => ManagedEmail::STATUS_CANCELLED]);
        });
    }
}
