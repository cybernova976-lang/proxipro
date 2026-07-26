<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production') || ! $request->isMethodSafe()) {
            return $next($request);
        }

        $canonicalUrl = rtrim((string) config('app.url'), '/');
        $canonicalHost = strtolower((string) parse_url($canonicalUrl, PHP_URL_HOST));

        if ($canonicalHost === '' || strtolower($request->getHost()) === $canonicalHost) {
            return $next($request);
        }

        return redirect()->away($canonicalUrl.$request->getRequestUri(), 301);
    }
}
