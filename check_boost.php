<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = \App\Models\User::where('email', 'fatima.abdou009@gmail.com')->first();
if (!$u) { echo "User NOT FOUND\n"; exit; }

echo "=== USER ===\n";
echo "ID: {$u->id}\n";
echo "Name: {$u->name}\n";
echo "Plan: " . ($u->plan ?? 'NULL') . "\n";
echo "Sub end: " . ($u->subscription_end ?? 'NULL') . "\n";
echo "Type: " . ($u->user_type ?? 'NULL') . "\n";
echo "Provider: " . ($u->is_service_provider ? 'yes' : 'no') . "\n";

$ads = \App\Models\Ad::where('user_id', $u->id)->get();
echo "\n=== ADS ({$ads->count()}) ===\n";
foreach ($ads as $a) {
    echo "---\n";
    echo "Ad #{$a->id}: {$a->title}\n";
    echo "Status: {$a->status}\n";
    echo "Boosted: " . ($a->is_boosted ? 'yes' : 'no') . "\n";
    echo "Boost end: " . ($a->boost_end ?? 'NULL') . "\n";
    echo "Boost type: " . ($a->boost_type ?? 'NULL') . "\n";
    echo "Urgent: " . ($a->is_urgent ? 'yes' : 'no') . "\n";
    echo "Urgent until: " . ($a->urgent_until ?? 'NULL') . "\n";
    echo "Visibility: " . ($a->visibility ?? 'NULL') . "\n";
    echo "Service type: " . ($a->service_type ?? 'NULL') . "\n";
    echo "Lat: " . ($a->latitude ?? 'NULL') . "\n";
    echo "Lng: " . ($a->longitude ?? 'NULL') . "\n";
    echo "Created: " . ($a->created_at ?? 'NULL') . "\n";
}

// Check if the query would return this ad
echo "\n=== FEED QUERY TEST ===\n";
$feedAds = \App\Models\Ad::where('status', 'active')
    ->where(function($q) {
        $q->where(function($q2) {
            $q2->where('is_boosted', true)->where('boost_end', '>', now());
        })
        ->orWhereHas('user', function($q3) {
            $q3->whereNotNull('plan')
                ->where('plan', '!=', '')
                ->where('plan', '!=', 'free')
                ->where(function($q4) {
                    $q4->whereNull('subscription_end')
                        ->orWhere('subscription_end', '>', now());
                });
        });
    })
    ->where('user_id', $u->id)
    ->get();
echo "Ads matching feed query: {$feedAds->count()}\n";
foreach ($feedAds as $a) {
    echo "  - Ad #{$a->id}: {$a->title} (boosted={$a->is_boosted}, boost_end={$a->boost_end})\n";
}

// Also check total feed count
$totalFeed = \App\Models\Ad::where('status', 'active')
    ->where(function($q) {
        $q->where(function($q2) {
            $q2->where('is_boosted', true)->where('boost_end', '>', now());
        })
        ->orWhereHas('user', function($q3) {
            $q3->whereNotNull('plan')
                ->where('plan', '!=', '')
                ->where('plan', '!=', 'free')
                ->where(function($q4) {
                    $q4->whereNull('subscription_end')
                        ->orWhere('subscription_end', '>', now());
                });
        });
    })
    ->count();
echo "\nTotal ads in feed (no geo/type filter): {$totalFeed}\n";
