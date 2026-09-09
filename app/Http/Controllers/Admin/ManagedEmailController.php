<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ManagedEmailMail;
use App\Models\ManagedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ManagedEmailController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $emails = ManagedEmail::with(['user', 'approver'])
            ->when(in_array($status, ['pending', 'sending', 'sent', 'cancelled', 'failed'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.emails.index', compact('emails', 'status'));
    }

    public function show(ManagedEmail $managedEmail)
    {
        $managedEmail->load(['user', 'approver']);

        return view('admin.emails.show', compact('managedEmail'));
    }

    public function preview(ManagedEmail $managedEmail)
    {
        return response((new ManagedEmailMail($managedEmail))->render())
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function update(Request $request, ManagedEmail $managedEmail)
    {
        abort_unless(in_array($managedEmail->status, [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED], true), 409);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'url', 'max:2000'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
        ]);

        $paths = collect($managedEmail->image_paths ?? []);
        $removals = collect($validated['remove_images'] ?? [])->intersect($paths);
        foreach ($removals as $path) {
            Storage::disk(config('filesystems.default', 'public'))->delete($path);
        }
        $paths = $paths->diff($removals)->values();

        foreach ($request->file('images', []) as $image) {
            if ($paths->count() >= 3) {
                break;
            }
            $paths->push($image->store('managed-emails', config('filesystems.default', 'public')));
        }

        $managedEmail->update([
            ...collect($validated)->only(['subject', 'eyebrow', 'headline', 'body', 'cta_label', 'cta_url'])->all(),
            'image_paths' => $paths->all(),
            'status' => ManagedEmail::STATUS_PENDING,
            'failure_message' => null,
        ]);

        return back()->with('success', 'Le message a été enregistré. Vérifiez l’aperçu avant de valider l’envoi.');
    }

    public function approve(Request $request, ManagedEmail $managedEmail)
    {
        $email = DB::transaction(function () use ($managedEmail, $request) {
            $email = ManagedEmail::lockForUpdate()->findOrFail($managedEmail->id);
            abort_unless(in_array($email->status, [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED], true), 409);
            $email->update([
                'status' => ManagedEmail::STATUS_SENDING,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'failure_message' => null,
            ]);

            return $email;
        });

        try {
            Mail::to($email->recipient_email, $email->recipient_name)->send(new ManagedEmailMail($email));
            $email->update(['status' => ManagedEmail::STATUS_SENT, 'sent_at' => now()]);
        } catch (\Throwable $exception) {
            $email->update([
                'status' => ManagedEmail::STATUS_FAILED,
                'failure_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            report($exception);

            return back()->with('error', 'L’envoi a échoué. Le message reste disponible pour correction ou nouvel essai.');
        }

        return redirect()->route('admin.emails.show', $email)->with('success', 'Message validé et envoyé.');
    }

    public function cancel(ManagedEmail $managedEmail)
    {
        abort_unless(in_array($managedEmail->status, [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED], true), 409);
        $managedEmail->update(['status' => ManagedEmail::STATUS_CANCELLED]);

        return redirect()->route('admin.emails.index')->with('success', 'Envoi annulé. Aucun e-mail n’a été transmis.');
    }
}
