{{-- Bouton unique de création : alerte « nouvelles offres du territoire » (mêmes champs que sur l'accueil). --}}
<form method="POST" action="{{ route('saved-searches.store') }}" class="saved-search-create">
    @csrf
    @foreach($territoryAlert as $alertField => $alertValue)
        <input type="hidden" name="{{ $alertField }}" value="{{ $alertValue }}">
    @endforeach
    <button type="submit" class="btn btn-primary" style="border-radius:12px; font-weight:700;">
        <i class="far fa-bell me-2"></i>Me prévenir des nouvelles offres {{ $territory->withPreposition() }}
    </button>
</form>
