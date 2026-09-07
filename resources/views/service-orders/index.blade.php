@extends('layouts.app')

@section('title', 'Commandes securisees - Prokejem')

@push('styles')
<style>
    .service-orders-page { max-width: 1180px; margin: 0 auto; padding: 32px 20px 48px; }
    .service-orders-title { font-size: 1.9rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    .service-orders-subtitle { color: #64748b; margin-bottom: 28px; }
    .service-orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
    .service-order-card { background: white; border: 1px solid #e2e8f0; border-radius: 18px; padding: 20px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); }
    .service-order-top { display:flex; justify-content:space-between; gap:12px; margin-bottom:12px; }
    .service-order-number { font-size: 0.78rem; font-weight: 800; color: #0f766e; letter-spacing: 0.04em; text-transform: uppercase; }
    .service-order-title { margin: 4px 0 0; font-size: 1rem; font-weight: 800; color: #0f172a; }
    .service-order-status { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#ecfeff; color:#155e75; font-size:0.75rem; font-weight:700; }
    .service-order-stats { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin:14px 0; }
    .service-order-stat { background:#f8fafc; border-radius:12px; padding:12px; }
    .service-order-stat-label { font-size:0.72rem; color:#64748b; margin-bottom:4px; }
    .service-order-stat-value { font-size:0.92rem; font-weight:800; color:#0f172a; }
    .service-order-meta { font-size:0.84rem; color:#475569; margin-bottom:12px; }
    .service-order-empty { background:white; border:1px solid #e2e8f0; border-radius:20px; padding:72px 20px; text-align:center; }
    .service-order-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
    .service-order-note { margin-top: 12px; padding: 12px; border-radius: 12px; background:#fff7ed; color:#9a3412; font-size:0.84rem; }
    .service-order-reason { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:0.84rem; min-height:84px; }
    .service-reminder { margin-top:16px; border:1px solid #bfdbfe; border-radius:14px; background:#eff6ff; overflow:hidden; }
    .service-reminder summary { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 14px; color:#1e3a8a; font-size:0.86rem; font-weight:800; cursor:pointer; list-style:none; }
    .service-reminder summary::-webkit-details-marker { display:none; }
    .service-reminder-summary { display:flex; align-items:center; gap:8px; }
    .service-reminder-state { padding:4px 8px; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:0.7rem; white-space:nowrap; }
    .service-reminder-state.is-paused { background:#f1f5f9; color:#64748b; }
    .service-reminder-body { padding:0 14px 14px; border-top:1px solid #dbeafe; }
    .service-reminder-explainer { margin:12px 0; color:#475569; font-size:0.78rem; line-height:1.45; }
    .service-reminder-fields { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1fr); gap:10px; }
    .service-reminder-field label { display:block; margin-bottom:5px; color:#334155; font-size:0.73rem; font-weight:700; }
    .service-reminder-field input,
    .service-reminder-field select { width:100%; min-height:40px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; padding:8px 10px; color:#0f172a; font-size:0.8rem; }
    .service-reminder-email { display:flex; align-items:flex-start; gap:8px; margin:11px 0; color:#334155; font-size:0.76rem; }
    .service-reminder-email input { margin-top:2px; }
    .service-reminder-controls { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .service-reminder-history { margin:10px 0 0; color:#64748b; font-size:0.72rem; }
    @media (max-width: 575.98px) {
        .service-orders-page { padding:22px 14px 110px; }
        .service-orders-grid { grid-template-columns:minmax(0, 1fr); }
        .service-order-stats { grid-template-columns:1fr; gap:8px; }
        .service-reminder-fields { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="service-orders-page">
    <h1 class="service-orders-title"><i class="fas fa-shield-alt me-2" style="color:#0f766e;"></i>Commandes securisees</h1>
    <p class="service-orders-subtitle">Suivez vos commandes acheteur et les demandes recues en tant que vendeur.</p>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    @if(($needsStripeConnectOnboarding ?? false) && auth()->user()?->role !== 'admin')
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap" style="border-radius:16px;">
            <div>
                <strong>Activez Stripe Connect pour recevoir les fonds.</strong><br>
                Tant que le compte vendeur n'est pas finalise, les libérations de fonds restent bloquées.
            </div>
            <a href="{{ route('service-orders.connect.onboarding') }}" class="btn btn-warning text-dark">
                <i class="fas fa-plug me-1"></i>Configurer Stripe Connect
            </a>
        </div>
    @endif

    <div class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-3">Mes commandes</h2>
        @if($ordersAsBuyer->count() > 0)
            <div class="service-orders-grid">
                @foreach($ordersAsBuyer as $order)
                    <div id="order-{{ $order->id }}" class="service-order-card">
                        <div class="service-order-top">
                            <div>
                                <div class="service-order-number">{{ $order->order_number }}</div>
                                <div class="service-order-title">{{ $order->ad->title }}</div>
                            </div>
                            <span class="service-order-status">{{ $order->status_label }}</span>
                        </div>
                        <div class="service-order-stats">
                            <div class="service-order-stat"><div class="service-order-stat-label">Montant</div><div class="service-order-stat-value">{{ number_format((float) $order->amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Commission</div><div class="service-order-stat-value">{{ number_format((float) $order->commission_amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Paiement</div><div class="service-order-stat-value">{{ $order->payment_status_label }}</div></div>
                        </div>
                        <div class="service-order-meta">Vendeur: <strong>{{ $order->seller->name }}</strong></div>
                        @if($order->seller->stripe_connect_account_id)
                            <div class="service-order-meta">Connect vendeur: <strong>{{ $order->seller->stripe_connect_payouts_enabled ? 'actif' : 'en attente' }}</strong></div>
                        @endif
                        @if($order->message)
                            <div class="service-order-meta">Message: {{ $order->message }}</div>
                        @endif
                        @if($order->canBuyerPay())
                            <div class="service-order-actions">
                                <form action="{{ route('service-orders.checkout', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-credit-card me-1"></i>Payer avec Stripe</button>
                                </form>
                            </div>
                        @endif
                        @if($order->canBuyerRelease())
                            <div class="service-order-actions">
                                <form action="{{ route('service-orders.release', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-unlock-alt me-1"></i>Liberer les fonds</button>
                                </form>
                            </div>
                            <form action="{{ route('service-orders.dispute', $order) }}" method="POST" class="mt-2">
                                @csrf
                                <textarea name="reason" class="service-order-reason" placeholder="Expliquez le probleme pour ouvrir un litige." required></textarea>
                                <div class="service-order-actions">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-exclamation-triangle me-1"></i>Ouvrir un litige</button>
                                </div>
                            </form>
                        @endif
                        @if($order->released_at)
                            <div class="service-order-note">Fonds liberes le {{ $order->released_at->format('d/m/Y H:i') }}.</div>
                        @endif
                        @if($order->refunded_at)
                            <div class="service-order-note">Remboursement Stripe emis le {{ $order->refunded_at->format('d/m/Y H:i') }}.</div>
                        @endif
                        @if($order->dispute_reason)
                            <div class="service-order-note">Litige: {{ $order->dispute_reason }}</div>
                        @endif
                        @if($order->refused_reason)
                            <div class="service-order-note">Refus: {{ $order->refused_reason }}</div>
                        @endif
                        @if($order->status === \App\Models\ServiceOrder::STATUS_COMPLETED)
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
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="service-order-empty"><i class="fas fa-receipt mb-3" style="font-size:2.4rem;color:#cbd5e1;"></i><h3 class="h5">Aucune commande envoyee</h3><p class="text-muted mb-0">Declenchez une commande securisee depuis une annonce.</p></div>
        @endif
    </div>

    <div>
        <h2 class="h5 fw-bold text-dark mb-3">Demandes recues</h2>
        @if($ordersAsSeller->count() > 0)
            <div class="service-orders-grid">
                @foreach($ordersAsSeller as $order)
                    <div id="order-{{ $order->id }}" class="service-order-card">
                        <div class="service-order-top">
                            <div>
                                <div class="service-order-number">{{ $order->order_number }}</div>
                                <div class="service-order-title">{{ $order->ad->title }}</div>
                            </div>
                            <span class="service-order-status">{{ $order->status_label }}</span>
                        </div>
                        <div class="service-order-stats">
                            <div class="service-order-stat"><div class="service-order-stat-label">Montant</div><div class="service-order-stat-value">{{ number_format((float) $order->amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Net vendeur</div><div class="service-order-stat-value">{{ number_format((float) $order->seller_amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Paiement</div><div class="service-order-stat-value">{{ $order->payment_status_label }}</div></div>
                        </div>
                        <div class="service-order-meta">Acheteur: <strong>{{ $order->buyer->name }}</strong></div>
                        <div class="service-order-meta">Connect vendeur: <strong>{{ $order->seller->stripe_connect_payouts_enabled ? 'actif' : 'en attente' }}</strong></div>
                        @if($order->scheduled_for)
                            <div class="service-order-meta">Souhaite demarrer le {{ $order->scheduled_for->format('d/m/Y') }}</div>
                        @endif
                        @if($order->message)
                            <div class="service-order-meta">Message: {{ $order->message }}</div>
                        @endif
                        @if($order->canSellerAccept())
                            <div class="service-order-actions">
                                <form action="{{ route('service-orders.accept', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Accepter</button>
                                </form>
                            </div>
                            <form action="{{ route('service-orders.refuse', $order) }}" method="POST" class="mt-2">
                                @csrf
                                <textarea name="reason" class="service-order-reason" placeholder="Motif de refus optionnel"></textarea>
                                <div class="service-order-actions">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Refuser</button>
                                </div>
                            </form>
                        @endif
                        @if($order->paid_at)
                            <div class="service-order-note">Paiement Stripe confirme le {{ $order->paid_at->format('d/m/Y H:i') }}. Les fonds sont bloques jusqu'a liberation.</div>
                        @endif
                        @if($order->refunded_at)
                            <div class="service-order-note">Remboursement Stripe emis le {{ $order->refunded_at->format('d/m/Y H:i') }}.</div>
                        @endif
                        @if($order->dispute_reason)
                            <div class="service-order-note">Litige: {{ $order->dispute_reason }}</div>
                        @endif
                        @if($order->admin_resolution_note)
                            <div class="service-order-note">Decision admin: {{ $order->admin_resolution_note }}</div>
                        @endif
                        @if($order->refused_reason)
                            <div class="service-order-note">Refus: {{ $order->refused_reason }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="service-order-empty"><i class="fas fa-inbox mb-3" style="font-size:2.4rem;color:#cbd5e1;"></i><h3 class="h5">Aucune demande recue</h3><p class="text-muted mb-0">Les commandes securisees recues apparaîtront ici.</p></div>
        @endif
    </div>
</div>
@endsection
