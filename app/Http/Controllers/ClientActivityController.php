<?php

namespace App\Http\Controllers;

use App\Services\ClientActivityService;
use Illuminate\Http\Request;

class ClientActivityController extends Controller
{
    public function index(Request $request, ClientActivityService $activity)
    {
        $requests = $activity->requestsQuery($request->user())->paginate(10, ['*'], 'demandes_page')->withQueryString();
        $orders = $activity->ordersQuery($request->user())->paginate(10, ['*'], 'missions_page')->withQueryString();
        $requests->through(fn ($ad) => $activity->requestItem($ad));
        $orders->through(fn ($order) => $activity->orderItem($order));

        return response()->view('demands.activity', [
            'requests' => $requests, 'orders' => $orders, 'total' => $requests->total() + $orders->total(), 'pkRole' => 'client',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function refresh(Request $request, ClientActivityService $activity)
    {
        $pkClientActivity = $activity->summary($request->user());
        $changed = $request->query('revision') !== $pkClientActivity['revision'];

        return response()->json([
            'revision' => $pkClientActivity['revision'], 'changed' => $changed,
            'html' => $changed ? view('feed.partials.client-activity', compact('pkClientActivity'))->render() : null,
        ])->header('Cache-Control', 'private, no-store');
    }
}
