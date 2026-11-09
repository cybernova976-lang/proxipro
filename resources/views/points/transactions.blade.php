@extends('layouts.app')
@section('title', 'Ancien historique des points - Prokejem')
@section('content')
<div class="container py-4">
    <h1 class="h4">Ancien historique des points</h1>
    <p>Le programme est terminé. Ces opérations sont conservées pour votre suivi ; elles ne donnent plus accès à des avantages.</p>
    <div class="table-responsive"><table class="table bg-white">
        <thead><tr><th>Date</th><th>Description</th><th>Mouvement</th></tr></thead>
        <tbody>@forelse($transactions as $transaction)<tr><td>{{ $transaction->created_at->format('d/m/Y') }}</td><td>{{ $transaction->description }}</td><td>{{ $transaction->points }}</td></tr>@empty<tr><td colspan="3">Aucune opération enregistrée.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $transactions->links() }}
    <a class="btn btn-outline-primary" href="{{ route('public.provider-offers') }}">Voir les offres et abonnements</a>
</div>
@endsection
