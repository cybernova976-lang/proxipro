<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('homepage'), 'lastmod' => null, 'priority' => '1.0'],
            ['loc' => route('ads.index'), 'lastmod' => null, 'priority' => '0.9'],
            ['loc' => route('demand.create'), 'lastmod' => null, 'priority' => '0.8'],
            ['loc' => route('contact.index'), 'lastmod' => null, 'priority' => '0.5'],
            ['loc' => route('legal.terms'), 'lastmod' => null, 'priority' => '0.3'],
            ['loc' => route('legal.privacy'), 'lastmod' => null, 'priority' => '0.3'],
            ['loc' => route('legal.mentions'), 'lastmod' => null, 'priority' => '0.3'],
        ]);

        try {
            Ad::query()
                ->inEnabledCategories()
                ->where('status', 'active')
                ->select(['id', 'updated_at'])
                ->latest('updated_at')
                ->limit(20000)
                ->each(fn (Ad $ad) => $urls->push([
                    'loc' => route('ads.show', $ad),
                    'lastmod' => $ad->updated_at?->toAtomString(),
                    'priority' => '0.7',
                ]));

            User::query()
                ->where('is_active', true)
                ->where('profile_public', true)
                ->where(fn ($query) => $query
                    ->where('user_type', 'professionnel')
                    ->orWhere('is_service_provider', true))
                ->select(['id', 'updated_at'])
                ->latest('updated_at')
                ->limit(20000)
                ->each(fn (User $user) => $urls->push([
                    'loc' => route('profile.public', $user->id),
                    'lastmod' => $user->updated_at?->toAtomString(),
                    'priority' => '0.6',
                ]));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $body .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $body .= "  <url><loc>{$escape($url['loc'])}</loc>";
            if ($url['lastmod']) {
                $body .= "<lastmod>{$escape($url['lastmod'])}</lastmod>";
            }
            $body .= "<priority>{$url['priority']}</priority></url>\n";
        }

        $body .= '</urlset>';

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
