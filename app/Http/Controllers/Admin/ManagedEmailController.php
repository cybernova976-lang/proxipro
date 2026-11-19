<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendManagedEmail;
use App\Mail\ManagedEmailMail;
use App\Models\ManagedEmail;
use App\Models\User;
use App\Services\DemandDraftReminderService;
use App\Support\LaunchEmailTemplates;
use App\Support\ManagedEmailAudience;
use App\Support\ManagedEmailContent;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ManagedEmailController extends Controller
{
    private function recipients(string $type = ManagedEmailAudience::NEWSLETTER)
    {
        return ManagedEmailAudience::query($type);
    }

    /** Le comparatif des offres est un argument de vente : jamais dans un message de service. */
    private function refuseSalesContentInServiceMessage(string $type, ?array $blocks): void
    {
        if ($type === ManagedEmailAudience::SERVICE && collect($blocks)->contains(fn ($block) => ($block['type'] ?? null) === 'comparison')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content_layout' => 'Le comparatif des offres est un argument de vente : retirez-le d’un message de service, ou envoyez-le comme newsletter.',
            ]);
        }
    }

    public function create(Request $request)
    {
        $model = $request->query('modele');
        $template = LaunchEmailTemplates::get(is_string($model) ? $model : null);
        $type = ManagedEmailAudience::normalize($template['type'] ?? $request->query('type'));
        $recipients = $this->recipients($type)->orderBy('name')
            ->get(['id', 'name', 'email', 'account_type', 'user_type']);

        return view('admin.emails.create', [
            'emailType' => $type,
            'template' => $template,
            'templateBlocks' => $template['blocks'] ?? null,
            'templates' => LaunchEmailTemplates::all(),
            'unverifiedCount' => $type === ManagedEmailAudience::SERVICE ? ManagedEmailAudience::unverifiedCount() : 0,
            'recipients' => $recipients,
            // Comptes joignables de chaque type, comptés sur la liste affichée.
            'professionalCount' => $recipients->filter(fn (User $user) => $user->isRegisteredAsProfessional())->count(),
        ]);
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
            'type' => ['nullable', Rule::in(ManagedEmailAudience::TYPES)],
        ]);
        $type = ManagedEmailAudience::normalize($data['type'] ?? null);
        $this->refuseSalesContentInServiceMessage($type, $blocks);
        $recipients = $this->recipients($type)
            ->when($data['audience'] === 'selected', fn ($q) => $q->whereIn('id', $data['recipients']))->get();
        if ($recipients->isEmpty() || ($data['audience'] === 'selected' && $recipients->count() !== count($data['recipients']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['recipients' => 'Sélectionnez des destinataires éligibles. Actualisez la liste si leurs préférences ont changé.']);
        }
        $paths = [];
        try {
            DB::transaction(function () use ($request, $data, $recipients, $blocks, $type, &$paths) {
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
                        'type' => $type, 'status' => ManagedEmail::STATUS_PENDING,
                        'image_paths' => $images, 'metadata' => ['batch' => $batch, 'created_by' => $request->user()->id,
                            ...($blocks !== null ? ['content_blocks' => ManagedEmailContent::resolve($blocks, $images)] : [])],
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk(config('filesystems.default', 'public'))->delete($paths);
            throw $exception;
        }
        return redirect()->route('admin.emails.index')->with('success', $recipients->count().' message(s) préparé(s). Rien n’est parti : vérifiez un message, puis validez l’envoi du lot.');
    }

    public function index(Request $request, DemandDraftReminderService $reminders)
    {
        $status = $request->string('status')->toString();
        $emails = ManagedEmail::with(['user', 'approver'])
            ->when(in_array($status, ['pending', 'sending', 'sent', 'cancelled', 'failed'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Lots préparés depuis le studio et encore à valider (au moins deux messages).
        $batches = ManagedEmail::query()->whereIn('type', ManagedEmailAudience::TYPES)
            ->whereIn('status', [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED])
            ->orderBy('id')->get(['id', 'source_key', 'subject', 'type', 'created_at'])
            ->groupBy(fn (ManagedEmail $email) => Str::before($email->source_key, ':'))
            ->filter(fn ($group, $batch) => Str::isUuid((string) $batch) && $group->count() > 1)
            ->map(fn ($group, $batch) => [
                'batch' => (string) $batch, 'subject' => $group->first()->subject, 'type' => $group->first()->type,
                'count' => $group->count(), 'first' => $group->first(), 'created_at' => $group->first()->created_at,
            ])->values();

        return view('admin.emails.index', [
            'batches' => $batches,
            'sendingCount' => ManagedEmail::where('status', ManagedEmail::STATUS_SENDING)->count(),
            'emails' => $emails,
            'status' => $status,
            'dueDrafts' => $reminders->due()->count(),
        ]);
    }

    /** Envoie tout de suite les rappels dus, sans attendre le prochain passage du planificateur. */
    public function sendDraftReminders(DemandDraftReminderService $reminders)
    {
        $result = $reminders->run(limit: 50);
        $parts = array_filter([
            $result['sent'] ? $result['sent'].' rappel(s) envoyé(s)' : null,
            $result['cancelled'] ? $result['cancelled'].' annulé(s) car devenu(s) inutile(s)' : null,
            $result['failed'] ? $result['failed'].' échec(s), nouvel essai automatique dans une heure' : null,
        ]);
        $message = $parts ? ucfirst(implode(', ', $parts)).'.' : 'Aucun rappel de brouillon n’était dû.';

        return redirect()->route('admin.emails.index')->with($result['failed'] ? 'error' : 'success', $message);
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
        $this->refuseSalesContentInServiceMessage((string) $managedEmail->type, $blocks);

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

    public function approve(Request $request, ManagedEmail $managedEmail, DemandDraftReminderService $reminders)
    {
        $email = DB::transaction(function () use ($managedEmail, $request, $reminders) {
            $email = ManagedEmail::lockForUpdate()->findOrFail($managedEmail->id);
            abort_unless(in_array($email->status, [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED], true), 409);
            if ($email->type === ManagedEmailAudience::NEWSLETTER) {
                abort_unless(ManagedEmailAudience::stillReachable($email), 409, 'Ce destinataire ne souhaite plus recevoir cette communication.');
            }
            if ($email->type === ManagedEmailAudience::SERVICE) {
                abort_unless(ManagedEmailAudience::stillReachable($email), 409, 'Ce compte n’est plus actif ou son adresse a changé.');
            }
            if ($email->type === DemandDraftReminderService::TYPE) {
                abort_unless($reminders->stillRelevant($email), 409, 'Ce rappel n’est plus pertinent ou le destinataire l’a désactivé.');
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

    /** Messages d'un lot du studio encore à valider. */
    private function pendingOfBatch(string $batch)
    {
        return ManagedEmail::query()->whereIn('type', ManagedEmailAudience::TYPES)
            ->where('source_key', 'like', $batch.':%')
            ->whereIn('status', [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED]);
    }

    /**
     * Valide tout un lot d'un seul geste. Les messages partent ensuite un par un,
     * en arrière-plan et espacés, pour rester sous les limites du service d'envoi.
     */
    public function approveBatch(Request $request, string $batch)
    {
        $ids = DB::transaction(function () use ($batch, $request) {
            $ids = $this->pendingOfBatch($batch)->lockForUpdate()->orderBy('id')->pluck('id');
            ManagedEmail::whereIn('id', $ids)->update([
                'status' => ManagedEmail::STATUS_SENDING,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'failure_message' => null,
            ]);

            return $ids;
        });
        if ($ids->isEmpty()) {
            return redirect()->route('admin.emails.index')->with('error', 'Ce lot ne contient plus aucun message à valider.');
        }

        $interval = max(0, (int) config('mail.managed_batch_interval', 20));
        foreach ($ids->values() as $position => $id) {
            SendManagedEmail::dispatch($id)->delay(now()->addSeconds($position * $interval));
        }
        $minutes = (int) ceil($ids->count() * $interval / 60);

        return redirect()->route('admin.emails.index', ['status' => 'sending'])->with('success', $ids->count().' message(s) validé(s). '
            .'L’envoi se fait en arrière-plan, un message toutes les '.$interval.' secondes'
            .($minutes > 1 ? ' : comptez environ '.$minutes.' minutes.' : '.')
            .' Les préférences de chaque destinataire sont relues au moment de l’envoi.');
    }

    public function cancelBatch(string $batch)
    {
        $cancelled = $this->pendingOfBatch($batch)->update(['status' => ManagedEmail::STATUS_CANCELLED]);

        return redirect()->route('admin.emails.index')->with($cancelled ? 'success' : 'error', $cancelled
            ? $cancelled.' envoi(s) annulé(s). Aucun e-mail n’a été transmis.'
            : 'Ce lot ne contient plus aucun message à annuler.');
    }

    public function cancel(ManagedEmail $managedEmail)
    {
        abort_unless(in_array($managedEmail->status, [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED], true), 409);
        $managedEmail->update(['status' => ManagedEmail::STATUS_CANCELLED]);

        return redirect()->route('admin.emails.index')->with('success', 'Envoi annulé. Aucun e-mail n’a été transmis.');
    }
}
