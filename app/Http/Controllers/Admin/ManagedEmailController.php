<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ManagedEmailMail;
use App\Models\ManagedEmail;
use App\Models\User;
use App\Support\ManagedEmailContent;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ManagedEmailController extends Controller
{
    private function recipients()
    {
        return User::query()->where('newsletter_subscribed', true)
            ->where('email_notifications', true)->whereNotNull('email_verified_at')
            ->where('is_active', true);
    }

    public function create()
    {
        return view('admin.emails.create', ['recipients' => $this->recipients()
            ->orderBy('name')->get(['id', 'name', 'email'])]);
    }

    public function store(Request $request)
    {
        $blocks = ManagedEmailContent::read($request);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'cta_label' => ['nullable', 'required_with:cta_url', 'string', 'max:80'],
            'cta_url' => ['nullable', 'required_with:cta_label', 'url:http,https', 'max:2000'],
            'audience' => ['required', Rule::in(['all', 'selected'])],
            'recipients' => ['required_if:audience,selected', 'array'],
            'recipients.*' => ['integer', 'distinct'],
            'images' => ['nullable', 'array', 'list', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
        $recipients = $this->recipients()
            ->when($data['audience'] === 'selected', fn ($q) => $q->whereIn('id', $data['recipients']))->get();
        if ($recipients->isEmpty() || ($data['audience'] === 'selected' && $recipients->count() !== count($data['recipients']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['recipients' => 'Sélectionnez des destinataires éligibles. Actualisez la liste si leurs préférences ont changé.']);
        }
        $paths = [];
        try {
            DB::transaction(function () use ($request, $data, $recipients, $blocks, &$paths) {
                $batch = (string) Str::uuid();
                foreach ($recipients as $recipient) {
                    $images = [];
                    foreach ($request->file('images', []) as $image) {
                        $images[] = $paths[] = $image->store('managed-emails', config('filesystems.default', 'public'));
                    }
                    ManagedEmail::create([
                        ...collect($data)->only(['subject', 'eyebrow', 'headline', 'body', 'cta_label', 'cta_url'])->all(),
                        'user_id' => $recipient->id, 'recipient_email' => $recipient->email,
                        'recipient_name' => $recipient->name, 'source_key' => $batch.':'.$recipient->id,
                        'type' => 'newsletter', 'status' => ManagedEmail::STATUS_PENDING,
                        'image_paths' => $images, 'metadata' => ['batch' => $batch, 'created_by' => $request->user()->id,
                            ...($blocks !== null ? ['content_blocks' => ManagedEmailContent::resolve($blocks, $images)] : [])],
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk(config('filesystems.default', 'public'))->delete($paths);
            throw $exception;
        }
        return redirect()->route('admin.emails.index')->with('success', $recipients->count().' message(s) préparé(s). Vous pouvez maintenant vérifier et valider chaque envoi.');
    }

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

        $blocks = ManagedEmailContent::read($request, $managedEmail->image_paths ?? []);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'url', 'max:2000'],
            'images' => ['nullable', 'array', 'list', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
        ]);

        if ($blocks !== null) {
            $uploaded = [];
            try {
                foreach ($request->file('images', []) as $image) {
                    $uploaded[] = $image->store('managed-emails', config('filesystems.default', 'public'));
                }
                $resolved = ManagedEmailContent::resolve($blocks, $uploaded, $managedEmail->image_paths ?? []);
                $paths = collect($resolved)->where('type', 'image')->pluck('path')->values()->all();
                $removed = array_diff($managedEmail->image_paths ?? [], $paths);
                $managedEmail->update([
                    ...collect($validated)->only(['subject', 'eyebrow', 'headline', 'body'])->all(),
                    'cta_label' => null,
                    'cta_url' => null,
                    'image_paths' => $paths,
                    'metadata' => [...($managedEmail->metadata ?? []), 'content_blocks' => $resolved],
                    'status' => ManagedEmail::STATUS_PENDING, 'failure_message' => null,
                ]);
            } catch (\Throwable $exception) {
                Storage::disk(config('filesystems.default', 'public'))->delete($uploaded);
                throw $exception;
            }
            Storage::disk(config('filesystems.default', 'public'))->delete(array_values($removed));
            return back()->with('success', 'Mise en page enregistrée. Vérifiez le message avant de valider son envoi.');
        }

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
            if ($email->type === 'newsletter') {
                abort_unless($this->recipients()->whereKey($email->user_id)->where('email', $email->recipient_email)->exists(), 409, 'Ce destinataire ne souhaite plus recevoir cette communication.');
            }
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
