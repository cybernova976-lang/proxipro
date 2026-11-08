@extends('admin.layouts.app')

@section('title', 'Santé du feed')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1"><i class="fas fa-heart-pulse text-primary me-2" aria-hidden="true"></i>Santé du feed</h1>
    <p class="text-muted mb-0">
        Mesures lues en base le {{ $pkHealth['generated_at']->format('d/m/Y à H:i') }}, avec les règles de territoire du flux client.
    </p>
</div>

<div class="alert alert-light border shadow-sm mb-4" role="note">
    <i class="fas fa-circle-info text-primary me-2" aria-hidden="true"></i>
    Cette page est en lecture seule : elle ne modifie, ne relance et ne supprime rien.
    Les listes sont limitées à {{ \App\Services\Feed\FeedHealthReport::LIST_LIMIT }} lignes.
</div>

@include('admin.feed-health.partials.territories')
@include('admin.feed-health.partials.promotions')
@include('admin.feed-health.partials.content')
@endsection
