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
    public function __invoke(Request $request, UsageAnalytics $analytics): Response
    {
        $validated = $request->validate([
            'event_name' => ['required', Rule::in(UsageAnalytics::EVENTS)],
            'route_name' => ['nullable', 'string', 'max:100'],
            'app_mode' => ['required', 'in:browser,pwa'],
            'step' => ['nullable', 'integer', 'between:1,5'],
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

        if ($isJourneyEvent) {
            if (! isset($validated['step'])) {
                return response()->noContent();
            }

            $audience = $request->user() ? 'member' : 'guest';
            $routeName = 'demand.step.'.$validated['step'].'.'.$audience;
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
