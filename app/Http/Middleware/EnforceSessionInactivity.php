<?php

namespace App\Http\Middleware;

use App\Services\SessionInactivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        $authenticated = $guard->check();
        $activity = app(SessionInactivity::class);

        if ($authenticated) {
            $last = $activity->lastInteraction($request);
            // A legacy persistent-login cookie must never bypass the idle limit.
            if ($guard->viaRemember() || ($last === null && $request->session()->get('inactivity_guard_started') === (int) $request->user()->id)
                || ($last !== null && now()->timestamp - $last >= SessionInactivity::TIMEOUT_SECONDS)) {
                $guard->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $message = 'Vous avez été déconnecté après une heure d’inactivité. Veuillez vous reconnecter.';
                $request->session()->flash('error', $message);

                return $request->expectsJson()
                    ? response()->json(['message' => $message, 'reason' => 'session_inactive', 'login_url' => route('login')], 401)
                        ->header('Cache-Control', 'no-store')
                    : redirect()->route('login')->with('error', $message)->header('Cache-Control', 'no-store');
            }

            $last ??= $activity->start($request);
            // Browser navigation initiated by a person counts, unlike AJAX polling.
            if ($request->isMethod('get') && $request->header('Sec-Fetch-User') === '?1'
                && $request->header('Sec-Fetch-Mode') === 'navigate') {
                $activity->touch($request, 0);
                $last = $activity->lastInteraction($request);
            }
            $request->attributes->set('session_idle_expires_at', $last + SessionInactivity::TIMEOUT_SECONDS);
        }

        $response = $next($request);

        // Authentication regenerates the session ID: seed its final ID after login.
        if (! $authenticated && $guard->check()) {
            $activity->start($request);
        }
        if ($authenticated || $guard->check()) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
