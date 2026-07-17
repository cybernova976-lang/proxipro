@extends('pro.layout')
@section('title', 'Mes Devis - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Devis</li>
            </ol>
        </nav>
        <h1>Mes devis</h1>
    </div>
    <a href="{{ route('pro.quotes.create') }}" class="btn btn-pro-primary">
        <i class="fas fa-plus me-1"></i> Nouveau devis
    </a>
</div>

{{-- Stats rapides --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-primary">{{ $quotes->total() }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Total devis</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-warning">{{ $quoteStats['waiting'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">En attente</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-success">{{ $quoteStats['accepted'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Acceptés</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-danger">{{ $quoteStats['refused'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Refusés</div>
        </div>
    </div>
</div>

@if($quotes->isEmpty())
    <div class="pro-card">
        <div class="pro-empty">
            <div class="pro-empty-icon">📄</div>
            <h5>Aucun devis</h5>
            <p>Créez votre premier devis en quelques clics.</p>
            <a href="{{ route('pro.quotes.create') }}" class="btn btn-pro-primary mt-2">
                <i class="fas fa-plus me-1"></i> Nouveau devis
            </a>
        </div>
    </div>
@else
    <div class="pro-card">
        <div class="table-responsive" style="overflow: visible;">
            <table class="pro-table">
                <thead>
                    <tr>
                        <th>N° Devis</th>
                        <th>Client</th>
                        <th>Objet</th>
                        <th>Montant TTC</th>
                        <th>Date</th>
                        <th>Validité</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotes as $quote)
                    <tr>
                        <td><a href="{{ route('pro.quotes.show', $quote->id) }}" class="fw-bold" style="color: var(--pro-primary);">{{ $quote->quote_number }}</a></td>
                        <td>{{ Str::limit($quote->client_name, 25) }}</td>
                        <td>{{ Str::limit($quote->subject, 30) }}</td>
                        <td class="fw-bold">{{ number_format($quote->total, 2, ',', ' ') }}€</td>
                        <td>{{ $quote->created_at->format('d/m/Y') }}</td>
                        <td>{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '-' }}</td>
                        <td><span class="pro-status pro-status-{{ $quote->getStatusColor() }}">{{ $quote->getStatusLabel() }}</span></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" style="border-radius: 8px;">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="z-index: 1050;">
                                    <li><a class="dropdown-item" href="{{ route('pro.quotes.show', $quote->id) }}"><i class="fas fa-eye me-2"></i>Voir</a></li>
                                    @if($quote->isEditable())<li><a class="dropdown-item" href="{{ route('pro.quotes.edit', $quote->id) }}"><i class="fas fa-edit me-2"></i>Modifier</a></li>@endif
                                    <li><a class="dropdown-item" href="{{ route('pro.quotes.download', $quote->id) }}"><i class="fas fa-download me-2"></i>Télécharger PDF</a></li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="openSendModal({{ $quote->id }}, '{{ $quote->quote_number }}', '{{ addslashes($quote->client_email ?? '') }}')">
                                            <i class="fas fa-paper-plane me-2"></i>Envoyer
                                        </a>
                                    </li>
                                    @if($quote->status === 'draft')
                                    <li>
                                        <form method="POST" action="{{ route('pro.quotes.status', $quote->id) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="sent">
                                            <button class="dropdown-item"><i class="fas fa-check-circle me-2 text-primary"></i>Marquer envoyé</button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($quote->status === 'sent')
                                    <li>
                                        <form method="POST" action="{{ route('pro.quotes.status', $quote->id) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="accepted">
                                            <button class="dropdown-item text-success"><i class="fas fa-check me-2"></i>Accepté</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('pro.quotes.status', $quote->id) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="refused">
                                            <button class="dropdown-item text-danger"><i class="fas fa-times me-2"></i>Refusé</button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($quote->status === 'accepted')
                                    <li><a class="dropdown-item" href="{{ route('pro.invoices.create', ['quoteId' => $quote->id]) }}"><i class="fas fa-file-invoice me-2"></i>Créer facture</a></li>
                                    @endif
                                    @if($quote->isEditable())
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('pro.quotes.delete', $quote->id) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce devis ?');">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="fas fa-trash-alt me-2"></i>Supprimer</button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $quotes->links() }}</div>
    </div>
@endif

{{-- ===== SEND QUOTE MODAL ===== --}}
<div class="modal fade" id="sendQuoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f172a, #1e3a5f); border: none; padding: 18px 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-paper-plane me-2"></i>Envoyer le devis <span id="sendQuoteNum"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Tab nav --}}
                <div class="d-flex border-bottom">
                    <button class="flex-fill py-3 border-0 bg-white fw-bold send-tab active" data-target="email-tab" style="color: var(--pro-primary); font-size: 0.9rem;">
                        <i class="fas fa-envelope me-1"></i> Par email
                    </button>
                    <button class="flex-fill py-3 border-0 bg-white fw-bold send-tab" data-target="msg-tab" style="color: #94a3b8; font-size: 0.9rem;">
                        <i class="fas fa-comment-dots me-1"></i> Par messagerie
                    </button>
                </div>

                {{-- Email tab --}}
                <div id="email-tab" class="send-tab-content active" style="padding: 24px;">
                    <form id="sendEmailForm" method="POST" action="">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Adresse email du destinataire</label>
                            <input type="email" name="email" id="sendEmail" class="form-control" placeholder="client@exemple.com" required style="border-radius: 10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Message personnalisé <span class="text-muted fw-normal">(optionnel)</span></label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Bonjour, veuillez trouver ci-joint..." style="border-radius: 10px;"></textarea>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-3 mb-3" style="background: #f0fdf4; border-radius: 10px; font-size: 0.82rem; color: #059669;">
                            <i class="fas fa-paperclip"></i>
                            <span>Le devis PDF sera automatiquement joint à l'email</span>
                        </div>
                        <button type="submit" class="btn w-100 py-2 fw-bold" style="background: linear-gradient(135deg, #0f172a, #1e3a5f); color: white; border-radius: 10px;">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer par email
                        </button>
                    </form>
                </div>

                {{-- Messaging tab --}}
                <div id="msg-tab" class="send-tab-content" style="padding: 24px; display: none;">
                    <form id="sendMsgForm" method="POST" action="">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Rechercher un destinataire</label>
                            <div class="position-relative">
                                <input type="text" id="recipientSearchList" class="form-control" placeholder="Tapez un nom ou email..." autocomplete="off" style="border-radius: 10px;">
                                <input type="hidden" name="recipient_id" id="recipientIdList" required>
                                <div id="recipientResultsList" class="position-absolute w-100 bg-white border rounded-3 shadow-sm" style="top: 100%; z-index: 10; max-height: 200px; overflow-y: auto; display: none;"></div>
                            </div>
                            <div id="selectedRecipientList" class="mt-2" style="display: none;">
                                <span class="badge bg-success px-3 py-2" style="font-size: 0.82rem; border-radius: 20px;">
                                    <i class="fas fa-user me-1"></i><span id="recipientNameList"></span>
                                    <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.6rem;" onclick="clearRecipientList()"></button>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem;">Message personnalisé <span class="text-muted fw-normal">(optionnel)</span></label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Bonjour, voici votre devis..." style="border-radius: 10px;"></textarea>
                        </div>
                        <div class="d-flex align-items-center gap-2 p-3 mb-3" style="background: #eff6ff; border-radius: 10px; font-size: 0.82rem; color: #2563eb;">
                            <i class="fas fa-comment-dots"></i>
                            <span>Le résumé du devis sera envoyé dans la conversation</span>
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
.send-tab-content.active { display: block; }
.send-tab.active { color: var(--pro-primary, #0f172a) !important; border-bottom: 3px solid var(--pro-primary, #0f172a); }
.send-tab { transition: all 0.2s; cursor: pointer; }
.send-tab:hover { background: #f8fafc !important; }
</style>

<script>
function openSendModal(quoteId, quoteNumber, clientEmail) {
    document.getElementById('sendQuoteNum').textContent = quoteNumber;
    document.getElementById('sendEmailForm').action = '/pro/quotes/' + quoteId + '/send-email';
    document.getElementById('sendMsgForm').action = '/pro/quotes/' + quoteId + '/send-message';
    if (clientEmail) {
        document.getElementById('sendEmail').value = clientEmail;
    }
    new bootstrap.Modal(document.getElementById('sendQuoteModal')).show();
}

document.querySelectorAll('.send-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.send-tab').forEach(function(t) { t.classList.remove('active'); t.style.color = '#94a3b8'; });
        document.querySelectorAll('.send-tab-content').forEach(function(c) { c.classList.remove('active'); c.style.display = 'none'; });
        this.classList.add('active');
        var target = document.getElementById(this.dataset.target);
        target.classList.add('active');
        target.style.display = 'block';
    });
});

// Recipient search for messaging (debounced)
var searchTimeoutList;
document.getElementById('recipientSearchList').addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(searchTimeoutList);
    if (q.length < 2) {
        document.getElementById('recipientResultsList').style.display = 'none';
        return;
    }
    searchTimeoutList = setTimeout(function() {
        fetch('/api/users/search?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(users) {
            var container = document.getElementById('recipientResultsList');
            if (users.length === 0) {
                container.innerHTML = '<div class="p-3 text-muted text-center" style="font-size: 0.85rem;">Aucun utilisateur trouvé</div>';
            } else {
                container.innerHTML = users.map(function(u) {
                    return '<div style="padding: 10px 14px; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background=\'#f0f2f5\'" onmouseout="this.style.background=\'transparent\'" onclick="selectRecipientList(' + u.id + ', \'' + u.name.replace(/'/g, "\\'") + '\')">'
                        + '<div style="font-weight: 600; color: #0f172a; font-size: 0.88rem;">' + u.name + '</div>'
                        + (u.email ? '<div style="font-size: 0.78rem; color: #64748b;">' + u.email + '</div>' : '')
                        + '</div>';
                }).join('');
            }
            container.style.display = 'block';
        })
        .catch(function() {
            document.getElementById('recipientResultsList').style.display = 'none';
        });
    }, 300);
});

function selectRecipientList(id, name) {
    document.getElementById('recipientIdList').value = id;
    document.getElementById('recipientNameList').textContent = name;
    document.getElementById('selectedRecipientList').style.display = 'block';
    document.getElementById('recipientSearchList').value = '';
    document.getElementById('recipientResultsList').style.display = 'none';
}

function clearRecipientList() {
    document.getElementById('recipientIdList').value = '';
    document.getElementById('selectedRecipientList').style.display = 'none';
}
</script>
@endsection
