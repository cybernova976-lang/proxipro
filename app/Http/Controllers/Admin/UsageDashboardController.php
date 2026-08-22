<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Message;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\UsageDailyMetric;
use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsageDashboardController extends Controller
{
    public function __invoke(Request $request, UsageAnalytics $analytics)
    {
        $period = (int) $request->integer('period', 30);
        if (! in_array($period, [7, 30, 90], true)) {
            $period = 30;
        }

        $end = today();
        $start = today()->subDays($period - 1);
        $metrics = UsageDailyMetric::query()
            ->whereBetween('metric_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $sumEvent = fn (string $eventName): int => (int) $metrics
            ->where('event_name', $eventName)
            ->sum('count');

        $pageViews = $sumEvent('page_view');
        $sessions = $sumEvent('session_start');
        $pwaPageViews = (int) $metrics
            ->where('event_name', 'page_view')
            ->where('app_mode', 'pwa')
            ->sum('count');

        $periodStart = $start->copy()->startOfDay();
        $periodEnd = $end->copy()->endOfDay();
        $demandStarts = (int) $metrics
            ->where('event_name', 'page_view')
            ->where('route_name', 'demand.create')
            ->sum('count');
        $periodDemands = Ad::query()
            ->where('service_type', 'demande')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->pluck('created_at', 'id');
        $firstProposalDates = $periodDemands->isEmpty()
            ? collect()
            : ServiceProposal::query()
                ->whereIn('ad_id', $periodDemands->keys())
                ->select('ad_id', DB::raw('MIN(created_at) as first_proposal_at'))
                ->groupBy('ad_id')
                ->get();
        $firstProposalMinutes = $firstProposalDates
            ->map(function (ServiceProposal $proposal) use ($periodDemands) {
                $publishedAt = $periodDemands->get($proposal->ad_id);

                return $publishedAt
                    ? Carbon::parse($publishedAt)->diffInMinutes(Carbon::parse($proposal->first_proposal_at))
                    : null;
            })
            ->filter(fn ($minutes) => $minutes !== null);

        $summary = [
            'page_views' => $pageViews,
            'sessions' => $sessions,
            'pwa_page_views' => $pwaPageViews,
            'pwa_share' => $pageViews > 0 ? round(($pwaPageViews / $pageViews) * 100, 1) : 0,
            'pwa_installs' => $sumEvent('pwa_install'),
            'push_activations' => $sumEvent('push_enabled'),
            'push_devices' => DB::table('push_subscriptions')->count(),
            'push_users' => DB::table('push_subscriptions')->distinct()->count('subscribable_id'),
            'demand_starts' => $demandStarts,
            'demand_publications' => $periodDemands->count(),
            'demand_completion_rate' => $demandStarts > 0
                ? round(($periodDemands->count() / $demandStarts) * 100, 1)
                : null,
            'demands_with_proposal' => $firstProposalDates->count(),
            'demand_proposal_rate' => $periodDemands->isNotEmpty()
                ? round(($firstProposalDates->count() / $periodDemands->count()) * 100, 1)
                : null,
            'median_first_proposal_minutes' => $firstProposalMinutes->isNotEmpty()
                ? (int) round($firstProposalMinutes->median())
                : null,
        ];

        $dailyGroups = $metrics->groupBy(fn (UsageDailyMetric $metric) => $metric->metric_date->toDateString());
        $dailyUsage = collect(range(0, $period - 1))->map(function (int $offset) use ($start, $dailyGroups) {
            $date = $start->copy()->addDays($offset);
            $dayMetrics = $dailyGroups->get($date->toDateString(), collect());

            return [
                'date' => $date,
                'page_views' => (int) $dayMetrics->where('event_name', 'page_view')->sum('count'),
                'sessions' => (int) $dayMetrics->where('event_name', 'session_start')->sum('count'),
                'pwa_views' => (int) $dayMetrics->where('event_name', 'page_view')->where('app_mode', 'pwa')->sum('count'),
            ];
        });

        $topPages = $metrics
            ->where('event_name', 'page_view')
            ->groupBy('route_name')
            ->map(fn ($rows, string $routeName) => [
                'route_name' => $routeName,
                'label' => $analytics->routeLabel($routeName),
                'count' => (int) $rows->sum('count'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $deviceBreakdown = collect(UsageAnalytics::DEVICE_TYPES)->mapWithKeys(fn (string $deviceType) => [
            $deviceType => (int) $metrics
                ->where('event_name', 'page_view')
                ->where('device_type', $deviceType)
                ->sum('count'),
        ]);

        $businessStats = [
            'registrations' => User::query()->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
            'ads' => Ad::query()->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
            'messages' => Message::query()->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
            'proposals' => ServiceProposal::query()->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
            'orders' => ServiceOrder::query()->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
            'paid_orders' => ServiceOrder::query()
                ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->whereIn('payment_status', [ServiceOrder::PAYMENT_PAID, ServiceOrder::PAYMENT_RELEASED])
                ->count(),
        ];

        return view('admin.usage', compact(
            'period',
            'start',
            'end',
            'summary',
            'dailyUsage',
            'topPages',
            'deviceBreakdown',
            'businessStats'
        ));
    }
}
