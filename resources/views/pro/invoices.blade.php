@extends('pro.layout')
@section('title', 'Mes Factures - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <h1>Mes factures</h1>
    </div>
    @include('pro.partials.document-allowance', ['createUrl' => route('pro.invoices.create'), 'createLabel' => 'Nouvelle facture'])
</div>

@include('pro.partials.kpi-strip', ['label' => 'Vos factures en chiffres', 'items' => [
    ['label' => 'Total factures', 'value' => $invoices->total(), 'tone' => 'info'],
    ['label' => 'En attente', 'value' => $invoiceStats['sent'], 'tone' => 'warning'],
    ['label' => 'Payées', 'value' => $invoiceStats['paid'], 'tone' => 'success'],
    ['label' => 'En retard', 'value' => $invoiceStats['overdue'], 'tone' => 'danger'],
]])

@if($invoices->isEmpty())
    <div class="pro-card">
        <div class="pro-empty">
            <div class="pro-empty-icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></div>
            <h5>Aucune facture</h5>
            <p>Créez votre première facture pour suivre vos paiements.</p>
            <a href="{{ route('pro.invoices.create') }}" class="btn btn-pro-outline mt-2">
                <i class="fas fa-plus me-1"></i> Nouvelle facture
            </a>
        </div>
    </div>
@else
    <div class="pro-card">
        <div class="table-responsive" style="overflow: visible;">
            <table class="pro-table pro-table--cards">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Client</th>
                        <th class="pk-col-amount">Montant TTC</th>
                        <th>Date</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                        <th class="pk-col-actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td class="pk-col-id"><a href="{{ route('pro.invoices.show', $invoice->id) }}" class="pro-table__link">{{ $invoice->invoice_number }}</a>@if($invoice->kind !== \App\Models\ProInvoice::KIND_INVOICE) <span class="pro-status pro-status-secondary">{{ $invoice->isCreditNote() ? 'Avoir' : 'Acompte' }}</span>@endif</td>
                        <td class="pk-col-client">{{ Str::limit($invoice->client_name, 25) }}</td>
                        <td class="pk-col-amount">{{ \App\Support\Money::eur($invoice->isCreditNote() ? -abs((float) $invoice->total) : $invoice->total) }}</td>
                        <td class="pk-col-date" data-label="Émise le">{{ $invoice->created_at->format('d/m/Y') }}</td>
                        <td class="pk-col-due" data-label="Échéance">@if($invoice->due_date){{ $invoice->due_date->format('d/m/Y') }}@endif</td>
                        <td class="pk-col-status"><span class="pro-status pro-status-{{ $invoice->getStatusColor() }}">{{ $invoice->getStatusLabel() }}</span></td>
                        <td class="pk-col-actions">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light pro-table__menu" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Actions de la facture {{ $invoice->invoice_number }}">
                                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('pro.invoices.show', $invoice->id) }}"><i class="fas fa-eye me-2"></i>Voir</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pro.invoices.download', $invoice->id) }}"><i class="fas fa-download me-2"></i>Télécharger PDF</a></li>
                                    @if($invoice->isEditable())<li><a class="dropdown-item" href="{{ route('pro.invoices.edit', $invoice->id) }}"><i class="fas fa-edit me-2"></i>Modifier</a></li>@endif
                                    @if(in_array($invoice->status, ['draft', 'sent', 'overdue']))
                                    <li>
                                        <form method="POST" action="{{ route('pro.invoices.status', $invoice->id) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="paid">
                                            <input type="hidden" name="payment_method" value="other">
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
                                    @if($invoice->isEditable())
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('pro.invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Supprimer cette facture ?')">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Supprimer</button>
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
        <div class="mt-3">{{ $invoices->links() }}</div>
    </div>
@endif
@endsection
