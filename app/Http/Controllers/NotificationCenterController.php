<?php

namespace App\Http\Controllers;

use App\Models\Ad;
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

        // L'annonce concernée a été supprimée depuis : la page visée n'existe plus.
        // Plutôt qu'une « Page introuvable », on le dit et on retire la notification.
        if ($this->adIsGone($notification->data)) {
            $notification->delete();

            return redirect()->route('notifications.index')
                ->with('success', 'Cette notification concernait une annonce qui a été supprimée : elle a été retirée de la liste.');
        }

        $notification->markAsRead();

        return redirect()->to($this->safeTarget((string) ($notification->data['action_url'] ?? '')));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('success', 'Toutes vos notifications sont marquées comme lues.');
    }

    /** La notification porte-t-elle sur une annonce qui n'existe plus ? */
    private function adIsGone(array $data): bool
    {
        $adId = $data['ad_id'] ?? null;

        return is_numeric($adId) && ! Ad::whereKey((int) $adId)->exists();
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
