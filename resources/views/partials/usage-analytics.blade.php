@unless(request()->routeIs('admin.*'))
<script>
(() => {
    'use strict';

    const endpoint = @json(route('usage.store'));
    const csrfToken = @json(csrf_token());
    const routeName = @json(request()->route()?->getName() ?: 'other');
    const optOutKey = 'prokejem_usage_disabled';
    const allowedEvents = [
        'page_view',
        'feed_situation_click',
        'feed_search_submit',
        'feed_search_suggestion',
        'feed_search_no_match',
        'pwa_install',
        'push_enabled',
        'demand_step_view',
        'demand_validation_error',
        'demand_auth_redirect',
        'demand_draft_resumed',
        'demand_guided_step_view',
        'demand_answer_unknown',
        'demand_photo_added',
        'demand_photo_rejected',
        'demand_tts_used',
        'demand_detail_added',
        'demand_share_click',
    ];
    let pageViewSent = false;

    const disabled = () => {
        try {
            return navigator.doNotTrack === '1'
                || window.doNotTrack === '1'
                || localStorage.getItem(optOutKey) === '1';
        } catch (error) {
            return true;
        }
    };

    const appMode = () => window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
        ? 'pwa'
        : 'browser';

    const track = (eventName, details = {}) => {
        if (disabled() || !allowedEvents.includes(eventName)) return false;

        const payload = new FormData();
        payload.append('_token', csrfToken);
        payload.append('event_name', eventName);
        payload.append('route_name', routeName);
        payload.append('app_mode', appMode());
        if (Number.isInteger(details.step) && details.step >= 1 && details.step <= 9) {
            payload.append('step', String(details.step));
        }

        if (navigator.sendBeacon && navigator.sendBeacon(endpoint, payload)) return true;

        fetch(endpoint, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Accept': 'application/json' },
        }).catch(() => {});

        return true;
    };

    window.prokejemUsage = { track, optOutKey };

    const trackPageView = () => {
        if (pageViewSent) return;
        pageViewSent = true;
        track('page_view');
    };

    if (document.visibilityState === 'prerender') {
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') trackPageView();
        }, { once: true });
    } else {
        trackPageView();
    }
})();
</script>
@endunless
