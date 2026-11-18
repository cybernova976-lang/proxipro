{{-- Messages de retour après une action. Styles : public/css/flash.css.
     Un message déjà écrit par la page elle-même, ou par le bandeau d'information
     du gabarit, n'est pas répété ici. --}}
@php
    $pkFlashRendered = $__env->yieldContent('content').$__env->yieldContent('scripts').$__env->yieldPushContent('scripts')
        .(is_string(session('info')) ? session('info') : '');
    $pkFlashMessages = \App\Support\FlashMessages::fromSession(request()->hasSession() ? request()->session() : null, $pkFlashRendered);
@endphp
@foreach($pkFlashMessages as $pkFlash)
    <div class="alert alert-dismissible fade show pk-flash pk-flash--{{ $pkFlash['tone'] }}" role="{{ $pkFlash['role'] }}">
        <i class="fas {{ $pkFlash['icon'] }}" aria-hidden="true"></i>
        @if(count($pkFlash['lines']) === 1)
            <span>{{ $pkFlash['lines'][0] }}</span>
        @else
            <ul>
                @foreach($pkFlash['lines'] as $pkFlashLine)
                    <li>{{ $pkFlashLine }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endforeach
