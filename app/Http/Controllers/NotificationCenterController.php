<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Page « Notifications » : tout ce qui s'est passé sur le compte du membre,
 * de la plus récente à la plus ancienne.
 */
class NotificationCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->view('notifications.index', [
            'notifications' => $user->notifications()->latest()->paginate(20),
            'unreadCount' => $user->unreadNotifications()->count(),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** Marque la notification comme lue, puis mène à la page concernée. */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->to($this->safeTarget((string) ($notification->data['action_url'] ?? '')));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('success', 'Toutes vos notifications sont marquées comme lues.');
    }

    /** Une notification ne peut mener qu'à une page de Prokejem, jamais à un site extérieur. */
    private function safeTarget(string $url): string
    {
        if ($url === '' || $url === '#') {
            return route('notifications.index');
        }

        if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $allowed = array_filter([request()->getHost(), parse_url((string) config('app.url'), PHP_URL_HOST)]);

        return $host !== null && in_array($host, $allowed, true) ? $url : route('notifications.index');
    }
}
