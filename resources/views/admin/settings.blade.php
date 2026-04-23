@extends('admin.layouts.app')

@section('title', 'Paramètres')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">
            <i class="fas fa-cog text-secondary me-2"></i>Paramètres de la Plateforme
        </h2>
        <p class="text-muted mb-0">Configuration générale de {{ $settings['general']['site_name'] ?? 'ProxiPro' }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <!-- Paramètres généraux -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-sliders-h me-2 text-primary"></i>
                    Paramètres Généraux
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.general') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Nom de la plateforme</label>
                        <input type="text" class="form-control" name="site_name" value="{{ $settings['general']['site_name'] ?? 'ProxiPro' }}">
                        <small class="text-muted">Ce nom sera affiché dans tout le site</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email de contact public</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ $settings['general']['contact_email'] ?? 'contact@ProxiPro.com' }}">
                        <small class="text-muted">Adresse affichée aux utilisateurs pour joindre la plateforme.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mode maintenance</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" {{ ($settings['general']['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenanceMode">Activer le mode maintenance</label>
                        </div>
                        <small class="text-muted">Le site sera inaccessible aux utilisateurs pendant la maintenance</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Paramètres des annonces -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn me-2 text-success"></i>
                    Paramètres des Annonces
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.ads') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Annonces gratuites par mois (plan FREE)</label>
                        <input type="number" class="form-control" name="free_ads_limit" value="{{ $settings['ads']['free_ads_limit'] ?? '3' }}" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Durée de validité des annonces (jours)</label>
                        <input type="number" class="form-control" name="ad_validity_days" value="{{ $settings['ads']['ad_validity_days'] ?? '30' }}" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Modération automatique</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_moderation" id="autoModeration" {{ ($settings['ads']['auto_moderation'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="autoModeration">Approuver automatiquement les nouvelles annonces</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Paramètres des points -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-coins me-2 text-warning"></i>
                    Système de Points
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.points') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Points par inscription</label>
                        <input type="number" class="form-control" name="signup_points" value="{{ $settings['points']['signup_points'] ?? '50' }}" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Points par connexion quotidienne</label>
                        <input type="number" class="form-control" name="daily_login_points" value="{{ $settings['points']['daily_login_points'] ?? '5' }}" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Points par partage social</label>
                        <input type="number" class="form-control" name="share_points" value="{{ $settings['points']['share_points'] ?? '10' }}" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Coût en points par message</label>
                        <input type="number" class="form-control" name="message_cost" value="{{ $settings['points']['message_cost'] ?? '1' }}" min="0">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Paramètres email -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2 text-info"></i>
                        Configuration Email
                    </h5>
                    @php
                        $mailBadgeClass = ($mailSummary['is_complete'] ?? false) ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning border-warning-subtle';
                        $mailBadgeLabel = ($mailSummary['is_complete'] ?? false) ? 'Config OK' : 'Config incomplète';
                    @endphp
                    <span class="badge rounded-pill border {{ $mailBadgeClass }} px-3 py-2">{{ $mailBadgeLabel }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="rounded-3 border bg-light-subtle p-3 mb-4">
                    <h6 class="fw-semibold mb-3">
                        <i class="fas fa-life-ring me-2 text-info"></i>Repères Support & Contact
                    </h6>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase fw-semibold mb-1">Contact public</div>
                            <div>{{ $settings['general']['contact_email'] ?? 'contact@ProxiPro.com' }}</div>
                            <div class="text-muted">Visible sur le site</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase fw-semibold mb-1">Expéditeur</div>
                            <div>{{ $settings['email']['mail_from_address'] ?? config('mail.from.address') ?: 'Non défini' }}</div>
                            <div class="text-muted">Adresse utilisée pour envoyer les mails</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase fw-semibold mb-1">Réponse</div>
                            <div>{{ $mailSummary['reply_to_address'] ?? 'Non défini' }}</div>
                            <div class="text-muted">Adresse qui reçoit les réponses @if($mailSummary['reply_to_uses_admin_fallback'] ?? false)(fallback admin actif)@endif</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase fw-semibold mb-1">Administration</div>
                            <div>{{ $mailSummary['admin_email'] ?? 'Non défini' }}</div>
                            <div class="text-muted">Adresse par défaut pour les alertes et tests</div>
                        </div>
                    </div>
                    @if(!($mailSummary['is_complete'] ?? false))
                        <div class="alert alert-warning mt-3 mb-0 py-2 px-3 small">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Configuration à compléter avant exploitation complète des notifications.
                        </div>
                    @endif
                </div>

                <form action="{{ route('admin.settings.email') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Canal d'envoi</label>
                        <select class="form-select" name="mail_driver">
                            <option value="failover" {{ ($settings['email']['mail_driver'] ?? config('mail.default')) == 'failover' ? 'selected' : '' }}>Failover Brevo + SMTP</option>
                            <option value="brevo" {{ ($settings['email']['mail_driver'] ?? config('mail.default')) == 'brevo' ? 'selected' : '' }}>Brevo API</option>
                            <option value="brevo_secondary" {{ ($settings['email']['mail_driver'] ?? config('mail.default')) == 'brevo_secondary' ? 'selected' : '' }}>Brevo API secondaire</option>
                            <option value="smtp" {{ ($settings['email']['mail_driver'] ?? config('mail.default')) == 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="mailgun" {{ ($settings['email']['mail_driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                            <option value="ses" {{ ($settings['email']['mail_driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                            <option value="log" {{ ($settings['email']['mail_driver'] ?? '') == 'log' ? 'selected' : '' }}>Log (dev)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse d'envoi</label>
                        <input type="email" class="form-control" name="mail_from_address" value="{{ $settings['email']['mail_from_address'] ?? config('mail.from.address') }}">
                        <small class="text-muted">Adresse vue par le destinataire. Elle doit correspondre à une adresse validée chez Brevo.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nom d'envoi</label>
                        <input type="text" class="form-control" name="mail_from_name" value="{{ $settings['email']['mail_from_name'] ?? config('mail.from.name') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse de réponse</label>
                        <input type="email" class="form-control" name="mail_reply_to_address" value="{{ $settings['email']['mail_reply_to_address'] ?? config('mail.reply_to.address') ?? ($settings['email']['mail_admin_address'] ?? config('mail.admin_email')) }}" placeholder="Laisser vide pour reprendre l'adresse admin">
                        <small class="text-muted">Si ce champ est vide, l'adresse admin sera utilisée automatiquement.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nom de réponse</label>
                        <input type="text" class="form-control" name="mail_reply_to_name" value="{{ $settings['email']['mail_reply_to_name'] ?? config('mail.reply_to.name') ?? ($settings['email']['mail_from_name'] ?? config('mail.from.name')) }}" placeholder="Laisser vide pour reprendre le nom d'envoi">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse admin</label>
                        <input type="email" class="form-control" name="mail_admin_address" value="{{ $settings['email']['mail_admin_address'] ?? config('mail.admin_email') }}">
                        <small class="text-muted">Destinataire par défaut des tests et des notifications administratives.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notifications par email</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="email_new_user" id="emailNewUser" {{ ($settings['email']['email_new_user'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="emailNewUser">Nouvelle inscription</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="email_new_ad" id="emailNewAd" {{ ($settings['email']['email_new_ad'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="emailNewAd">Nouvelle annonce</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="email_new_message" id="emailNewMessage" {{ ($settings['email']['email_new_message'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="emailNewMessage">Nouveau message</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </form>

                <hr>

                <form action="{{ route('admin.settings.email.test') }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">Envoyer un e-mail de test</label>
                        <input type="email" class="form-control" name="test_email" value="{{ $settings['email']['mail_admin_address'] ?? config('mail.admin_email') }}" placeholder="admin@example.com">
                        <small class="text-muted">Laissez cette adresse ou saisissez un autre destinataire temporaire.</small>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-info w-100">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer un test
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Informations système -->
<div class="row mt-4">
    <!-- Sécurité & Anti-bot -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2 text-danger"></i>
                    Sécurité & Anti-bot
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.security') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0 fw-semibold">Vérification e-mail par code</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_verification_enabled" id="emailVerificationEnabled" role="switch"
                                    {{ ($settings['security']['email_verification_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                        
                        <div class="alert alert-light border py-2 px-3 mb-3" style="font-size: 0.85rem;">
                            <i class="fas fa-info-circle text-primary me-1"></i>
                            Lorsque cette option est <strong>activée</strong>, un code à 6 chiffres est envoyé par e-mail à chaque inscription. L'utilisateur doit le saisir <strong>avant de pouvoir se connecter</strong>.
                        </div>

                        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 0.82rem;" id="verificationWarning" 
                            style="{{ ($settings['security']['email_verification_enabled'] ?? '1') == '1' ? '' : 'display:none' }}">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Attention :</strong> Si les e-mails ne fonctionnent pas correctement (SMTP mal configuré, erreurs d'envoi), les utilisateurs ne pourront pas s'inscrire. Désactivez cette option en cas de problème.
                        </div>
                    </div>

                    <hr>

                    <div class="mb-0">
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            <i class="fas fa-check-circle text-success me-1"></i> Protection honeypot (toujours active)<br>
                            <i class="fas fa-check-circle text-success me-1"></i> Vérification de timing anti-bot (toujours active)<br>
                            <i class="fas fa-check-circle text-success me-1"></i> Limitation de tentatives : 5/min (toujours active)
                        </p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Informations système -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-server me-2 text-secondary"></i>
                    Informations Système
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Version Laravel</p>
                        <p class="fw-bold">{{ app()->version() }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Version PHP</p>
                        <p class="fw-bold">{{ phpversion() }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Environnement</p>
                        <p class="fw-bold">
                            <span class="badge bg-{{ app()->environment('production') ? 'danger' : 'success' }}">
                                {{ app()->environment() }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted">Cache Driver</p>
                        <p class="fw-bold">{{ config('cache.default') }}</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('admin.settings.system') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-broom me-2"></i>Vider le cache
                        </button>
                    </form>
                    
                    <form action="{{ route('admin.settings.system') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="optimize">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-rocket me-2"></i>Optimiser
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
