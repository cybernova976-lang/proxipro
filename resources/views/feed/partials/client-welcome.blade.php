@php
    $pkFirstName = trim(Str::before(trim(Auth::user()->name ?? ''), ' '));
    $pkPlace = $geoCity ?: ($pkTerritory?->label ?? null);
@endphp
<section class="pk-client-welcome" aria-labelledby="pkStateTitle">
    <h1 id="pkStateTitle">Bonjour{{ $pkFirstName !== '' ? ' '.$pkFirstName : '' }}</h1>
    <a class="pk-client-place" href="{{ route('profile.edit') }}#city">
        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
        @if($pkPlace)
            <span>{{ $pkPlace }}@if($pkTerritory && $pkTerritory->label !== $pkPlace), {{ $pkTerritory->label }}@endif</span><small>changer</small>
        @else
            <span>Choisir ma commune</span>
        @endif
    </a>
    @if(session('success'))
        <p class="pk-flash" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}</p>
    @endif
    @if(($pkPrimaryAction['state'] ?? null) === 'draft')
        <div class="pk-draft-resume">
            <span><i class="far fa-edit" aria-hidden="true"></i> Vous avez commencé une demande sans la publier.</span>
            <div class="pk-draft-resume__actions">
                <form method="POST" action="{{ route('demand.draft.destroy') }}" data-pk-confirm="Supprimer ce brouillon ? Les informations déjà saisies seront perdues.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="pk-draft-resume__drop">Abandonner</button>
                </form>
                <a class="pk-btn" href="{{ $pkPrimaryAction['url'] }}">{{ $pkPrimaryAction['label'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    @endif
</section>
