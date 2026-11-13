{{-- Messages de retour après une action, affichés une seule fois en haut du contenu. --}}
@foreach(['success' => ['success', 'fa-circle-check'], 'error' => ['error', 'fa-circle-exclamation'], 'warning' => ['warning', 'fa-triangle-exclamation']] as $key => [$tone, $icon])
    @if(session($key))
        <div class="alert pro-alert pro-alert--{{ $tone }} alert-dismissible fade show" role="{{ $tone === 'success' ? 'status' : 'alert' }}">
            <i class="fas {{ $icon }} me-2" aria-hidden="true"></i>{{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif
@endforeach

@if($errors->any())
    <div class="alert pro-alert pro-alert--error alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif
