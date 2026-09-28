@auth
<script src="{{ asset('js/user-presence.js') }}?v={{ filemtime(public_path('js/user-presence.js')) }}" data-user-presence data-heartbeat-url="{{ route('presence.heartbeat') }}" data-interval="{{ \App\Services\UserPresence::HEARTBEAT_SECONDS }}" defer></script>
@endauth
