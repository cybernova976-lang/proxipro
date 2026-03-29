@extends('pro.layout')
@section('title', 'Mes Factures - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Factures</li>
            </ol>
        </nav>
        <h1>Mes factures</h1>
    </div>
    <a href="{{ route('pro.invoices.create') }}" class="btn btn-pro-primary">
        <i class="fas fa-plus me-1"></i> Nouvelle facture
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-primary">{{ $invoices->total() }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Total factures</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-warning">{{ $invoices->where('status', 'sent')->count() }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">En attente</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-success">{{ $invoices->where('status', 'paid')->count() }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Payées</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pro-card text-center py-3 mb-0">
            <div class="fw-bold fs-4 text-danger">{{ $invoices->where('status', 'overdue')->count() }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">En retard</div>
        </div>
    </div>
</div>

@if($invoices->isEmpty())
    <div class="pro-card">
        <div class="pro-empty">
            <div class="pro-empty-icon">🧾</div>
            <h5>Aucune facture</h5>
            <p>Créez votre première facture pour suivre vos paiements.</p>
            <a href="{{ route('pro.invoices.create') }}" class="btn btn-pro-primary mt-2">
                <i class="fas fa-plus me-1"></i> Nouvelle facture
            </a>
        </div>
    </div>
@else
    <div class="pro-card">
        <div class="table-responsive" style="overflow: visible;">
            <table class="pro-table">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Client</th>
                        <th>Montant TTC</th>
                        <th>Date</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('pro.invoices.show', $invoice->id) }}" class="fw-bold" style="color: var(--pro-primary);">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ Str::limit($invoice->client_name, 25) }}</td>
                        <td class="fw-bold">{{ number_format($invoice->total, 2, ',', ' ') }}€</td>
                        <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                        <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                        <td><span class="pro-status pro-status-{{ $invoice->getStatusColor() }}">{{ $invoice->getStatusLabel() }}</span></td>
                        <td style="text-align: right;">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" data-bs-display="static" style="border-radius: 8px;">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('pro.invoices.show', $invoice->id) }}"><i class="fas fa-eye me-2"></i>Voir</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pro.invoices.download', $invoice->id) }}"><i class="fas fa-download me-2"></i>Télécharger PDF</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pro.invoices.edit', $invoice->id) }}"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                    @if($invoice->status !== 'paid')
                                    <li>
                                        <form method="POST" action="{{ route('pro.invoices.status', $invoice->id) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="paid">
                                            <button class="dropdown-item text-success"><i class="fas fa-check me-2"></i>Marquer payée</button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($invoice->status === 'draft')
                                    <li>
                                        <form method="POST" action="{{ route('pro.invoices.status', $invoice->id) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="sent">
                                            <button class="dropdown-item"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
                                        </form>
                                    </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('pro.invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Supprimer cette facture ?')">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $invoices->links() }}</div>
    </div>
@endif
@endsection
