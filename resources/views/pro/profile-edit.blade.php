@extends('pro.layout')
@section('title', 'Modifier mon profil - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.profile') }}" style="color: var(--pro-primary);">Profil</a></li>
                <li class="breadcrumb-item active">Modifier</li>
            </ol>
        </nav>
        <h1>Modifier mon profil</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Personnalisez votre profil professionnel visible par les clients.</p>
    </div>
    <a href="{{ route('pro.profile') }}" class="btn btn-light" style="border-radius: 10px;">
        <i class="fas fa-arrow-left me-1"></i> Retour au profil
    </a>
</div>

<form method="POST" action="{{ route('pro.profile.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Identity --}}
            <div class="pro-card">
                <h5 class="fw-bold mb-3"><i class="fas fa-user me-2 text-primary"></i>Identité</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prénom *</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom *</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom de l'entreprise</label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $user->company_name) }}" style="border-radius: 10px;" placeholder="Ex: Martin Plomberie">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Titre professionnel</label>
                        <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $user->job_title) }}" style="border-radius: 10px;" placeholder="Ex: Plombier chauffagiste">
                    </div>
                </div>
            </div>

            {{-- Bio --}}
            <div class="pro-card">
                <h5 class="fw-bold mb-3"><i class="fas fa-align-left me-2 text-primary"></i>Présentation</h5>
                <textarea name="bio" class="form-control" rows="5" style="border-radius: 10px;" placeholder="Décrivez votre activité, votre expertise, vos points forts...">{{ old('bio', $user->bio) }}</textarea>
                <small class="text-muted">Max 1000 caractères. Cette description apparaît sur votre profil public.</small>
            </div>

            {{-- Contact --}}
            <div class="pro-card">
                <h5 class="fw-bold mb-3"><i class="fas fa-phone me-2 text-primary"></i>Contact & localisation</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" style="border-radius: 10px;" placeholder="06 12 34 56 78">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Site web</label>
                        <input type="url" name="website_url" class="form-control" value="{{ old('website_url', $user->website_url) }}" style="border-radius: 10px;" placeholder="https://www.monsite.fr">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Adresse</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $user->address) }}" style="border-radius: 10px;" placeholder="12 rue de la Paix, 75001 Paris">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Rayon d'intervention (km)</label>
                        <input type="number" name="pro_intervention_radius" class="form-control" value="{{ old('pro_intervention_radius', $user->pro_intervention_radius ?? 30) }}" style="border-radius: 10px;" min="1" max="200">
                    </div>
                </div>
            </div>

            {{-- Professional Info --}}
            <div class="pro-card">
                <h5 class="fw-bold mb-3"><i class="fas fa-briefcase me-2 text-primary"></i>Informations professionnelles</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tarif horaire (€)</label>
                        <input type="number" name="hourly_rate" class="form-control" value="{{ old('hourly_rate', $user->hourly_rate) }}" style="border-radius: 10px;" step="0.01" min="0" placeholder="35.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Années d'expérience</label>
                        <input type="number" name="years_experience" class="form-control" value="{{ old('years_experience', $user->years_experience) }}" style="border-radius: 10px;" min="0" max="60" placeholder="5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">N° d'assurance</label>
                        <input type="text" name="insurance_number" class="form-control" value="{{ old('insurance_number', $user->insurance_number) }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Spécialités</label>
                        <input type="text" name="specialties" class="form-control" value="{{ old('specialties', is_array($user->specialties) ? implode(', ', $user->specialties) : $user->specialties) }}" style="border-radius: 10px;" placeholder="Plomberie, Chauffage, Climatisation">
                        <small class="text-muted">Séparez par des virgules</small>
                    </div>
                </div>
            </div>

            {{-- Social Media --}}
            <div class="pro-card">
                <h5 class="fw-bold mb-3"><i class="fas fa-share-alt me-2 text-primary"></i>Réseaux sociaux</h5>
                @php $links = is_array($user->social_links) ? $user->social_links : json_decode($user->social_links ?? '{}', true) ?? []; @endphp
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="fab fa-facebook text-primary me-1"></i>Facebook</label>
                        <input type="url" name="social_links[facebook]" class="form-control" value="{{ old('social_links.facebook', $links['facebook'] ?? '') }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="fab fa-instagram text-danger me-1"></i>Instagram</label>
                        <input type="url" name="social_links[instagram]" class="form-control" value="{{ old('social_links.instagram', $links['instagram'] ?? '') }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="fab fa-linkedin text-info me-1"></i>LinkedIn</label>
                        <input type="url" name="social_links[linkedin]" class="form-control" value="{{ old('social_links.linkedin', $links['linkedin'] ?? '') }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="fab fa-tiktok me-1"></i>TikTok</label>
                        <input type="url" name="social_links[tiktok]" class="form-control" value="{{ old('social_links.tiktok', $links['tiktok'] ?? '') }}" style="border-radius: 10px;">
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column (Sidebar) --}}
        <div class="col-lg-4">
            {{-- Avatar --}}
            <div class="pro-card text-center">
                <h6 class="fw-bold mb-3">Photo de profil</h6>
                <div id="avatarPreview" style="width: 140px; height: 140px; border-radius: 16px; background: linear-gradient(135deg, #a855f7, #6366f1); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(99,102,241,0.18); border: 3px solid #e2e8f0;">
                    @if($user->avatar)
                        <img id="avatarImg" src="{{ asset('storage/' . $user->avatar) }}" style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                    @else
                        <span id="avatarInitial">{{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}</span>
                        <img id="avatarImg" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;" alt="Avatar">
                    @endif
                </div>
                <input type="file" name="avatar" id="avatarInput" class="form-control" accept="image/*" style="border-radius: 10px; font-size: 0.85rem;">
                <small class="text-muted d-block mt-1">JPG, PNG — Max 2 Mo</small>
            </div>

            {{-- Categories --}}
            <div class="pro-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-tags me-2 text-primary"></i>Catégories de services</h6>
                @php
                    // Catégories depuis config/categories.php (source unique)
                    $categories = [];
                    foreach (config('categories.services') as $name => $data) {
                        $key = \Illuminate\Support\Str::slug($name, '_');
                        $categories[$key] = $data['icon'] . ' ' . $name;
                    }
                    $selectedCats = is_array($user->pro_service_categories) ? $user->pro_service_categories : json_decode($user->pro_service_categories ?? '[]', true) ?? [];
                @endphp
                @foreach($categories as $key => $label)
                <div class="form-check mb-2">
                    <input type="checkbox" name="pro_service_categories[]" value="{{ $key }}" class="form-check-input" id="cat_{{ $key }}"
                        {{ in_array($key, $selectedCats) ? 'checked' : '' }}>
                    <label class="form-check-label" for="cat_{{ $key }}" style="font-size: 0.85rem;">{{ $label }}</label>
                </div>
                @endforeach
            </div>

            {{-- Notifications --}}
            <div class="pro-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-bell me-2 text-primary"></i>Notifications</h6>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="pro_notifications_realtime" value="1" id="notifRealtime"
                        {{ $user->pro_notifications_realtime ? 'checked' : '' }}>
                    <label class="form-check-label" for="notifRealtime" style="font-size: 0.85rem;">Notifications en temps réel</label>
                </div>
                <small class="text-muted">Recevez une alerte dès qu'un client vous contacte ou laisse un avis.</small>
            </div>

            {{-- Save --}}
            <div class="pro-card" style="position: sticky; top: 80px;">
                <button type="submit" class="btn btn-pro-primary w-100 btn-lg mb-2">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
                <a href="{{ route('pro.profile') }}" class="btn btn-light w-100" style="border-radius: 10px;">Annuler</a>
            </div>
        </div>
    </div>
</form>

{{-- Delete Account Section --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="pro-card" style="border: 2px solid #dc3545; border-radius: 12px;">
            <div class="d-flex align-items-center mb-3">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #dc354515; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-danger">Zone dangereuse</h5>
                    <small class="text-muted">Suppression définitive du compte</small>
                </div>
            </div>

            <div class="alert alert-warning" style="border-radius: 10px; border: none; background: #fff3cd;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Attention !</strong> La suppression de votre compte est <strong>irréversible</strong>. Toutes vos données seront définitivement supprimées :
                <ul class="mb-0 mt-2" style="font-size: 0.88rem;">
                    <li>Votre profil, vos annonces et vos photos</li>
                    <li>Vos messages et conversations</li>
                    <li>Vos avis, points et badges</li>
                    <li>Vos clients, devis et factures (espace pro)</li>
                    <li>Vos documents et fichiers uploadés</li>
                </ul>
            </div>

            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal" style="border-radius: 10px;">
                <i class="fas fa-trash-alt me-1"></i> Supprimer mon compte
            </button>
        </div>
    </div>
</div>

{{-- Delete Account Modal --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header bg-danger text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Supprimer mon compte
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="{{ route('settings.delete-account') }}" method="POST" id="deleteAccountForm">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">
                        Cette action est <strong>définitive</strong>. Votre compte et toutes vos données personnelles seront supprimés de façon permanente.
                    </p>

                    <div class="mb-3">
                        <label for="delete_reason" class="form-label fw-semibold">Pourquoi souhaitez-vous partir ?</label>
                        <select class="form-select" id="delete_reason" name="reason" style="border-radius: 10px;">
                            <option value="">Sélectionner une raison (optionnel)...</option>
                            <option value="Je ne l'utilise plus">Je ne l'utilise plus</option>
                            <option value="J'ai créé un autre compte">J'ai créé un autre compte</option>
                            <option value="Problèmes de confidentialité">Problèmes de confidentialité</option>
                            <option value="Service insatisfaisant">Service insatisfaisant</option>
                            <option value="Trop de notifications">Trop de notifications</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    @if(!auth()->user()->isOAuthUser())
                    <div class="mb-3">
                        <label for="delete_password" class="form-label fw-semibold">Confirmez votre mot de passe *</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="delete_password" name="password" required style="border-radius: 10px;" placeholder="Votre mot de passe actuel">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="1" required>
                        <label class="form-check-label" for="confirm_delete" style="font-size: 0.88rem;">
                            Je comprends que cette action est <strong>définitive</strong> et que toutes mes données personnelles seront supprimées de façon permanente.
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #eee;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled style="border-radius: 10px;">
                        <i class="fas fa-trash-alt me-1"></i> Supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Enable delete button only when checkbox is checked
    document.getElementById('confirm_delete').addEventListener('change', function() {
        document.getElementById('confirmDeleteBtn').disabled = !this.checked;
    });

    // Final confirmation before submit
    document.getElementById('deleteAccountForm').addEventListener('submit', function(e) {
        if (!confirm('Êtes-vous absolument sûr(e) ? Cette action est irréversible et supprimera toutes vos données.')) {
            e.preventDefault();
        }
    });
</script>
@endpush

@if($errors->any() && ($errors->has('password') || $errors->has('delete') || $errors->has('confirm_delete')))
@push('scripts')
<script>
    // Réouvrir le modal si erreur de validation
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
        modal.show();
    });
</script>
@endpush
@endif
@endsection

@section('scripts')
<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(ev) {
        var img = document.getElementById('avatarImg');
        img.src = ev.target.result;
        img.style.display = '';
        var initial = document.getElementById('avatarInitial');
        if (initial) initial.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
