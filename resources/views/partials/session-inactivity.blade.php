@auth
<script src="{{ asset('js/session-inactivity.js') }}?v={{ filemtime(public_path('js/session-inactivity.js')) }}"
    data-session-inactivity data-activity-url="{{ route('auth.session-activity') }}"
    data-csrf="{{ csrf_token() }}"
    data-login-url="{{ route('login') }}"
    data-remaining="{{ max(0, (request()->attributes->get('session_idle_expires_at') ?? now()->timestamp) - now()->timestamp) }}" defer></script>
@endauth
