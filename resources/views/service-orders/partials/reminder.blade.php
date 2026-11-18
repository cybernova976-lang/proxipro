@php($reminder = $order->serviceReminder)
<details class="service-reminder" @if($errors->any() && (int) old('service_order_id') === $order->id) open @endif>
    <summary>
        <span class="service-reminder-summary">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            {{ $reminder ? 'Mon rappel de service' : 'Me rappeler ce service' }}
        </span>
        @if($reminder)
            <span class="service-reminder-state {{ $reminder->is_active ? '' : 'is-paused' }}">
                {{ $reminder->is_active ? 'Actif' : ($reminder->frequency === 'once' && $reminder->last_sent_at ? 'Terminé' : 'En pause') }}
            </span>
        @else
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
        @endif
    </summary>
    <div class="service-reminder-body">
        <p class="service-reminder-explainer">
            Choisissez vous-même la date. Prokejem vous préviendra seulement : aucune annonce, réservation ou paiement ne sera créé automatiquement.
        </p>
        @if($errors->any() && (int) old('service_order_id') === $order->id)
            <div class="alert alert-danger py-2 px-3 small" role="alert">
                Corrigez les champs indiqués avant d’enregistrer le rappel.
            </div>
        @endif
        <form action="{{ $reminder ? route('service-reminders.update', $reminder) : route('service-reminders.store', $order) }}" method="POST">
            @csrf
            @if($reminder) @method('PUT') @endif
            <input type="hidden" name="service_order_id" value="{{ $order->id }}">
            <div class="service-reminder-fields">
                <div class="service-reminder-field">
                    <label for="reminder-date-{{ $order->id }}">Prochain rappel</label>
                    <small class="d-block text-muted mb-1">Heure {{ config('app.timezone') }}</small>
                    <input id="reminder-date-{{ $order->id }}" type="datetime-local" name="next_reminder_at" class="{{ $errors->has('next_reminder_at') && (int) old('service_order_id') === $order->id ? 'is-invalid' : '' }}"
                        min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                        value="{{ old('service_order_id') == $order->id ? old('next_reminder_at') : ($reminder?->next_reminder_at?->format('Y-m-d\TH:i') ?? now()->addMonth()->format('Y-m-d\TH:i')) }}"
                        required>
                    @if($errors->has('next_reminder_at') && (int) old('service_order_id') === $order->id)
                        <div class="invalid-feedback d-block">{{ $errors->first('next_reminder_at') }}</div>
                    @endif
                </div>
                <div class="service-reminder-field">
                    <label for="reminder-frequency-{{ $order->id }}">Fréquence</label>
                    <select id="reminder-frequency-{{ $order->id }}" name="frequency" class="{{ $errors->has('frequency') && (int) old('service_order_id') === $order->id ? 'is-invalid' : '' }}" required>
                        @foreach([
                            'once' => 'Une seule fois',
                            'monthly' => 'Tous les mois',
                            'quarterly' => 'Tous les 3 mois',
                            'semiannual' => 'Tous les 6 mois',
                            'yearly' => 'Tous les ans',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected((old('service_order_id') == $order->id ? old('frequency') : ($reminder?->frequency ?? 'once')) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('frequency') && (int) old('service_order_id') === $order->id)
                        <div class="invalid-feedback d-block">{{ $errors->first('frequency') }}</div>
                    @endif
                </div>
            </div>
            <label class="service-reminder-email">
                <input type="checkbox" name="send_email" value="1" @checked(old('service_order_id') == $order->id ? old('send_email') : ($reminder?->send_email ?? false))>
                <span>M’envoyer aussi un e-mail (en plus de la notification dans Prokejem).</span>
            </label>
            @if(! auth()->user()->email_notifications)
                <p class="service-reminder-history mb-2">Les e-mails sont désactivés dans vos <a href="{{ route('settings.index') }}#notifications">préférences de notification</a>.</p>
            @endif
            <div class="service-reminder-controls">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-check me-1" aria-hidden="true"></i>{{ $reminder ? 'Enregistrer les modifications' : 'Programmer le rappel' }}
                </button>
            </div>
        </form>
        @if($reminder)
            <div class="service-reminder-controls mt-2">
                <form action="{{ route('service-reminders.toggle', $reminder) }}" method="POST">
                    @csrf
                    <input type="hidden" name="service_order_id" value="{{ $order->id }}">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="fas {{ $reminder->is_active ? 'fa-pause' : 'fa-play' }} me-1" aria-hidden="true"></i>{{ $reminder->is_active ? 'Mettre en pause' : 'Réactiver' }}
                    </button>
                </form>
                <form action="{{ route('service-reminders.destroy', $reminder) }}" method="POST" onsubmit="return confirm('Supprimer définitivement ce rappel ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1" aria-hidden="true"></i>Supprimer</button>
                </form>
            </div>
            <p class="service-reminder-history">
                {{ $reminder->is_active ? 'Prochain rappel' : 'Date programmée' }} : {{ $reminder->next_reminder_at->format('d/m/Y à H:i') }} · {{ $reminder->frequency_label }}
                @if($reminder->last_sent_at)
                    · Dernière notification dans Prokejem : {{ $reminder->last_sent_at->format('d/m/Y à H:i') }} ({{ $reminder->reminders_sent_count }} au total)
                @endif
            </p>
            @if(in_array($reminder->last_email_status, ['failed', 'pending'], true))
                <p class="service-reminder-history" role="status">L’envoi du dernier e-mail n’a pas pu être confirmé. Le rappel est disponible dans vos notifications Prokejem.</p>
            @endif
        @endif
    </div>
</details>
