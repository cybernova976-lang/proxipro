@extends('layouts.app')

@section('title', 'Paramètres - ProxiPro')

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded">
                        <a href="#password" class="list-group-item list-group-item-action d-flex align-items-center py-3" data-bs-toggle="list">
                            <i class="fas fa-lock me-3 text-primary" style="width: 20px;"></i>
                            Mot de passe
                        </a>
                        <a href="#notifications" class="list-group-item list-group-item-action d-flex align-items-center py-3" data-bs-toggle="list">
                            <i class="fas fa-bell me-3 text-warning" style="width: 20px;"></i>
                            Notifications
                        </a>
                        <a href="#privacy" class="list-group-item list-group-item-action d-flex align-items-center py-3" data-bs-toggle="list">
                            <i class="fas fa-shield-alt me-3 text-success" style="width: 20px;"></i>
                            Confidentialité
                        </a>
                        <a href="#delete" class="list-group-item list-group-item-action d-flex align-items-center py-3 text-danger" data-bs-toggle="list">
                            <i class="fas fa-trash-alt me-3" style="width: 20px;"></i>
                            Supprimer le compte
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <div class="tab-content">
                <!-- Password Section -->
                <div class="tab-pane fade show active" id="password">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="mb-0"><i class="fas fa-lock me-2 text-primary"></i>Changer le mot de passe</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('settings.password') }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8" oninput="checkPasswordStrength(this.value)">
                                    <div id="passwordStrength" class="mt-2" style="display:none;">
                                        <div class="d-flex gap-1 mb-1">
                                            <div class="flex-fill rounded-pill" style="height:4px;background:#e2e8f0;" id="str1"></div>
                                            <div class="flex-fill rounded-pill" style="height:4px;background:#e2e8f0;" id="str2"></div>
                                            <div class="flex-fill rounded-pill" style="height:4px;background:#e2e8f0;" id="str3"></div>
                                            <div class="flex-fill rounded-pill" style="height:4px;background:#e2e8f0;" id="str4"></div>
                                        </div>
                                        <small id="strengthText" class="text-muted">Minimum 8 caractères</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Mettre à jour
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Section -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="mb-0"><i class="fas fa-bell me-2 text-warning"></i>Préférences de notification</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('settings.notifications') }}" method="POST">
                                @csrf
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                           {{ $user->email_notifications ?? true ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_notifications">
                                        <strong>Notifications par email</strong>
                                        <p class="text-muted mb-0 small">Recevoir les notifications importantes par email</p>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" 
                                           {{ $user->sms_notifications ?? false ? 'checked' : '' }}>
                                    <label class="form-check-label" for="sms_notifications">
                                        <strong>Notifications par SMS</strong>
                                        <p class="text-muted mb-0 small">Recevoir les alertes urgentes par SMS</p>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications" 
                                           {{ $user->push_notifications ?? true ? 'checked' : '' }}>
                                    <label class="form-check-label" for="push_notifications">
                                        <strong>Notifications push</strong>
                                        <p class="text-muted mb-0 small">Recevoir des notifications dans votre navigateur</p>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Section -->
                <div class="tab-pane fade" id="privacy">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2 text-success"></i>Confidentialité</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('settings.privacy') }}" method="POST">
                                @csrf
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="profile_public" name="profile_public" 
                                           {{ $user->profile_public ?? true ? 'checked' : '' }}>
                                    <label class="form-check-label" for="profile_public">
                                        <strong>Profil public</strong>
                                        <p class="text-muted mb-0 small">Permettre aux autres utilisateurs de voir votre profil</p>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="show_email" name="show_email" 
                                           {{ $user->show_email ?? false ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_email">
                                        <strong>Afficher mon email</strong>
                                        <p class="text-muted mb-0 small">Afficher votre email sur votre profil public</p>
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="show_phone" name="show_phone" 
                                           {{ $user->show_phone ?? true ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_phone">
                                        <strong>Afficher mon téléphone</strong>
                                        <p class="text-muted mb-0 small">Afficher votre numéro sur vos annonces</p>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Account Section -->
                <div class="tab-pane fade" id="delete">
                    <div class="card border-0 shadow-sm border-danger">
                        <div class="card-header bg-danger text-white py-3">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Zone dangereuse</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Attention !</strong> Cette action est <strong>irréversible</strong>. Toutes vos données personnelles seront supprimées définitivement :
                                <ul class="mb-0 mt-2">
                                    <li>Votre profil, vos annonces et vos photos</li>
                                    <li>Vos messages et conversations</li>
                                    <li>Vos avis, points et badges</li>
                                    <li>Vos clients, devis et factures (espace pro)</li>
                                    <li>Vos documents et fichiers uploadés</li>
                                </ul>
                            </div>
                            
                            <form action="{{ route('settings.delete-account') }}" method="POST" id="settingsDeleteForm"
                                  onsubmit="return confirm('Êtes-vous absolument sûr(e) ? Cette action est irréversible et supprimera toutes vos données.')">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="settings_reason" class="form-label">Pourquoi souhaitez-vous supprimer votre compte ?</label>
                                    <select class="form-select" id="settings_reason" name="reason">
                                        <option value="">Sélectionner une raison (optionnel)...</option>
                                        <option value="Je ne l'utilise plus">Je ne l'utilise plus</option>
                                        <option value="J'ai créé un autre compte">J'ai créé un autre compte</option>
                                        <option value="Problèmes de confidentialité">Problèmes de confidentialité</option>
                                        <option value="Mauvaise expérience">Mauvaise expérience</option>
                                        <option value="Trop de notifications">Trop de notifications</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                
                                @if(!auth()->user()->isOAuthUser())
                                <div class="mb-3">
                                    <label for="settings_delete_password" class="form-label">Confirmez votre mot de passe *</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="settings_delete_password" name="password" required placeholder="Votre mot de passe actuel">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="settings_confirm_delete" name="confirm_delete" value="1" required>
                                    <label class="form-check-label" for="settings_confirm_delete">
                                        Je comprends que cette action est <strong>définitive</strong> et que toutes mes données personnelles seront supprimées de façon permanente.
                                    </label>
                                </div>

                                @error('delete')
                                    <div class="alert alert-danger mb-3">{{ $message }}</div>
                                @enderror
                                
                                <button type="submit" class="btn btn-danger" id="settingsDeleteBtn" disabled>
                                    <i class="fas fa-trash-alt me-2"></i>Supprimer mon compte définitivement
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete account checkbox toggle
        var checkbox = document.getElementById('settings_confirm_delete');
        var btn = document.getElementById('settingsDeleteBtn');
        if (checkbox && btn) {
            checkbox.addEventListener('change', function() {
                btn.disabled = !this.checked;
            });
        }

        // Active sidebar state based on hash or first item
        var hash = window.location.hash;
        var listItems = document.querySelectorAll('.list-group-item[data-bs-toggle="list"]');
        if (hash && document.querySelector('.list-group-item[href="' + hash + '"]')) {
            listItems.forEach(function(item) { item.classList.remove('active'); });
            document.querySelector('.list-group-item[href="' + hash + '"]').click();
        } else if (listItems.length > 0) {
            listItems[0].classList.add('active');
        }

        // Update hash on tab change
        listItems.forEach(function(item) {
            item.addEventListener('click', function() {
                history.replaceState(null, null, this.getAttribute('href'));
            });
        });
    });

    // Password strength checker
    function checkPasswordStrength(val) {
        var container = document.getElementById('passwordStrength');
        var bars = [document.getElementById('str1'), document.getElementById('str2'), document.getElementById('str3'), document.getElementById('str4')];
        var text = document.getElementById('strengthText');

        if (val.length === 0) { container.style.display = 'none'; return; }
        container.style.display = 'block';

        var score = 0;
        if (val.length >= 8) score++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^a-zA-Z0-9]/.test(val)) score++;

        var colors = ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
        var labels = ['Faible', 'Moyen', 'Bon', 'Excellent'];
        var textColors = ['text-danger', 'text-warning', 'text-primary', 'text-success'];

        bars.forEach(function(bar, i) {
            bar.style.background = i < score ? colors[Math.max(0, score - 1)] : '#e2e8f0';
        });
        text.textContent = labels[Math.max(0, score - 1)] || 'Minimum 8 caractères';
        text.className = 'small ' + (textColors[Math.max(0, score - 1)] || 'text-muted');
    }
</script>
@endpush