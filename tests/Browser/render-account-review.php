<?php

// Local visual fixtures only: an isolated in-memory database, fake mail, no Stripe calls.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync', 'APP_URL' => 'http://127.0.0.1:8096'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::create('http://127.0.0.1:8096'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
// Even a cached local configuration must never point this utility at a real DB.
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null, 'mail.default' => 'array', 'queue.default' => 'sync',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
config(['webpush.vapid.public_key' => null, 'services.stripe.key' => 'pk_live_visual_only',
    'services.stripe.secret' => 'sk_live_visual_only', 'services.stripe.webhook_secret' => 'whsec_visual_only']);
foreach (['legal_entity_name' => 'PROKEJEM démo locale', 'legal_registration_number' => 'DEMO',
    'legal_address' => 'Adresse de démonstration', 'legal_publication_director' => 'Démo',
    'platform_public_url' => 'https://example.test', 'stripe_billing_portal_configured' => '1'] as $key => $value) {
    App\Models\Setting::set($key, $value);
}
App\Support\PlatformFeatures::setProSubscriptionsEnabled(true);
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8096');
Illuminate\Support\Facades\URL::forceScheme('http');
$user = App\Models\User::factory()->completeForVerification()->create([
    'name' => 'Camille Démonstration', 'email' => 'camille@example.test', 'avatar' => null,
    'is_service_provider' => true, 'user_type' => 'auto_entrepreneur', 'account_type' => 'professionnel',
    'identity_verified' => true, 'profile_completed' => true, 'pro_onboarding_completed' => true,
    'pro_service_categories' => ['Bricolage & Travaux'],
    'service_category' => 'Plombier', 'email_notifications' => true, 'newsletter_subscribed' => false,
    'stripe_id' => 'cus_visual_only',
]);
foreach (['Réparer une fuite sous un évier', 'Installation de luminaires', 'Entretien de votre jardin'] as $index => $title) {
    $ad = App\Models\Ad::create(['user_id' => $user->id, 'title' => $title, 'description' => 'Une description de démonstration pour examiner la présentation sur ordinateur et téléphone.',
        'main_category' => 'Bricolage & Travaux', 'category' => 'Plombier', 'publication_domain' => 'service',
        'location' => 'Paris', 'country' => 'France', 'city' => 'Paris', 'service_type' => $index ? 'offre' : 'demande',
        'status' => $index === 2 ? 'inactive' : 'active', 'views' => 12 + $index, 'expires_at' => now()->addDays(10)]);
}
App\Models\DemandDraft::create(['user_id' => $user->id, 'payload' => ['title' => 'Repeindre le salon avant mon déménagement', 'version' => 2, 'category' => 'Peintre'], 'current_step' => 3, 'last_activity_at' => now()->subHours(5)]);
$directory = dirname(__DIR__, 2).'/tmp/account-review';
if (! is_dir($directory)) { mkdir($directory, 0777, true); }
$pages = [];
$render = function (string $name, string $uri, bool $authenticated = true) use ($app, $kernel, $user, $directory, &$pages) {
    $app['auth']->forgetGuards();
    if ($authenticated) { $app['auth']->guard()->setUser($user->fresh()); }
    $request = Illuminate\Http\Request::create('http://127.0.0.1:8096'.$uri, 'GET');
    $response = $kernel->handle($request);
    file_put_contents($directory.'/'.$name.'.html', $response->getContent());
    $pages[] = ['name' => $name, 'uri' => $uri, 'status' => $response->getStatusCode()];
    echo $name.': '.$response->getStatusCode().PHP_EOL;
};
$render('homepage', '/', false);
$render('offers', '/offres-prestataires', false);
$render('login', '/login', false);
$render('register', '/register', false);
$render('guides', '/conseils', false);
$render('settings', '/settings');
$render('publications', '/ads/my-ads');
$render('feed', '/feed?mode=client');
$render('activity', '/home');
$render('demand', parse_url(route('demand.create'), PHP_URL_PATH));
$render('profile', parse_url(route('profile.show'), PHP_URL_PATH));
$render('messages', parse_url(route('messages.index'), PHP_URL_PATH));
$render('alerts', parse_url(route('saved-searches.index'), PHP_URL_PATH));
$render('pro-dashboard', parse_url(route('pro.dashboard'), PHP_URL_PATH));
$render('analytics', parse_url(route('pro.analytics'), PHP_URL_PATH));
$render('subscription-free', parse_url(route('pro.subscription'), PHP_URL_PATH));
$sub = App\Models\ProSubscription::create(['user_id' => $user->id, 'plan' => 'annual', 'amount' => 85, 'status' => 'active', 'stripe_status' => 'active',
    'starts_at' => now()->subDays(30), 'ends_at' => now()->addDays(335), 'stripe_subscription_id' => 'sub_visual_only', 'auto_renew' => true]);
$render('subscription-active', parse_url(route('pro.subscription'), PHP_URL_PATH));
$sub->update(['auto_renew' => false]);
$render('subscription-cancelled', parse_url(route('pro.subscription'), PHP_URL_PATH));
$sub->update(['status' => 'pending', 'stripe_status' => 'past_due']);
$render('subscription-past-due', parse_url(route('pro.subscription'), PHP_URL_PATH));
$offer = $user->ads()->where('status', 'active')->where('service_type', 'offre')->firstOrFail();
$render('catalog', '/ads', false);
$render('offer-detail', parse_url(route('ads.show', $offer), PHP_URL_PATH), false);
$render('offer-editor', parse_url(route('ads.edit', $offer), PHP_URL_PATH));
$render('contact', parse_url(route('contact.index'), PHP_URL_PATH), false);
$render('privacy', parse_url(route('legal.privacy'), PHP_URL_PATH), false);
$render('favorites', parse_url(route('saved-ads.index'), PHP_URL_PATH));
$render('orders', parse_url(route('service-orders.index'), PHP_URL_PATH));
foreach (['clients', 'quotes', 'quotes.create', 'invoices', 'invoices.create', 'account-status'] as $page) {
    $render('pro-'.str_replace('.', '-', $page), parse_url(route('pro.'.$page), PHP_URL_PATH));
}
$user->update(['newsletter_subscribed' => true]);
$unsubscribe = Illuminate\Support\Facades\URL::signedRoute('newsletter.unsubscribe', ['user' => $user]);
$render('newsletter', parse_url($unsubscribe, PHP_URL_PATH).'?'.parse_url($unsubscribe, PHP_URL_QUERY), false);
$user->forceFill(['role' => 'admin'])->save();
App\Models\DemandDraft::where('user_id', $user->id)->update(['last_activity_at' => now()->subHours(25)]);
$render('admin-reminders', parse_url(route('admin.emails.index'), PHP_URL_PATH));
file_put_contents($directory.'/manifest.json', json_encode($pages, JSON_PRETTY_PRINT));
exit(collect($pages)->contains(fn ($page) => $page['status'] !== 200) ? 1 : 0);
