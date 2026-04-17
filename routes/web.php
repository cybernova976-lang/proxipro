<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\LostItemController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\StripeCheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\BoostController;
use App\Http\Controllers\SavedAdController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\QuoteToolController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Mail;

// Page d'accueil publique
Route::get('/', [HomePageController::class, 'index'])->name('homepage');

// Test route pour vérifier les cartes de publication
Route::get('/test-card', function() {
    return view('feed.test-card');
})->name('test-card');

// Test route for provider button
Route::get('/test-provider', function() {
    return view('test-provider-button');
})->middleware('auth')->name('test-provider');

if (app()->environment('local')) {
    Route::get('/test-mail', function () {
        $to = request('to', config('mail.from.address'));

        Mail::raw('Email de test depuis ProxiPro.', function ($message) use ($to) {
            $message->to($to)->subject('Email de test ProxiPro');
        });

        return "Mail sent to {$to}";
    })->middleware('throttle:5,1')->name('test-mail');
}

// Authentication Routes...
Route::get('login', function() {
    if (Auth::check()) {
        return redirect()->route('feed');
    }
    return view('auth.login');
})->name('login');
Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Registration Routes...
Route::get('register', function() {
    if (Auth::check()) {
        return redirect()->route('feed');
    }
    return view('auth.register');
})->name('register');
Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
    ->middleware('throttle:5,1');  // Max 5 attempts/minute

// Password Reset Routes...
Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:3,1')  // Max 3 password reset emails/minute
    ->name('password.email');
Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
    ->middleware('throttle:5,1')
    ->name('password.update');

// Password Confirmation Routes
Route::get('password/confirm', [App\Http\Controllers\Auth\ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
Route::post('password/confirm', [App\Http\Controllers\Auth\ConfirmPasswordController::class, 'confirm']);

// Social Authentication Routes (Google, Facebook)
Route::get('/auth/{provider}', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])->name('social.callback');

// Email Verification Code Routes (code-based, before login)
Route::get('/email/verify-code', [App\Http\Controllers\Auth\EmailVerificationCodeController::class, 'show'])
    ->name('verification.code.show');
Route::post('/email/verify-code', [App\Http\Controllers\Auth\EmailVerificationCodeController::class, 'verify'])
    ->middleware('throttle:5,1')
    ->name('verification.code.verify');
Route::post('/email/resend-code', [App\Http\Controllers\Auth\EmailVerificationCodeController::class, 'resend'])
    ->middleware('throttle:3,1')
    ->name('verification.code.resend');

// Email Verification Routes (legacy link-based)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify');
    })->name('verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    
    Route::post('/email/verification-notification', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.resend');
});

// Routes pour les annonces
Route::middleware(['auth'])->group(function () {
    Route::get('/ads/my-ads', [AdController::class, 'myAds'])->name('ads.myads');
    Route::post('/ads/store-from-popup', [AdController::class, 'storeFromPopup'])->name('ads.storeFromPopup');
});
Route::resource('ads', AdController::class);
Route::delete('/ads/{ad}/photos/{index}', [AdController::class, 'deletePhoto'])->middleware('auth')->name('ads.photos.delete');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home/export-transactions-pdf', [App\Http\Controllers\HomeController::class, 'exportTransactionsPdf'])->middleware('auth')->name('home.export-transactions-pdf');

// Dashboard AJAX sections (SPA navigation)
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::get('/profile-edit', [DashboardController::class, 'profileEdit'])->name('profile-edit');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::get('/points', [DashboardController::class, 'points'])->name('points');
    Route::get('/my-ads', [DashboardController::class, 'myAds'])->name('my-ads');
    Route::get('/messages', [DashboardController::class, 'messages'])->name('messages');
    Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions');
    Route::get('/create-ad', [DashboardController::class, 'createAd'])->name('create-ad');
});

// Factures d'achat (points & abonnements)
Route::get('/purchase/invoice', [PurchaseInvoiceController::class, 'download'])->middleware('auth')->name('purchase.invoice');

// Routes pour le feed (page d'accueil après connexion)
Route::middleware(['auth', 'geo'])->group(function () {
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');
    Route::get('/feed-test', [FeedController::class, 'indexTest'])->name('feed.test');
    
    // AJAX endpoints for category filtering
    Route::get('/feed/filter-ads', [FeedController::class, 'filterAds'])->name('feed.filter-ads');
    Route::get('/feed/subcategories', [FeedController::class, 'getSubcategories'])->name('feed.subcategories');
    Route::get('/feed/professionals', [FeedController::class, 'getProfessionalsByCategory'])->name('feed.professionals');
    
    // AJAX endpoint pour stocker la position du navigateur
    Route::post('/feed/store-browser-location', [FeedController::class, 'storeBrowserLocation'])->name('feed.store-browser-location');
    // AJAX endpoint pour mettre à jour le rayon
    Route::post('/feed/update-radius', [FeedController::class, 'updateRadius'])->name('feed.update-radius');
});

// Routes pour les annonces sauvegardées
Route::middleware(['auth'])->group(function () {
    Route::get('/saved-ads', [SavedAdController::class, 'index'])->name('saved-ads.index');
    Route::post('/ads/{ad}/toggle-save', [SavedAdController::class, 'toggle'])->name('ads.toggle-save');
    Route::get('/ads/{ad}/check-saved', [SavedAdController::class, 'check'])->name('ads.check-saved');
    Route::post('/ads/{ad}/candidature', [FeedController::class, 'submitCandidature'])->name('ads.candidature');
});

// Routes pour les commentaires
Route::get('/ads/{ad}/comments', [CommentController::class, 'index'])->name('comments.index');
Route::middleware(['auth'])->group(function () {
    Route::post('/ads/{ad}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// Routes pour devenir prestataire (Particulier → Particulier Prestataire)
Route::middleware(['auth'])->prefix('service-provider')->name('service-provider.')->group(function () {
    Route::get('/form', [ServiceProviderController::class, 'showForm'])->name('form');
    Route::get('/categories', [ServiceProviderController::class, 'getCategories'])->name('categories');
    Route::get('/my-services', [ServiceProviderController::class, 'getMyServices'])->name('my-services');
    Route::get('/mes-services', [ServiceProviderController::class, 'mesServices'])->name('mes-services');
    Route::post('/register', [ServiceProviderController::class, 'store'])->name('register');
    Route::post('/deactivate', [ServiceProviderController::class, 'deactivate'])->name('deactivate');
    Route::put('/service/{id}', [ServiceProviderController::class, 'updateService'])->name('update-service');
    Route::delete('/service/{id}', [ServiceProviderController::class, 'deleteService'])->name('delete-service');
    Route::post('/update-profile-fields', [ServiceProviderController::class, 'updateProfileFields'])->name('update-profile-fields');
    Route::get('/payment/success', [ServiceProviderController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('/payment/cancel', [ServiceProviderController::class, 'paymentCancel'])->name('payment.cancel');
});

// Routes pour devenir prestataire (Utilisateurs OAuth)
Route::middleware(['auth'])->prefix('become-provider')->name('become-provider.')->group(function () {
    Route::get('/data', [\App\Http\Controllers\BecomeProviderController::class, 'getFormData'])->name('data');
    Route::post('/store', [\App\Http\Controllers\BecomeProviderController::class, 'store'])->name('store');
    Route::get('/payment/success', [\App\Http\Controllers\BecomeProviderController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('/payment/cancel', [\App\Http\Controllers\BecomeProviderController::class, 'paymentCancel'])->name('payment.cancel');
});

// Routes pour publier une demande simplifiée + matching
Route::middleware(['auth'])->prefix('demande')->name('demand.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DemandController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\DemandController::class, 'store'])->name('store');
    Route::get('/{ad}/professionnels', [\App\Http\Controllers\DemandController::class, 'matching'])->name('matching');
    Route::get('/{ad}/matching-api', [\App\Http\Controllers\DemandController::class, 'matchingApi'])->name('matching.api');
});

// Routes de profil
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/save-categories', [ProfileController::class, 'saveCategories'])->name('profile.save-categories');
});
Route::get('/user/{id}', [ProfileController::class, 'publicProfile'])->name('profile.public');
Route::middleware(['auth'])->group(function () {
    Route::post('/user/{id}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});

// Routes de paramètres
Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/privacy', [SettingsController::class, 'updatePrivacy'])->name('settings.privacy');
    Route::post('/settings/delete-account', [SettingsController::class, 'deleteAccount'])->name('settings.delete-account');
});

// Routes objets perdus/trouvés
Route::middleware(['auth'])->group(function () {
    Route::get('/lost-items', [LostItemController::class, 'index'])->name('lost-items.index');
    Route::get('/lost-items/create', [LostItemController::class, 'create'])->name('lost-items.create');
    Route::post('/lost-items', [LostItemController::class, 'store'])->name('lost-items.store');
    Route::get('/lost-items/{id}', [LostItemController::class, 'show'])->name('lost-items.show');
    Route::get('/lost-items/{id}/edit', [LostItemController::class, 'edit'])->name('lost-items.edit');
    Route::put('/lost-items/{id}', [LostItemController::class, 'update'])->name('lost-items.update');
    Route::delete('/lost-items/{id}', [LostItemController::class, 'destroy'])->name('lost-items.destroy');
});

// Routes outils
Route::middleware(['auth'])->group(function () {
    Route::get('/tools/pdf-converter', [ToolController::class, 'pdfConverter'])->name('tools.pdf-converter');
    Route::post('/tools/pdf-converter', [ToolController::class, 'convertPdf'])->name('tools.convert-pdf');
    Route::get('/tools/image-compressor', [ToolController::class, 'imageCompressor'])->name('tools.image-compressor');
    Route::post('/tools/image-compressor', [ToolController::class, 'compressImage'])->name('tools.compress-image');
    Route::get('/tools/qr-generator', [ToolController::class, 'qrGenerator'])->name('tools.qr-generator');
    Route::post('/tools/qr-generator', [ToolController::class, 'generateQr'])->name('tools.generate-qr');
});

// Outil Devis/Facture - Landing SEO publique
Route::get('/creer-devis-facture-gratuit', [QuoteToolController::class, 'landing'])->name('quote-tool.landing');

// Outil Devis/Facture - Routes protégées
Route::middleware(['auth'])->prefix('outils')->name('quote-tool.')->group(function () {
    Route::get('/devis/creer', [QuoteToolController::class, 'createQuote'])->name('quote.create');
    Route::post('/devis/creer', [QuoteToolController::class, 'storeQuote'])->name('quote.store');
    Route::get('/facture/creer', [QuoteToolController::class, 'createInvoice'])->name('invoice.create');
    Route::post('/facture/creer', [QuoteToolController::class, 'storeInvoice'])->name('invoice.store');
    Route::get('/document/{token}/telecharger', [QuoteToolController::class, 'downloadDocument'])->name('download');
    Route::get('/acheter-credits', [QuoteToolController::class, 'purchaseCredits'])->name('credits');
    Route::post('/acheter-credits', [QuoteToolController::class, 'processPurchase'])->name('credits.purchase');
    Route::get('/achat-succes', [QuoteToolController::class, 'purchaseSuccess'])->name('credits.success');
    Route::get('/achat-annule', [QuoteToolController::class, 'purchaseCancel'])->name('credits.cancel');
});

// Routes de contact
Route::middleware(['auth'])->group(function () {
    Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
    Route::get('/contact/mes-messages', [ContactController::class, 'myMessages'])->name('contact.my-messages');
});

// Routes de vérification d'identité
Route::middleware(['auth'])->group(function () {
    Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification', [VerificationController::class, 'store'])->name('verification.store');
    Route::post('/verification/cancel', [VerificationController::class, 'cancel'])->name('verification.cancel');
    Route::post('/verification/resubmit', [VerificationController::class, 'resubmit'])->name('verification.resubmit');
    
    // Routes AJAX pour vérification avec paiement
    Route::get('/verification/status', [VerificationController::class, 'getStatus'])->name('verification.status');
    Route::post('/verification/submit-ajax', [VerificationController::class, 'storeAjax'])->name('verification.submit.ajax');
    Route::post('/verification/create-payment', [VerificationController::class, 'createPaymentSession'])->name('verification.create.payment');
    Route::post('/verification/pay-with-points', [VerificationController::class, 'payWithPoints'])->name('verification.pay.points');
    Route::get('/verification/payment/success/{id}', [VerificationController::class, 'paymentSuccess'])->name('verification.payment.success');
    Route::get('/verification/payment/cancel/{id}', [VerificationController::class, 'paymentCancel'])->name('verification.payment.cancel');
    
    // Notifications
    Route::post('/notifications/{id}/mark-read', function($id) {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['success' => true]);
    })->name('notifications.mark-read');

    Route::post('/notifications/mark-all-read', function() {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    })->name('notifications.mark-all-read');
});

// Routes de messagerie
Route::middleware(['auth'])->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/conversation', [MessageController::class, 'createConversation'])->name('messages.create.conversation');
    Route::post('/messages/{conversation}/block', [MessageController::class, 'block'])->name('messages.block');
    Route::post('/messages/{conversation}/unblock', [MessageController::class, 'unblock'])->name('messages.unblock');
    Route::delete('/messages/{conversation}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::put('/messages/item/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/messages/item/{message}', [MessageController::class, 'deleteMessage'])->name('messages.delete');
    Route::post('/messages/mark-all-read', [MessageController::class, 'markAllAsRead'])->name('messages.markAllRead');
    Route::get('/messages/{conversation}/poll', [MessageController::class, 'poll'])->name('messages.poll');
});

// Recherche d'utilisateurs (AJAX)
Route::middleware(['auth'])->get('/api/users/search', function (\Illuminate\Http\Request $request) {
    $q = $request->query('q', '');
    if (strlen($q) < 2) return response()->json([]);

    $users = \App\Models\User::where('id', '!=', auth()->id())
        ->where(function ($query) use ($q) {
            $query->where('name', 'LIKE', '%' . $q . '%')
                  ->orWhere('email', 'LIKE', '%' . $q . '%');
        })
        ->select('id', 'name', 'email', 'profile_photo')
        ->limit(10)
        ->get();

    return response()->json($users);
})->name('users.search');

// Routes de tarification (page unifiée)
Route::middleware(['auth'])->group(function () {
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing/subscribe', [PricingController::class, 'subscribe'])->name('pricing.subscribe');
    Route::post('/pricing/cancel', [PricingController::class, 'cancel'])->name('pricing.cancel');
    Route::post('/pricing/resume', [PricingController::class, 'resume'])->name('pricing.resume');
    Route::post('/pricing/purchase-points', [PricingController::class, 'purchasePoints'])->name('pricing.purchase-points');
});

// Routes Stripe Checkout (style ServicePro)
Route::middleware(['auth'])->group(function () {
    Route::post('/stripe/create-checkout', [StripeCheckoutController::class, 'createCheckout'])->name('stripe.create-checkout');
    Route::get('/stripe/success', [StripeCheckoutController::class, 'success'])->name('stripe.success');
    Route::get('/stripe/transactions', [StripeCheckoutController::class, 'transactions'])->name('stripe.transactions');
    Route::post('/social/share', [StripeCheckoutController::class, 'socialShare'])->name('social.share');
    Route::get('/social/status', [StripeCheckoutController::class, 'socialStatus'])->name('social.status');
});
Route::post('/stripe/webhook', [StripeCheckoutController::class, 'webhook'])->name('stripe.webhook');

// Routes de Boost d'annonces
Route::middleware(['auth'])->group(function () {
    Route::get('/ads/{ad}/boost', [BoostController::class, 'show'])->name('boost.show');
    Route::post('/ads/{ad}/boost/points', [BoostController::class, 'purchaseWithPoints'])->name('boost.purchase.points');
    Route::post('/ads/{ad}/boost/stripe', [BoostController::class, 'purchaseWithStripe'])->name('boost.purchase.stripe');
    Route::get('/boost/success', [BoostController::class, 'success'])->name('boost.success');
    Route::get('/ads/{ad}/boost/after-creation', [BoostController::class, 'afterCreation'])->name('boost.after-creation');
    Route::post('/ads/{ad}/refresh', [BoostController::class, 'refreshAd'])->name('ads.refresh');
    Route::post('/ads/{ad}/refresh/stripe', [BoostController::class, 'refreshAdStripe'])->name('ads.refresh.stripe');
    Route::get('/refresh/success', [BoostController::class, 'refreshAdSuccess'])->name('ads.refresh.success');
    Route::post('/ads/{ad}/make-urgent', [BoostController::class, 'makeUrgent'])->name('ads.make-urgent');
    Route::post('/ads/{ad}/make-urgent/stripe', [BoostController::class, 'makeUrgentStripe'])->name('ads.make-urgent.stripe');
    Route::get('/urgent/success', [BoostController::class, 'urgentSuccess'])->name('boost.urgent.success');
    Route::post('/ads/{ad}/report', [ReportController::class, 'store'])->name('ads.report');
});

// Routes d'abonnement (supprimées - redirigent vers la page Tarifs)
Route::middleware(['auth'])->group(function () {
    Route::get('/subscriptions', function () {
        return redirect()->route('pricing.index');
    })->name('subscriptions.index');
});

// Routes d'achat de points
Route::middleware(['auth'])->group(function () {
    Route::get('/buy-points', [PaymentController::class, 'buyPoints'])->name('buy-points');
    Route::post('/purchase-points', [PaymentController::class, 'purchasePoints'])->name('purchase-points');
    Route::get('/payment/points/success', [PaymentController::class, 'pointsSuccess'])->name('payment.points.success');
    Route::get('/payment/points/cancel', [PaymentController::class, 'pointsCancel'])->name('payment.points.cancel');
    Route::get('/points/history', [PaymentController::class, 'pointsHistory'])->name('points.history');
});

// Routes pour les points (protégées par auth)
Route::middleware('auth')->group(function () {
    Route::get('/points', [PointController::class, 'dashboard'])->name('points.dashboard');
    Route::get('/points/index', [PointController::class, 'dashboard'])->name('points.index');
    Route::get('/points/transactions', [PointController::class, 'transactions'])->name('points.transactions');
    Route::post('/points/share', [PointController::class, 'share'])->name('points.share');
    Route::post('/points/daily', [PointController::class, 'dailyEngagement'])->name('points.daily');
});

// ==========================================
// Routes Espace Pro (Dashboard Professionnel)
// ==========================================
Route::middleware(['auth'])->prefix('pro')->name('pro.')->group(function () {
    $ctrl = \App\Http\Controllers\ProDashboardController::class;

    // Onboarding page (full-page wizard)
    Route::get('/onboarding', [$ctrl, 'onboarding'])->name('onboarding');

    // Dashboard
    Route::get('/', [$ctrl, 'dashboard'])->name('dashboard');

    // Profil
    Route::get('/profile', [$ctrl, 'profile'])->name('profile');
    Route::get('/profile/edit', [$ctrl, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [$ctrl, 'updateProfile'])->name('profile.update');

    // Clients
    Route::get('/clients', [$ctrl, 'clients'])->name('clients');
    Route::post('/clients', [$ctrl, 'storeClient'])->name('clients.store');
    Route::put('/clients/{id}', [$ctrl, 'updateClient'])->name('clients.update');
    Route::delete('/clients/{id}', [$ctrl, 'deleteClient'])->name('clients.delete');

    // Devis
    Route::get('/quotes', [$ctrl, 'quotes'])->name('quotes');
    Route::get('/quotes/create', [$ctrl, 'createQuote'])->name('quotes.create');
    Route::post('/quotes', [$ctrl, 'storeQuote'])->name('quotes.store');
    Route::get('/quotes/{id}', [$ctrl, 'showQuote'])->name('quotes.show');
    Route::get('/quotes/{id}/edit', [$ctrl, 'editQuote'])->name('quotes.edit');
    Route::put('/quotes/{id}', [$ctrl, 'updateQuote'])->name('quotes.update');
    Route::put('/quotes/{id}/status', [$ctrl, 'updateQuoteStatus'])->name('quotes.status');
    Route::delete('/quotes/{id}', [$ctrl, 'deleteQuote'])->name('quotes.delete');
    Route::get('/quotes/{id}/download', [$ctrl, 'downloadQuote'])->name('quotes.download');
    Route::post('/quotes/{id}/send-email', [$ctrl, 'sendQuoteEmail'])->name('quotes.sendEmail');
    Route::post('/quotes/{id}/send-message', [$ctrl, 'sendQuoteMessage'])->name('quotes.sendMessage');

    // Factures
    Route::get('/invoices', [$ctrl, 'invoices'])->name('invoices');
    Route::get('/invoices/create/{quoteId?}', [$ctrl, 'createInvoice'])->name('invoices.create');
    Route::post('/invoices', [$ctrl, 'storeInvoice'])->name('invoices.store');
    Route::get('/invoices/{id}', [$ctrl, 'showInvoice'])->name('invoices.show');
    Route::get('/invoices/{id}/edit', [$ctrl, 'editInvoice'])->name('invoices.edit');
    Route::put('/invoices/{id}', [$ctrl, 'updateInvoice'])->name('invoices.update');
    Route::put('/invoices/{id}/status', [$ctrl, 'updateInvoiceStatus'])->name('invoices.status');
    Route::get('/invoices/{id}/download', [$ctrl, 'downloadInvoice'])->name('invoices.download');
    Route::delete('/invoices/{id}', [$ctrl, 'destroyInvoice'])->name('invoices.destroy');

    // Documents
    Route::get('/documents', [$ctrl, 'documents'])->name('documents');
    Route::post('/documents', [$ctrl, 'storeDocument'])->name('documents.store');
    Route::delete('/documents/{id}', [$ctrl, 'deleteDocument'])->name('documents.delete');

    // Statut du compte
    Route::get('/account-status', [$ctrl, 'accountStatus'])->name('account-status');
    Route::put('/account-status', [$ctrl, 'updateAccountStatus'])->name('account-status.update');

    // Abonnement
    Route::get('/subscription', [$ctrl, 'subscription'])->name('subscription');

    // Onboarding / Subscribe
    Route::get('/onboarding/data', [$ctrl, 'getOnboardingData'])->name('onboarding.data');
    Route::post('/onboarding/subscribe', [$ctrl, 'subscribeOnboarding'])->name('onboarding.subscribe');
    Route::get('/onboarding/payment/success', [$ctrl, 'onboardingPaymentSuccess'])->name('onboarding.payment.success');
    Route::get('/onboarding/payment/cancel', [$ctrl, 'onboardingPaymentCancel'])->name('onboarding.payment.cancel');

    // Statistiques (page complète)
    Route::get('/analytics', [$ctrl, 'analytics'])->name('analytics');

    // Agenda / Planning
    Route::get('/agenda', [$ctrl, 'agenda'])->name('agenda');

    // Stats API (AJAX)
    Route::get('/stats', [$ctrl, 'getStats'])->name('stats');
});

// Routes Admin (protégées par middleware admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/users/{id}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    
    // Gestion des annonces
    Route::get('/ads', [AdminController::class, 'ads'])->name('admin.ads');
    Route::get('/ads/{id}', [AdminController::class, 'showAd'])->name('admin.ads.show');
    Route::put('/ads/{id}', [AdminController::class, 'updateAd'])->name('admin.ads.update');
    Route::delete('/ads/{id}', [AdminController::class, 'deleteAd'])->name('admin.ads.delete');
    
    // Gestion des boosts / urgents
    Route::get('/boosts', [AdminController::class, 'boosts'])->name('admin.boosts');
    Route::post('/ads/{id}/grant-boost', [AdminController::class, 'grantBoost'])->name('admin.ads.grant-boost');
    Route::post('/ads/{id}/grant-urgent', [AdminController::class, 'grantUrgent'])->name('admin.ads.grant-urgent');
    Route::post('/ads/{id}/revoke-boost', [AdminController::class, 'revokeBoost'])->name('admin.ads.revoke-boost');
    Route::post('/ads/{id}/revoke-urgent', [AdminController::class, 'revokeUrgent'])->name('admin.ads.revoke-urgent');

    // Comptes supprimés
    Route::get('/deleted-accounts', [AdminController::class, 'deletedAccounts'])->name('admin.deleted-accounts');
    Route::post('/deleted-accounts/{id}/restore', [AdminController::class, 'restoreAccount'])->name('admin.accounts.restore');
    Route::delete('/deleted-accounts/{id}/force', [AdminController::class, 'forceDeleteAccount'])->name('admin.accounts.force-delete');
    
    // Gestion des abonnements
    Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('admin.subscriptions');
    Route::put('/subscriptions/{id}', [AdminController::class, 'updateSubscription'])->name('admin.subscriptions.update');
    Route::post('/subscriptions/{id}/grant-premium', [AdminController::class, 'grantPremium'])->name('admin.subscriptions.grant-premium');
    Route::post('/subscriptions/{id}/suspend', [AdminController::class, 'suspendSubscription'])->name('admin.subscriptions.suspend');
    Route::post('/subscriptions/{id}/cancel', [AdminController::class, 'cancelSubscription'])->name('admin.subscriptions.cancel');
    
    // Statistiques
    Route::get('/stats', [AdminController::class, 'stats'])->name('admin.stats');
    
    // Paramètres
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/settings/general', [AdminController::class, 'updateSettingsGeneral'])->name('admin.settings.general');
    Route::post('/settings/ads', [AdminController::class, 'updateSettingsAds'])->name('admin.settings.ads');
    Route::post('/settings/points', [AdminController::class, 'updateSettingsPoints'])->name('admin.settings.points');
    Route::post('/settings/email', [AdminController::class, 'updateSettingsEmail'])->name('admin.settings.email');
    Route::post('/settings/security', [AdminController::class, 'updateSettingsSecurity'])->name('admin.settings.security');
    Route::post('/settings/system', [AdminController::class, 'updateSettingsSystem'])->name('admin.settings.system');

    
    // Gestion des administrateurs (réservé à l'admin principal)
    Route::get('/admins', [AdminController::class, 'admins'])->name('admin.admins');
    Route::post('/admins/{id}/promote', [AdminController::class, 'promoteToAdmin'])->name('admin.admins.promote');
    Route::post('/admins/{id}/revoke', [AdminController::class, 'revokeAdmin'])->name('admin.admins.revoke');
    Route::put('/admins/{id}/privileges', [AdminController::class, 'updateAdminPrivileges'])->name('admin.admins.privileges');
    
    // Gestion des vérifications de profil
    Route::get('/verifications', [AdminController::class, 'verifications'])->name('admin.verifications');
    Route::get('/verifications/{id}', [AdminController::class, 'showVerification'])->name('admin.verifications.show');
    Route::post('/verifications/{id}/approve', [AdminController::class, 'approveVerification'])->name('admin.verifications.approve');
    Route::post('/verifications/{id}/reject', [AdminController::class, 'rejectVerification'])->name('admin.verifications.reject');
    Route::post('/verifications/{id}/review-documents', [AdminController::class, 'reviewDocuments'])->name('admin.verifications.review-documents');
    Route::post('/verifications/{id}/return', [AdminController::class, 'returnVerification'])->name('admin.verifications.return');
    
    // Gestion des publicités
    Route::get('/advertisements', [AdminController::class, 'advertisements'])->name('admin.advertisements');
    Route::get('/advertisements/create', [AdminController::class, 'createAdvertisement'])->name('admin.advertisements.create');
    Route::post('/advertisements', [AdminController::class, 'storeAdvertisement'])->name('admin.advertisements.store');
    Route::get('/advertisements/{id}/edit', [AdminController::class, 'editAdvertisement'])->name('admin.advertisements.edit');
    Route::put('/advertisements/{id}', [AdminController::class, 'updateAdvertisement'])->name('admin.advertisements.update');
    Route::delete('/advertisements/{id}', [AdminController::class, 'deleteAdvertisement'])->name('admin.advertisements.delete');
    Route::post('/advertisements/{id}/toggle', [AdminController::class, 'toggleAdvertisement'])->name('admin.advertisements.toggle');

    // Gestion des signalements
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/reports/{id}', [AdminController::class, 'showReport'])->name('admin.reports.show');
    Route::post('/reports/{id}/resolve', [AdminController::class, 'resolveReport'])->name('admin.reports.resolve');
    Route::post('/reports/{id}/dismiss', [AdminController::class, 'dismissReport'])->name('admin.reports.dismiss');
    Route::delete('/reports/{id}', [AdminController::class, 'deleteReport'])->name('admin.reports.delete');

    // Gestion des messages de contact
    Route::get('/contact-messages', [AdminController::class, 'contactMessages'])->name('admin.contact-messages');
    Route::get('/contact-messages/{id}', [AdminController::class, 'showContactMessage'])->name('admin.contact-messages.show');
    Route::post('/contact-messages/{id}/reply', [AdminController::class, 'replyContactMessage'])->name('admin.contact-messages.reply');
    Route::post('/contact-messages/{id}/status', [AdminController::class, 'updateContactMessageStatus'])->name('admin.contact-messages.status');
    Route::delete('/contact-messages/{id}', [AdminController::class, 'deleteContactMessage'])->name('admin.contact-messages.delete');
});

// Diagnostic boost (admin uniquement) - TEMPORAIRE
Route::get('/boost-diagnostic', function () {
    if (!Auth::check() || (Auth::user()->role ?? '') !== 'admin') {
        abort(403);
    }

    $email = request('email', 'fatima.abdou009@gmail.com');
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) return response()->json(['error' => 'User not found: ' . $email]);

    $userData = [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'plan' => $user->plan,
        'subscription_end' => $user->subscription_end,
        'user_type' => $user->user_type,
        'is_service_provider' => $user->is_service_provider ?? false,
    ];

    $ads = \App\Models\Ad::where('user_id', $user->id)->get()->map(function($a) {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'status' => $a->status,
            'is_boosted' => $a->is_boosted,
            'boost_end' => $a->boost_end ? $a->boost_end->toDateTimeString() : null,
            'boost_end_is_future' => $a->boost_end ? $a->boost_end->isFuture() : false,
            'boost_type' => $a->boost_type,
            'is_urgent' => $a->is_urgent,
            'urgent_until' => $a->urgent_until ? $a->urgent_until->toDateTimeString() : null,
            'visibility' => $a->visibility,
            'service_type' => $a->service_type,
            'latitude' => $a->latitude,
            'longitude' => $a->longitude,
            'created_at' => $a->created_at->toDateTimeString(),
        ];
    });

    // Simulate feed query for this user's ads
    $feedQuery = \App\Models\Ad::where('status', 'active')
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
        ->where('user_id', $user->id)
        ->get();

    $feedMatchIds = $feedQuery->pluck('id')->toArray();

    // Total feed count
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
        })->count();

    return response()->json([
        'now' => now()->toDateTimeString(),
        'timezone' => config('app.timezone'),
        'user' => $userData,
        'ads' => $ads,
        'ads_matching_feed_query' => $feedMatchIds,
        'total_feed_ads' => $totalFeed,
    ], 200, [], JSON_PRETTY_PRINT);
});

// Diagnostic de stockage (admin uniquement)
Route::get('/storage-diagnostic', function () {
    if (!Auth::check() || (Auth::user()->role ?? '') !== 'admin') {
        abort(403);
    }

    $defaultDisk = config('filesystems.default', 'public');
    $disk = \Illuminate\Support\Facades\Storage::disk($defaultDisk);
    $driver = config('filesystems.disks.' . $defaultDisk . '.driver');
    $url = config('filesystems.disks.' . $defaultDisk . '.url');

    $results = [
        'default_disk' => $defaultDisk,
        'disk_driver' => $driver,
        'disk_url' => $url,
        'env_FILESYSTEM_DISK' => config('filesystems.default'),
        'env_AWS_URL' => config('filesystems.disks.s3.url'),
        'env_AWS_ENDPOINT' => config('filesystems.disks.s3.endpoint') ? '***set***' : 'NOT SET',
        'env_AWS_ACCESS_KEY_ID' => config('filesystems.disks.s3.key') ? '***set***' : 'NOT SET',
    ];

    try {
        $testFile = '_diagnostic_test_' . time() . '.txt';
        $disk->put($testFile, 'test-' . now());
        $results['write_test'] = 'OK';
        $results['file_url'] = $disk->url($testFile);
        $content = $disk->get($testFile);
        $results['read_test'] = $content ? 'OK' : 'FAILED';
        $disk->delete($testFile);
        $results['delete_test'] = 'OK';
    } catch (\Exception $e) {
        $results['storage_error'] = $e->getMessage();
    }

    return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
})->middleware('auth');

// Route de secours pour les images (disque local uniquement)
// Quand FILESYSTEM_DISK=s3, les images sont servies directement par R2/S3.
Route::get('storage/{path}', function ($path) {
    // Sécurité : empêcher la traversée de répertoire
    if (str_contains($path, '..')) {
        abort(403);
    }

    $defaultDisk = config('filesystems.default', 'public');
    $defaultDriver = config('filesystems.disks.' . $defaultDisk . '.driver', 'local');

    // Si le disque par défaut est S3, rediriger vers l'URL S3
    if ($defaultDriver === 's3') {
        return redirect(\Illuminate\Support\Facades\Storage::disk($defaultDisk)->url($path));
    }

    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath);
})->where('path', '.*');

// Pages légales
Route::get('/mentions-legales', function() {
    return view('legal.mentions');
})->name('legal.mentions');

Route::get('/conditions-utilisation', function() {
    return view('legal.terms');
})->name('legal.terms');

Route::get('/politique-confidentialite', function() {
    return view('legal.privacy');
})->name('legal.privacy');

Route::get('/politique-cookies', function() {
    return view('legal.cookies');
})->name('legal.cookies');

