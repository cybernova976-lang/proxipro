@extends('layouts.app')

@section('title', 'Mes Factures - Lunamars')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="display-6 fw-bold text-white mb-2">
                <i class="fas fa-file-invoice me-3 text-primary"></i>Mes Factures
            </h1>
            <p class="text-white-50">Historique de vos paiements et factures téléchargeables</p>
        </div>
        <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-light">
            <i class="fas fa-arrow-left me-2"></i>Retour aux abonnements
        </a>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 20px; background: rgba(255,255,255,0.05);">
        <div class="card-body p-0">
            @if($invoices && count($invoices) > 0)
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="border-0 py-3">Date</th>
                                <th class="border-0 py-3">Description</th>
                                <th class="border-0 py-3">Montant</th>
                                <th class="border-0 py-3">Statut</th>
                                <th class="border-0 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td class="py-3">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                        {{ $invoice->date()->format('d/m/Y') }}
                                    </td>
                                    <td class="py-3">
                                        @foreach($invoice->invoiceItems() as $item)
                                            {{ $item->description }}<br>
                                        @endforeach
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-success fs-6">
                                            {{ $invoice->total() }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        @if($invoice->paid)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Payée
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>En attente
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-end">
                                        <a href="{{ route('subscriptions.download-invoice', $invoice->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i>Télécharger
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-4x text-white-50 mb-4"></i>
                    <h4 class="text-white mb-2">Aucune facture</h4>
                    <p class="text-white-50 mb-4">Vous n'avez pas encore de factures.</p>
                    <a href="{{ route('subscriptions.index') }}" class="btn btn-primary">
                        <i class="fas fa-crown me-2"></i>Voir les abonnements
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
