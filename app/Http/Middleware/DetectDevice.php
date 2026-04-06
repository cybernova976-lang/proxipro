<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class DetectDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent = new Agent();

        $deviceType = 'desktop';
        if ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        }

        view()->share('deviceType', $deviceType);
        view()->share('isMobile', $agent->isMobile());
        view()->share('isTablet', $agent->isTablet());
        view()->share('isDesktop', $agent->isDesktop());
        view()->share('deviceBrowser', $agent->browser());
        view()->share('devicePlatform', $agent->platform());

        return $next($request);
    }
}
