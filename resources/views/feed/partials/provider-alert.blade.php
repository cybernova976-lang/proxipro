{{-- Alerte sur le prestataire d'une offre : le lecteur est prévenu de ses prochaines offres.
     Sans script : un formulaire par état. $pkProviderAlerts : prestataire => alerte du lecteur. --}}
@php
    $pkAlertId = collect($pkProviderAlerts ?? [])->get((int) ($pkAuthor?->id ?? 0));
    $pkCanFollow = auth()->check() && $pkAuthor && $ad->service_type === 'offre' && (int) $pkAuthor->id !== (int) auth()->id();
@endphp
@if($pkCanFollow && $pkAlertId)
    <form method="POST" action="{{ route('saved-searches.destroy', $pkAlertId) }}" class="pk-follow"
          data-pk-confirm="Ne plus être prévenu des nouvelles offres de {{ $pkName }} ?">
        @csrf
        @method('DELETE')
        <button type="submit" class="pk-save" aria-pressed="true"
                aria-label="Ne plus être prévenu des nouvelles offres de {{ $pkName }}"
                title="Alerte active. Cliquez pour ne plus être prévenu des offres de {{ $pkName }}">
            <i class="fas fa-bell" aria-hidden="true"></i>
        </button>
    </form>
@elseif($pkCanFollow)
    <form method="POST" action="{{ route('saved-searches.store') }}" class="pk-follow">
        @csrf
        <input type="hidden" name="provider" value="{{ $pkAuthor->id }}">
        <button type="submit" class="pk-save" aria-pressed="false"
                aria-label="Me prévenir des nouvelles offres de {{ $pkName }}"
                title="Me prévenir des nouvelles offres de {{ $pkName }}">
            <i class="far fa-bell" aria-hidden="true"></i>
        </button>
    </form>
@endif
