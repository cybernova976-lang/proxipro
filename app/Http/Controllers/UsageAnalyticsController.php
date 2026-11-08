<?php

namespace App\Http\Controllers;

use App\Services\UsageAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class UsageAnalyticsController extends Controller
{
    /** Le formulaire habituel a 5 étapes ; le tableau d'administration les lit une à une. */
    private const FREE_FORM_STEPS = 5;

    public function __invoke(Request $request, UsageAnalytics $analytics): Response
    {
        $validated = $request->validate([
            'event_name' => ['required', Rule::in(array_values(array_diff(UsageAnalytics::EVENTS, UsageAnalytics::SERVER_EVENTS)))],
            'route_name' => ['nullable', 'string', 'max:100'],
            'app_mode' => ['required', 'in:browser,pwa'],
            // 9 écrans au plus dans le parcours guidé.
            'step' => ['nullable', 'integer', 'between:1,9'],
        ]);

        if ($request->header('DNT') === '1') {
            return response()->noContent();
        }

        $agent = new Agent;
        $agent->setUserAgent($request->userAgent() ?? '');

        if ($agent->isRobot()) {
            return response()->noContent();
        }

        $journeyEvents = [
            'demand_step_view',
            'demand_validation_error',
            'demand_auth_redirect',
            'demand_draft_resumed',
        ];
        $isJourneyEvent = in_array($validated['event_name'], $journeyEvents, true);
        $isGuidedJourneyEvent = $validated['event_name'] === 'demand_guided_step_view';

        if ($isJourneyEvent || $isGuidedJourneyEvent) {
            if (! isset($validated['step'])) {
                return response()->noContent();
            }

            // Ancien formulaire : une étape au-delà de 5 fausserait son tableau.
            if ($isJourneyEvent && (int) $validated['step'] > self::FREE_FORM_STEPS) {
                return response()->noContent();
            }

            $audience = $request->user() ? 'member' : 'guest';
            $prefix = $isGuidedJourneyEvent ? 'demand.guided.step.' : 'demand.step.';
            $routeName = $prefix.$validated['step'].'.'.$audience;
        } else {
            $routeName = (string) ($validated['route_name'] ?? 'other');
            if (! Route::has($routeName) || str_starts_with($routeName, 'admin.')) {
                $routeName = 'other';
            }
        }

        $deviceType = (string) $request->attributes->get('device_type', 'desktop');
        $appMode = (string) $validated['app_mode'];

        $analytics->record($validated['event_name'], $routeName, $deviceType, $appMode);

        if ($validated['event_name'] === 'page_view') {
            $today = today()->toDateString();
            if ($request->session()->get('usage_analytics.last_session_day') !== $today) {
                $analytics->record('session_start', null, $deviceType, $appMode);
                $request->session()->put('usage_analytics.last_session_day', $today);
            }
        }

        return response()->noContent();
    }
}
