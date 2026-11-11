@php
    $managedSubscription = $user->subscriptionForManagement();
    $activeAdsCount = $user->ads()->marketplaceActive()->count();
    $adLimit = $user->getMaxActiveAds();
@endphp
<link rel="stylesheet" href="{{ asset('css/account-center.css') }}?v={{ filemtime(public_path('css/account-center.css')) }}">
<div class="account-center">
    <header class="account-heading">
        <div><span class="account-eyebrow">Votre espace personnel</span><h1>Mon compte, mes choix</h1><p>Abonnement, notifications et confidentialité : les réglages essentiels au même endroit.</p></div>
        <a href="{{ route('contact.index') }}" class="btn btn-outline-primary">Besoin d’aide ?</a>
    </header>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Vérifiez les informations suivantes :</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="account-summary">
        <a href="{{ route('pro.subscription') }}"><span>Abonnement et facturation</span><strong>{{ $managedSubscription?->isActive() ? 'Premium actif' : ($managedSubscription?->hasOpenBilling() ? 'Facturation à vérifier' : 'Compte gratuit') }}</strong><small>Formule, factures et résiliation →</small></a>
        <a href="{{ route('ads.myads') }}"><span>Mes publications</span><strong>{{ $activeAdsCount }} / {{ $adLimit }} annonces actives</strong><small>Retrouver mes annonces et mon brouillon →</small></a>
        <a href="#notifications"><span>Mes communications</span><strong>{{ $user->email_notifications ? 'Emails activés' : 'Emails désactivés' }}</strong><small>Choisir ce que je reçois →</small></a>
    </div>
    <div class="account-layout">
        <nav class="account-nav" aria-label="Rubriques des paramètres">
            <a href="#notifications">Notifications et newsletters</a><a href="#privacy">Confidentialité</a><a href="#password">Mot de passe</a><a href="#delete">Supprimer mon compte</a>
        </nav>
        <div class="account-sections">
            <section id="notifications" class="account-section" aria-labelledby="notifications-title">
                <h2 id="notifications-title">Choisir mes notifications</h2><p>Vous gardez la main. Ces préférences sont gratuites, quelle que soit votre formule.</p>
                <form action="{{ route('settings.notifications') }}" method="POST">
                    @csrf
                    <label class="account-choice" for="email_notifications"><span><strong>Notifications par email</strong><small>Messages, candidatures et suivi de votre activité. Ce réglage suspend aussi les newsletters et rappels ci-dessous.</small></span><input type="checkbox" id="email_notifications" name="email_notifications" value="1" @checked(old('email_notifications', $user->email_notifications))></label>
                    <label class="account-choice" for="draft_reminders_enabled"><span><strong>Rappel de demande inachevée</strong><small>Au maximum un rappel par brouillon, après 24 heures sans modification. Aucune publication automatique.</small></span><input type="checkbox" id="draft_reminders_enabled" name="draft_reminders_enabled" value="1" @checked(old('draft_reminders_enabled', $user->draft_reminders_enabled))></label>
                    <label class="account-choice" for="pro_notifications_email"><span><strong>Alertes de recherche et opportunités</strong><small>Les nouvelles annonces correspondant à mes recherches enregistrées ou à mes services.</small></span><input type="checkbox" id="pro_notifications_email" name="pro_notifications_email" value="1" @checked(old('pro_notifications_email', $user->pro_notifications_email))></label>
                    <label class="account-choice" for="newsletter_subscribed"><span><strong>Newsletters et nouveautés PROKEJEM</strong><small>Je souhaite recevoir les actualités, conseils et offres de la plateforme. Je peux me désinscrire depuis chaque newsletter.</small></span><input type="checkbox" id="newsletter_subscribed" name="newsletter_subscribed" value="1" @checked(old('newsletter_subscribed', $user->newsletter_subscribed))></label>
                    <p class="account-note">Les messages nécessaires à la sécurité du compte et les communications de facturation restent distincts. Les notifications dans votre espace personnel restent disponibles.</p>
                    <button type="submit" class="btn btn-primary">Enregistrer mes préférences</button>
                </form>
                <div class="account-device">@include('partials.push-notification-controls')</div>
                <p class="account-note">Les notifications du navigateur se règlent séparément sur chaque appareil. <a href="{{ route('saved-searches.index') }}">Gérer mes alertes de recherche</a>.</p>
            </section>
            <section id="privacy" class="account-section" aria-labelledby="privacy-title">
                <h2 id="privacy-title">Ma confidentialité</h2><p>Choisissez les informations visibles sur votre profil.</p>
                <form action="{{ route('settings.privacy') }}" method="POST">
                    @csrf
                    <label class="account-choice" for="profile_public"><span><strong>Rendre mon profil public</strong><small>Permettre aux autres membres de consulter mon profil.</small></span><input type="checkbox" id="profile_public" name="profile_public" value="1" @checked($user->profile_public)></label>
                    <label class="account-choice" for="show_email"><span><strong>Afficher mon adresse email</strong><small>Sur mon profil public.</small></span><input type="checkbox" id="show_email" name="show_email" value="1" @checked($user->show_email)></label>
                    <label class="account-choice" for="show_phone"><span><strong>Afficher mon téléphone</strong><small>Sur mon profil et mes annonces.</small></span><input type="checkbox" id="show_phone" name="show_phone" value="1" @checked($user->show_phone)></label>
                    <button type="submit" class="btn btn-primary">Enregistrer ma confidentialité</button>
                </form>
            </section>
            <section id="password" class="account-section" aria-labelledby="password-title">
                <h2 id="password-title">Sécurité du compte</h2>
                @if($user->isOAuthUser())
                    <p>Vous vous connectez avec votre compte {{ ucfirst($user->provider ?? 'externe') }}. Le mot de passe se gère auprès de ce service.</p>
                @else
                    <form action="{{ route('settings.password') }}" method="POST">
                        @csrf
                        <div class="mb-3"><label for="current_password" class="form-label">Mot de passe actuel</label><input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required></div>
                        <div class="mb-3"><label for="password" class="form-label">Nouveau mot de passe</label><input type="password" class="form-control" id="password" name="password" autocomplete="new-password" minlength="8" aria-describedby="password-help" required><small id="password-help">Au moins 8 caractères.</small></div>
                        <div class="mb-3"><label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
                        <button type="submit" class="btn btn-primary">Changer mon mot de passe</button>
                    </form>
                @endif
            </section>
            <section id="delete" class="account-section account-section-danger" aria-labelledby="delete-title">
                <h2 id="delete-title">Supprimer mon compte</h2>
                <p>Cette action retire votre profil et vos publications et supprime l’accès à votre espace personnel. Elle est irréversible.</p>
                <div class="account-note"><strong>Vous souhaitez seulement arrêter de payer ?</strong> <a href="{{ route('pro.subscription') }}">Résilier mon abonnement</a> vous permet de conserver votre compte gratuit et l’accès Premium jusqu’à la fin de la période payée.</div>
                <p>En supprimant le compte, les abonnements Stripe associés sont arrêtés immédiatement, avant la suppression. Si cet arrêt ne peut pas être confirmé, votre compte est conservé. La suppression ne déclenche pas de remboursement automatique. <a href="{{ route('legal.privacy') }}">Consulter la politique de confidentialité</a>.</p>
                <form action="{{ route('settings.delete-account') }}" method="POST" onsubmit="return confirm('Supprimer définitivement votre compte et arrêter ses abonnements ?');">
                    @csrf
                    <div class="mb-3"><label for="settings_reason" class="form-label">La raison de votre départ (facultatif)</label><textarea name="reason" id="settings_reason" class="form-control" rows="2" maxlength="500">{{ old('reason') }}</textarea></div>
                    @unless($user->isOAuthUser())<div class="mb-3"><label for="settings_delete_password" class="form-label">Confirmez votre mot de passe</label><input type="password" id="settings_delete_password" name="password" class="form-control" autocomplete="current-password" required></div>@endunless
                    <label class="account-choice" for="settings_confirm_delete"><span><strong>Je confirme la suppression définitive de mon compte.</strong></span><input type="checkbox" name="confirm_delete" id="settings_confirm_delete" value="1" required></label>
                    <button type="submit" class="btn btn-outline-danger" id="settingsDeleteBtn">Supprimer définitivement mon compte</button>
                </form>
            </section>
        </div>
    </div>
</div>
