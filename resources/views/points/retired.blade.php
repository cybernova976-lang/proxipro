@extends('layouts.app')
@section('title', 'Ancien programme de points - Prokejem')
@section('content')
<div class="container py-4" style="max-width:760px">
    <div class="card border-0 shadow-sm p-4">
        <h1 class="h4">Le programme de points est terminé</h1>
        <p>{{ \App\Support\RetiredPoints::MESSAGE }}</p>
        <p class="text-muted">Votre historique reste accessible. Un ancien solde ne donne plus accès à des avantages ou à des options payantes.</p>
        <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-primary" href="{{ route('public.provider-offers') }}">Voir les offres et abonnements</a>
            <a class="btn btn-outline-secondary" href="{{ route('points.transactions') }}">Consulter mon ancien historique</a>
        </div>
        <a class="mt-3" href="{{ route('contact.index') }}">Une question sur un ancien achat ? Contactez-nous.</a>
    </div>
</div>
@endsection
