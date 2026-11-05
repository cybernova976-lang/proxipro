@if(request()->routeIs('profile.show') && request()->query('activer_prestataire') === '1' && !auth()->user()->is_service_provider && auth()->user()->hasCompleteVerificationProfile() && auth()->user()->hasVerifiedProfileBadge())
<script>
    (() => {
        let attempts = 0;

        const openProviderOnboarding = () => {
            const modalElement = document.getElementById('becomeProviderModal');
            if (modalElement && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                const url = new URL(window.location.href);
                url.searchParams.delete('activer_prestataire');
                window.history.replaceState(window.history.state, '', url);
                return;
            }

            attempts += 1;
            if (attempts < 30) {
                window.setTimeout(openProviderOnboarding, 100);
            }
        };

        if (document.readyState !== 'loading') {
            openProviderOnboarding();
        } else {
            document.addEventListener('DOMContentLoaded', openProviderOnboarding, { once: true });
        }
    })();
</script>
@endif
