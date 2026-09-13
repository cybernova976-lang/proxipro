@if(session('open_provider_onboarding'))
<script>
    (() => {
        let attempts = 0;

        const openProviderOnboarding = () => {
        const modalElement = document.getElementById('becomeProviderModal');
        if (modalElement && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                return;
            }

            attempts += 1;
            if (attempts < 30) {
                window.setTimeout(openProviderOnboarding, 100);
            }
        };

        if (document.readyState === 'complete') {
            openProviderOnboarding();
        } else {
            window.addEventListener('load', openProviderOnboarding, { once: true });
        }
    })();
</script>
@endif
