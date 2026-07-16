<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlockedEmailController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlockedEmail::query()->with(['blockedBy', 'sourceUser']);

        if ($request->filled('search')) {
            $search = BlockedEmail::normalize((string) $request->input('search'));
            $query->where('email', 'like', '%'.$search.'%');
        }

        $blockedEmails = $query->latest()->paginate(20)->withQueryString();

        return view('admin.blocked-emails.index', [
            'blockedEmails' => $blockedEmails,
            'blockedEmailsCount' => BlockedEmail::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => BlockedEmail::normalize((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('blocked_emails', 'email'),
            ],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'email.required' => 'Saisissez l’adresse e-mail à bloquer.',
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà bloquée.',
            'reason.max' => 'Le motif ne peut pas dépasser 1 000 caractères.',
        ]);

        BlockedEmail::create([
            'email' => $validated['email'],
            'reason' => $validated['reason'] ?? null,
            'blocked_by' => $request->user()->id,
        ]);

        return back()->with('success', 'L’adresse '.$validated['email'].' est maintenant bloquée pour toute nouvelle inscription.');
    }

    public function storeFromDeletedAccount(Request $request, int $id): RedirectResponse
    {
        $deletedUser = User::onlyTrashed()->findOrFail($id);
        $deletionLog = DB::table('deleted_accounts')->where('user_id', $deletedUser->id)->first();
        $email = BlockedEmail::normalize((string) ($deletionLog->email ?? $deletedUser->email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || str_ends_with($email, '@deleted.local')) {
            return back()->with('error', 'L’adresse e-mail originale de ce compte n’est plus disponible. Ajoutez-la manuellement depuis la liste des e-mails bloqués.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $blockedEmail = BlockedEmail::updateOrCreate(
            ['email' => $email],
            [
                'reason' => $validated['reason'] ?? 'Blocage décidé depuis l’historique des comptes supprimés.',
                'blocked_by' => $request->user()->id,
                'source_user_id' => $deletedUser->id,
            ]
        );

        $message = $blockedEmail->wasRecentlyCreated
            ? 'L’adresse '.$email.' est maintenant bloquée.'
            : 'Le blocage de l’adresse '.$email.' a été mis à jour.';

        return back()->with('success', $message);
    }

    public function destroy(BlockedEmail $blockedEmail): RedirectResponse
    {
        $email = $blockedEmail->email;
        $blockedEmail->delete();

        return back()->with('success', 'L’adresse '.$email.' est de nouveau autorisée à créer un compte.');
    }
}
