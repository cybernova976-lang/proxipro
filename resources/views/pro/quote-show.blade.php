@extends('pro.layout')
@section('title', 'Devis ' . $quote->quote_number . ' - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.quotes') }}" style="color: var(--pro-primary);">Devis</a></li>
                <li class="breadcrumb-item active">{{ $quote->quote_number }}</li>
            </ol>
        </nav>
        <h1>Devis {{ $quote->quote_number }}</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="pro-status pro-status-{{ $quote->getStatusColor() }}" style="font-size: 0.88rem; padding: 8px 16px;">{{ $quote->getStatusLabel() }}</span>
        @if($quote->status === 'accepted')
            @if((float) $quote->deposit_percentage > 0 && (float) $quote->deposit_percentage < 100 && ! $quote->depositInvoices()->exists() && ! $quote->invoice()->exists())
            <form method="POST" action="{{ route('pro.quotes.deposit-invoice', $quote->id) }}">
                @csrf
                <button type="submit" class="btn btn-pro-outline"><i class="fas fa-percent me-1" aria-hidden="true"></i> Facturer l’acompte</button>
            </form>
            @endif
            <a href="{{ route('pro.invoices.create', ['quoteId' => $quote->id]) }}" class="btn btn-pro-primary">
                <i class="fas fa-file-invoice me-1"></i> Créer facture
            </a>
        @endif
    </div>
</div>

@push('head')
<link rel="stylesheet" href="{{ asset('css/pro-document-sheet.css') }}?v={{ @filemtime(public_path('css/pro-document-sheet.css')) }}">
@endpush
<div class="pk-sheet-frame">
    <article class="pk-sheet" aria-label="Aperçu du devis">
        @include('pro.partials.quote-sheet')
    </article>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert" style="border-radius: 12px; border: none;">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="border-radius: 12px; border: none;">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex flex-wrap gap-2 mt-3">
    @if($quote->isEditable())
    <a href="{{ route('pro.quotes.edit', $quote->id) }}" class="btn btn-pro-primary">
        <i class="fas fa-edit me-1"></i> Modifier
    </a>
    @endif
    <a href="{{ route('pro.quotes.download', $quote->id) }}" class="btn btn-pro-outline">
        <i class="fas fa-download me-1"></i> Télécharger PDF
    </a>
    <button onclick="openSendModal()" class="btn btn-pro-outline" style="border-color: #00a884; color: #00a884;">
        <i class="fas fa-paper-plane me-1"></i> Envoyer
    </button>
    <button onclick="window.print()" class="btn btn-pro-outline">
        <i class="fas fa-print me-1"></i> Imprimer
    </button>
    <form method="POST" action="{{ route('pro.quotes.duplicate', $quote->id) }}">
        @csrf
        <button type="submit" class="btn btn-pro-outline"><i class="fas fa-copy me-1" aria-hidden="true"></i> Dupliquer</button>
    </form>
    <a href="{{ route('pro.quotes') }}" class="btn btn-light" style="border-radius: 10px;">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    @if($quote->isEditable())
    <form method="POST" action="{{ route('pro.quotes.delete', $quote->id) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce brouillon ?');" class="ms-auto">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger" style="border-radius: 10px;">
            <i class="fas fa-trash-alt me-1"></i> Supprimer
        </button>
    </form>
    @endif
</div>

@include('pro.partials.free-watermark-notice', ['documentLabel' => 'le PDF de ce devis', 'pkDocument' => $quote, 'pkLine' => true])

@include('pro.partials.quote-response')
@include('pro.partials.quote-premium-tools')

{{-- ===== SEND QUOTE MODAL ===== --}}
<div class="modal fade" id="sendQuoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f172a, #1e3a5f); border: none; padding: 18px 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-paper-plane me-2"></i>Envoyer le devis {{ $quote->quote_number }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Tab nav --}}
                <div class="d-flex border-bottom">
                    <button class="flex-fill py-3 border-0 bg-white fw-bold send-tab active" data-target="email-tab" style="color: var(--pro-primary); font-size: 0.9rem; cursor: pointer;">
                        <i class="fas fa-envelope me-1"></i> Par email
                    </button>
                    <button class="flex-fill py-3 border-0 bg-white fw-bold send-tab" data-target="msg-tab" style="color: #94a3b8; font-size: 0.9rem; cursor: pointer;">
                        <i class="fas fa-comment-dots me-1"></i> Par messagerie
                    </button>
                </div>

                {{-- Email tab --}}
                <div id="email-tab" class="send-tab-content" style="padding: 24px;">
                    <form method="POST" action="{{ route('pro.quotes.sendEmail', $quote->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Adresse email du destinataire</label>
                            <input type="email" name="email" value="{{ $quote->client_email ?? '' }}" class="form-control" placeholder="client@exemple.com" required style="border-radius: 10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Message personnalisé <span class="text-muted fw-normal">(optionnel)</span></label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Bonjour, veuillez trouver ci-joint votre devis..." style="border-radius: 10px;"></textarea>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-3 mb-3" style="background: #f0fdf4; border-radius: 10px; font-size: 0.82rem; color: #059669;">
                            <i class="fas fa-paperclip"></i>
                            <span>Le devis PDF sera automatiquement joint à l'email</span>
                        </div>
                        @if($user->hasActiveProSubscription() && ! $quote->reminder_sent_at)
                        <div class="form-check mb-3" style="font-size: 0.85rem;">
                            <input type="hidden" name="auto_reminder" value="0">
                            <input class="form-check-input" type="checkbox" name="auto_reminder" value="1" id="pkAutoReminder" checked>
                            <label class="form-check-label" for="pkAutoReminder">Relancer automatiquement ce client une seule fois, après {{ \App\Services\QuoteReminderService::DELAY_DAYS }} jours sans réponse <span class="text-muted">(Premium)</span></label>
                        </div>
                        @endif
                        <button type="submit" class="btn w-100 py-2 fw-bold" style="background: linear-gradient(135deg, #0f172a, #1e3a5f); color: white; border-radius: 10px;">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer par email
                        </button>
                    </form>
                </div>

                {{-- Messaging tab --}}
                <div id="msg-tab" class="send-tab-content" style="padding: 24px; display: none;">
                    <form method="POST" action="{{ route('pro.quotes.sendMessage', $quote->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Rechercher un utilisateur</label>
                            <div class="position-relative">
                                <input type="text" id="recipientSearch" class="form-control" placeholder="Tapez un nom ou email..." autocomplete="off" style="border-radius: 10px;">
                                <input type="hidden" name="recipient_id" id="recipientId" required>
                                <div id="recipientResults" class="position-absolute w-100 bg-white border rounded-3 shadow-sm" style="top: 100%; z-index: 10; max-height: 200px; overflow-y: auto; display: none;"></div>
                            </div>
                            <div id="selectedRecipient" class="mt-2" style="display: none;">
                                <span class="badge bg-success px-3 py-2" style="font-size: 0.82rem; border-radius: 20px;">
                                    <i class="fas fa-user me-1"></i><span id="recipientName"></span>
                                    <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.6rem;" onclick="clearRecipient()"></button>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Message personnalisé <span class="text-muted fw-normal">(optionnel)</span></label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Bonjour, voici votre devis..." style="border-radius: 10px;"></textarea>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-3 mb-3" style="background: #eff6ff; border-radius: 10px; font-size: 0.82rem; color: #2563eb;">
                            <i class="fas fa-comment-dots"></i>
                            <span>Le résumé du devis sera envoyé dans la messagerie interne</span>
                        </div>
                        <button type="submit" class="btn w-100 py-2 fw-bold" style="background: linear-gradient(135deg, #00a884, #25d366); color: white; border-radius: 10px;">
                            <i class="fas fa-comment-dots me-2"></i>Envoyer par messagerie
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.send-tab-content { display: none; }
.send-tab-content:first-of-type { display: block; }
#email-tab { display: block; }
.send-tab.active { color: var(--pro-primary, #0f172a) !important; border-bottom: 3px solid var(--pro-primary, #0f172a); }
.send-tab { transition: all 0.2s; }
.send-tab:hover { background: #f8fafc !important; }
.recipient-option { padding: 10px 14px; cursor: pointer; transition: background 0.15s; font-size: 0.88rem; }
.recipient-option:hover { background: #f0f2f5; }
.recipient-option .name { font-weight: 600; color: #0f172a; }
.recipient-option .email { font-size: 0.78rem; color: #64748b; }
</style>

<script>
function openSendModal() {
    new bootstrap.Modal(document.getElementById('sendQuoteModal')).show();
}

// Tab switching
document.querySelectorAll('.send-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.send-tab').forEach(function(t) { t.classList.remove('active'); t.style.color = '#94a3b8'; });
        document.querySelectorAll('.send-tab-content').forEach(function(c) { c.style.display = 'none'; });
        this.classList.add('active');
        document.getElementById(this.dataset.target).style.display = 'block';
    });
});

// Recipient search (debounced)
var searchTimeout;
document.getElementById('recipientSearch').addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(searchTimeout);
    if (q.length < 2) {
        document.getElementById('recipientResults').style.display = 'none';
        return;
    }
    searchTimeout = setTimeout(function() {
        fetch('/api/users/search?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(users) {
            var container = document.getElementById('recipientResults');
            if (users.length === 0) {
                container.innerHTML = '<div class="p-3 text-muted text-center" style="font-size: 0.85rem;">Aucun utilisateur trouvé</div>';
            } else {
                container.innerHTML = users.map(function(u) {
                    return '<div class="recipient-option" onclick="selectRecipient(' + u.id + ', \'' + u.name.replace(/'/g, "\\'") + '\')">'
                        + '<div class="name">' + u.name + '</div>'
                        + (u.email ? '<div class="email">' + u.email + '</div>' : '')
                        + '</div>';
                }).join('');
            }
            container.style.display = 'block';
        })
        .catch(function() {
            document.getElementById('recipientResults').style.display = 'none';
        });
    }, 300);
});

function selectRecipient(id, name) {
    document.getElementById('recipientId').value = id;
    document.getElementById('recipientName').textContent = name;
    document.getElementById('selectedRecipient').style.display = 'block';
    document.getElementById('recipientSearch').value = '';
    document.getElementById('recipientResults').style.display = 'none';
}

function clearRecipient() {
    document.getElementById('recipientId').value = '';
    document.getElementById('selectedRecipient').style.display = 'none';
}
</script>
@endsection
