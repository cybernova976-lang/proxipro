{{-- Fiche d'une offre : être prévenu des prochaines offres de ce prestataire. $provider : son compte. --}}
@php
    $followService = app(\App\Services\SavedSearchService::class);
    $followViewer = auth()->user();
    $followAllowed = $followViewer && $provider && $followService->canFollowProvider($followViewer, $provider);
    $followAlert = $followAllowed ? $followService->findProviderAlert($followViewer, $provider) : null;
@endphp
@if($followAllowed && $followAlert?->is_active)
    <form method="POST" action="{{ route('saved-searches.destroy', $followAlert) }}" class="mt-2"
          data-pk-confirm="Ne plus être prévenu des nouvelles offres de {{ $provider->name }} ?">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-contact btn-contact-secondary" aria-pressed="true">
            <i class="fas fa-bell me-2" aria-hidden="true"></i>Alerte active · ne plus me prévenir
        </button>
    </form>
@elseif($followAllowed)
    <div data-pk-alert>
    <template data-pk-alert-done>
        <form method="POST" action="__STOP_URL__" class="mt-2"
              data-pk-confirm="Ne plus être prévenu des nouvelles offres de {{ $provider->name }} ?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-contact btn-contact-secondary" aria-pressed="true">
                <i class="fas fa-bell me-2" aria-hidden="true"></i>Alerte active · ne plus me prévenir
            </button>
        </form>
    </template>
    <form method="POST" action="{{ route('saved-searches.store') }}" class="mt-2">
        @csrf
        <input type="hidden" name="provider" value="{{ $provider->id }}">
        <button type="submit" class="btn btn-contact btn-contact-secondary">
            <i class="far fa-bell me-2" aria-hidden="true"></i>Me prévenir de ses nouvelles offres
        </button>
    </form>
    </div>
@endif
