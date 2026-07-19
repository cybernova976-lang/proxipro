<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Review;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomePageController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('feed');
        }

        $serviceCategories = \App\Support\MarketplaceCategoryRegistry::enabledServices();

        try {
            $stats = Cache::remember('homepage.stats', now()->addMinutes(5), fn () => [
                'totalAds' => Ad::inEnabledCategories()->where('status', 'active')->count(),
                'totalPros' => User::where('is_active', true)
                    ->where(fn ($query) => $query
                        ->where('user_type', 'professionnel')
                        ->orWhere('is_service_provider', true))
                    ->count(),
                'totalUsers' => User::where('is_active', true)->count(),
            ]);

            // Une seule requête agrégée remplace une requête par sous-catégorie.
            $countsByCategory = Cache::remember('homepage.category_counts', now()->addMinutes(5), fn () => Ad::query()
                ->inEnabledCategories()
                ->where('status', 'active')
                ->selectRaw('category, COUNT(*) as aggregate')
                ->groupBy('category')
                ->pluck('aggregate', 'category')
            );

            $latestAds = Ad::query()
                ->inEnabledCategories()
                ->where('status', 'active')
                ->with('user:id,name,avatar,identity_verified')
                ->latest()
                ->take(6)
                ->get();

            $featuredPros = User::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->where('user_type', 'professionnel')
                    ->orWhere('is_service_provider', true))
                ->whereNotNull('profession')
                ->withCount([
                    'ads as active_ads_count' => fn ($query) => $query->inEnabledCategories()->where('status', 'active'),
                    'verifiedReviewsReceived as verified_reviews_count',
                ])
                ->withAvg('verifiedReviewsReceived as verified_reviews_avg', 'rating')
                ->orderByDesc('identity_verified')
                ->orderByDesc('verified_reviews_count')
                ->orderByDesc('active_ads_count')
                ->take(6)
                ->get();

            $verifiedReviews = Review::query()
                ->whereNotNull('service_order_id')
                ->whereHas('serviceOrder', fn ($query) => $query
                    ->where('status', ServiceOrder::STATUS_COMPLETED)
                    ->where('payment_status', ServiceOrder::PAYMENT_RELEASED))
                ->whereNotNull('comment')
                ->where('comment', '!=', '')
                ->with([
                    'reviewer:id,name,avatar,city',
                    'reviewedUser:id,name,profession',
                    'ad:id,title',
                ])
                ->latest()
                ->take(3)
                ->get();
        } catch (\Throwable $exception) {
            report($exception);
            $stats = ['totalAds' => 0, 'totalPros' => 0, 'totalUsers' => 0];
            $countsByCategory = collect();
            $latestAds = collect();
            $featuredPros = collect();
            $verifiedReviews = collect();
        }

        $categoriesWithSubs = collect($serviceCategories)->map(function (array $category, string $name) use ($countsByCategory) {
            $subcategories = collect($category['subcategories'] ?? [])->map(fn (string $subcategory) => [
                'name' => $subcategory,
                'count' => (int) ($countsByCategory[$subcategory] ?? 0),
            ])->values()->all();

            return [
                'icon' => $category['fa_icon'] ?? 'fas fa-briefcase',
                'color' => $category['color'] ?? '#4f46e5',
                'description' => $category['description'] ?? '',
                'subs' => $subcategories,
                'total' => (int) ($countsByCategory[$name] ?? 0)
                    + collect($subcategories)->sum('count'),
            ];
        })->all();

        return view('pages.home', [
            ...$stats,
            'latestAds' => $latestAds,
            'featuredPros' => $featuredPros,
            'verifiedReviews' => $verifiedReviews,
            'categoriesWithSubs' => $categoriesWithSubs,
        ]);
    }
}
