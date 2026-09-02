@extends('layouts.app')

@section('title', 'Modifier le profil - Prokejem')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Modifier le profil</h5>
                        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- Avatar -->
                        <div class="text-center mb-4" id="profile-photo-section">
                            <div class="position-relative d-inline-block">
                                @if($user->avatar)
                                    <img src="{{ storage_url($user->avatar) }}" alt="Avatar" 
                                         class="rounded-3 shadow-sm" style="width: 140px; height: 140px; object-fit: cover;" id="avatarPreview">
                                @else
                                    <div class="rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm" 
                                         style="width: 140px; height: 140px; font-size: 48px;" id="avatarPlaceholder">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <img src="" alt="Avatar" class="rounded-3 shadow-sm d-none" 
                                         style="width: 140px; height: 140px; object-fit: cover;" id="avatarPreview">
                                @endif
                                <label for="avatar" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                       style="cursor: pointer; width: 44px; height: 44px; font-size: 1rem;">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" id="avatar" name="avatar" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif">
                                <input type="hidden" id="avatar_cropped" name="avatar_cropped" value="">
                            </div>
                            <div class="text-muted small mt-2">
                                La photo de profil est obligatoire avant une demande de vérification. Recadrez-la avant l'enregistrement.
                            </div>
                            <div id="avatarFeedback" class="small mt-2 d-none" role="alert" aria-live="polite"></div>
                            @error('avatar')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row g-3">
                            <!-- Nom -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Téléphone -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}" 
                                       placeholder="Votre numéro de téléphone" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Pays -->
                            <div class="col-md-6">
                                <label for="country" class="form-label">Pays <span class="text-danger">*</span></label>
                                <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
                                    <option value="">Sélectionnez votre pays</option>
                                    @foreach(config('locations.countries', []) as $countryName => $flag)
                                        <option value="{{ $countryName }}" {{ old('country', $user->country) === $countryName ? 'selected' : '' }}>{{ $flag }} {{ $countryName }}</option>
                                    @endforeach
                                </select>
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="city" class="form-label">Ville <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city"
                                       value="{{ old('city', $user->city) }}" placeholder="Votre ville" required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="address" class="form-label">Adresse <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address"
                                       value="{{ old('address', $user->address) }}" placeholder="Numéro et nom de voie" required>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Code postal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code"
                                       value="{{ old('postal_code', $user->postal_code) }}" placeholder="Code postal" required>
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Bio -->
                            <div class="col-12">
                                <label for="bio" class="form-label">À propos de moi <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('bio') is-invalid @enderror" 
                                          id="bio" name="bio" rows="4" 
                                          placeholder="Décrivez-vous en quelques mots..." 
                                          maxlength="500" required>{{ old('bio', $user->bio) }}</textarea>
                                <div class="form-text">
                                    <span id="bioCount">{{ strlen($user->bio ?? '') }}</span>/500 caractères
                                </div>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Section Tarif horaire pour les prestataires --}}
                        @if($user->user_type === 'professionnel' || $user->is_service_provider || $user->hasActiveProSubscription() || $user->hasCompletedProOnboarding())
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-euro-sign me-2 text-primary"></i>Tarif horaire
                        </h6>
                        <p class="text-muted small mb-3">
                            Indiquez votre tarif horaire pour que les clients potentiels puissent estimer le coût de vos services. Vous pouvez choisir de ne pas l'afficher.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="hourly_rate" class="form-label">Tarif horaire (€/h)</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control @error('hourly_rate') is-invalid @enderror" 
                                           id="hourly_rate" 
                                           name="hourly_rate" 
                                           value="{{ old('hourly_rate', $user->hourly_rate) }}" 
                                           placeholder="Ex: 25"
                                           min="0" 
                                           max="999" 
                                           step="0.50">
                                    <span class="input-group-text"><i class="fas fa-euro-sign"></i>/h</span>
                                </div>
                                @error('hourly_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Laissez vide si vous ne souhaitez pas définir de tarif.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="show_hourly_rate" name="show_hourly_rate" value="1"
                                           {{ old('show_hourly_rate', $user->show_hourly_rate ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_hourly_rate">
                                        <i class="fas fa-eye me-1 text-muted"></i>Afficher sur mon profil public
                                    </label>
                                </div>
                                <div class="form-text">Décochez pour masquer votre tarif aux autres utilisateurs.</div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <section id="profile-realizations" class="professional-gallery-editor" aria-labelledby="professionalGalleryTitle">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1" id="professionalGalleryTitle">
                                        <i class="fas fa-images me-2 text-primary"></i>Mes réalisations professionnelles
                                    </h6>
                                    <p class="text-muted small mb-0">Présentez jusqu’à 6 travaux réellement réalisés. Les photos seront visibles sur votre profil public.</p>
                                </div>
                                <span class="professional-gallery-count" id="professionalGalleryCount">{{ $user->professionalRealizations->count() }}/6</span>
                            </div>

                            <div class="professional-gallery-editor__grid" id="professionalGalleryGrid">
                                @foreach($user->professionalRealizations as $realization)
                                    <article class="professional-gallery-editor__item">
                                        <img src="{{ storage_url($realization->photo_path) }}"
                                             alt="Réalisation professionnelle {{ $loop->iteration }}"
                                             loading="lazy">
                                        <button type="submit"
                                                class="professional-gallery-editor__delete"
                                                form="deleteProfessionalRealization{{ $realization->id }}"
                                                aria-label="Supprimer cette réalisation"
                                                title="Supprimer cette réalisation">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </article>
                                @endforeach

                                @if($user->professionalRealizations->count() < 6)
                                    <label for="professionalRealizationPhotos" class="professional-gallery-editor__add" id="professionalGalleryAdd">
                                        <i class="fas fa-plus"></i>
                                        <strong>Ajouter des travaux</strong>
                                        <span id="professionalGalleryAvailable">{{ 6 - $user->professionalRealizations->count() }} emplacement(s) disponible(s)</span>
                                    </label>
                                @endif
                            </div>

                            <input type="file"
                                   class="visually-hidden @error('professional_realization_photos') is-invalid @enderror"
                                   id="professionalRealizationPhotos"
                                   name="professional_realization_photos[]"
                                   accept="image/jpeg,image/png,image/webp"
                                   multiple
                                   data-remaining-slots="{{ 6 - $user->professionalRealizations->count() }}">
                            <div class="form-text mt-2">JPG, PNG ou WebP · 5 Mo maximum par photo.</div>
                            <div class="professional-gallery-selection small mt-2" id="professionalGallerySelection" role="status" aria-live="polite"></div>
                            @error('professional_realization_photos')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @error('professional_realization_photos.*')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </section>
                        @endif
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('profile.show') }}" class="btn btn-light">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>

                    @foreach($user->professionalRealizations as $realization)
                        <form id="deleteProfessionalRealization{{ $realization->id }}"
                              action="{{ route('profile.realizations.destroy', $realization) }}"
                              method="POST"
                              class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                </div>
            </div>

            {{-- Delete Account Section --}}
            <div class="card border-0 shadow-sm mt-4" style="border: 2px solid #dc3545 !important;">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Zone dangereuse</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-3">
                        La suppression de votre compte est <strong>irréversible</strong>. Toutes vos données personnelles seront supprimées définitivement :
                        annonces, messages, avis, points, badges, documents et fichiers.
                    </p>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash-alt me-1"></i> Supprimer mon compte
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Account Modal --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Supprimer mon compte
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="{{ route('settings.delete-account') }}" method="POST" id="profileDeleteAccountForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Cette action est <strong>définitive</strong>. Toutes vos données personnelles seront supprimées de façon permanente.
                    </div>

                    <div class="mb-3">
                        <label for="profile_delete_reason" class="form-label fw-semibold">Pourquoi souhaitez-vous partir ?</label>
                        <select class="form-select" id="profile_delete_reason" name="reason">
                            <option value="">Sélectionner une raison (optionnel)...</option>
                            <option value="Je ne l'utilise plus">Je ne l'utilise plus</option>
                            <option value="J'ai créé un autre compte">J'ai créé un autre compte</option>
                            <option value="Problèmes de confidentialité">Problèmes de confidentialité</option>
                            <option value="Service insatisfaisant">Service insatisfaisant</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    @if(!auth()->user()->isOAuthUser())
                    <div class="mb-3">
                        <label for="profile_delete_password" class="form-label fw-semibold">Confirmez votre mot de passe *</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="profile_delete_password" name="password" required placeholder="Votre mot de passe actuel">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="profile_confirm_delete" name="confirm_delete" value="1" required>
                        <label class="form-check-label" for="profile_confirm_delete">
                            Je comprends que cette action est <strong>définitive</strong> et que toutes mes données seront supprimées.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger" id="profileConfirmDeleteBtn" disabled>
                        <i class="fas fa-trash-alt me-1"></i> Supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Avatar Crop Modal --}}
<div class="modal fade" id="avatarCropModal" tabindex="-1" aria-labelledby="avatarCropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarCropModalLabel">
                    <i class="fas fa-crop-alt me-2"></i>Ajuster la photo de profil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Déplacez la photo et utilisez le zoom pour choisir le cadrage.</p>
                <div id="cropViewport" class="mx-auto mb-3" style="width:min(280px, 100%); aspect-ratio:1; border-radius:14px; overflow:hidden; background:#f1f5f9; border:1px solid #e2e8f0; position:relative; touch-action:none;">
                    <div id="cropLoader" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted" style="z-index:2; background:#f1f5f9;">
                        <span class="spinner-border spinner-border-sm text-primary mb-2" aria-hidden="true"></span>
                        <span class="small">Préparation de la photo…</span>
                    </div>
                    <img id="cropImage" alt="Prévisualisation du recadrage" style="position:absolute; left:0; top:0; opacity:0; transform-origin: top left; user-select:none; -webkit-user-drag:none; max-width:none; will-change:transform;">
                </div>
                <label for="cropZoom" class="form-label mb-1">Zoom</label>
                <input type="range" id="cropZoom" class="form-range" min="1" max="3" step="0.01" value="1">
                <div class="d-flex justify-content-between">
                    <button type="button" id="cropReset" class="btn btn-light btn-sm">Réinitialiser</button>
                    <span class="text-muted small" id="cropHint">Format exporté: carré optimisé</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="cropApply">Appliquer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.professional-gallery-count {
    flex: 0 0 auto;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    font-size: .78rem;
}
.professional-gallery-editor__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .8rem;
}
.professional-gallery-editor__item,
.professional-gallery-editor__add {
    position: relative;
    overflow: hidden;
    min-height: 145px;
    aspect-ratio: 4 / 3;
    border-radius: 16px;
}
.professional-gallery-editor__item {
    background: #e2e8f0;
}
.professional-gallery-editor__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.professional-gallery-editor__delete {
    position: absolute;
    top: .55rem;
    right: .55rem;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 50%;
    background: rgba(15, 23, 42, .8);
    color: #fff;
}
.professional-gallery-editor__add {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    padding: 1rem;
    border: 2px dashed #93c5fd;
    background: #f8fbff;
    color: #1d4ed8;
    text-align: center;
    cursor: pointer;
}
.professional-gallery-editor__add i {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #dbeafe;
}
.professional-gallery-editor__add span {
    color: #64748b;
    font-size: .75rem;
}
.professional-gallery-selection.is-error { color: #dc2626; }
.professional-gallery-selection.is-ready { color: #047857; }
@media (max-width: 575.98px) {
    .professional-gallery-editor__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .professional-gallery-editor__item,
    .professional-gallery-editor__add { min-height: 120px; }
}
</style>
@endpush

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
const avatarInput = document.getElementById('avatar');
const avatarPreview = document.getElementById('avatarPreview');
const avatarPlaceholder = document.getElementById('avatarPlaceholder');
const avatarCroppedInput = document.getElementById('avatar_cropped');
const avatarFeedback = document.getElementById('avatarFeedback');
const professionalGalleryInput = document.getElementById('professionalRealizationPhotos');
const professionalGalleryGrid = document.getElementById('professionalGalleryGrid');
const professionalGallerySelection = document.getElementById('professionalGallerySelection');
const professionalGalleryCount = document.getElementById('professionalGalleryCount');
const professionalGalleryAdd = document.getElementById('professionalGalleryAdd');
const professionalGalleryAvailable = document.getElementById('professionalGalleryAvailable');
const professionalGalleryRemainingSlots = Number(professionalGalleryInput?.dataset.remainingSlots || 0);
let professionalGalleryPreviewUrls = [];
let professionalGalleryFiles = [];

function professionalGalleryFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

function syncProfessionalGalleryInput() {
    if (!professionalGalleryInput) return;

    const transfer = new DataTransfer();
    professionalGalleryFiles.forEach(file => transfer.items.add(file));
    professionalGalleryInput.files = transfer.files;
}

function updateProfessionalGalleryAvailability() {
    const availableSlots = Math.max(0, professionalGalleryRemainingSlots - professionalGalleryFiles.length);
    const existingCount = 6 - professionalGalleryRemainingSlots;

    if (professionalGalleryCount) {
        professionalGalleryCount.textContent = `${existingCount + professionalGalleryFiles.length}/6`;
    }
    if (professionalGalleryAvailable) {
        professionalGalleryAvailable.textContent = `${availableSlots} emplacement(s) disponible(s)`;
    }
    if (professionalGalleryAdd) {
        professionalGalleryAdd.hidden = availableSlots === 0;
    }
}

function setProfessionalGalleryFeedback(message = '', type = '') {
    if (!professionalGallerySelection) return;

    professionalGallerySelection.textContent = message;
    professionalGallerySelection.classList.toggle('is-error', type === 'error');
    professionalGallerySelection.classList.toggle('is-ready', type === 'ready');
}

function renderProfessionalGallerySelection(files) {
    professionalGalleryPreviewUrls.forEach(url => URL.revokeObjectURL(url));
    professionalGalleryPreviewUrls = [];
    professionalGalleryGrid?.querySelectorAll('[data-new-realization]').forEach(item => item.remove());

    files.forEach((file, index) => {
        const item = document.createElement('article');
        item.className = 'professional-gallery-editor__item';
        item.dataset.newRealization = 'true';

        const image = document.createElement('img');
        const previewUrl = URL.createObjectURL(file);
        professionalGalleryPreviewUrls.push(previewUrl);
        image.src = previewUrl;
        image.alt = 'Nouvelle réalisation à enregistrer';

        item.appendChild(image);
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'professional-gallery-editor__delete';
        removeButton.setAttribute('aria-label', `Retirer ${file.name} de la sélection`);
        removeButton.title = 'Retirer cette photo';
        removeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        removeButton.addEventListener('click', () => {
            professionalGalleryFiles.splice(index, 1);
            syncProfessionalGalleryInput();
            renderProfessionalGallerySelection(professionalGalleryFiles);
            updateProfessionalGalleryAvailability();
            setProfessionalGalleryFeedback(
                professionalGalleryFiles.length
                    ? `${professionalGalleryFiles.length} nouvelle(s) réalisation(s) prête(s) à être enregistrée(s).`
                    : '',
                professionalGalleryFiles.length ? 'ready' : ''
            );
        });

        item.appendChild(removeButton);
        professionalGalleryGrid?.insertBefore(item, professionalGalleryAdd);
    });
}

professionalGalleryInput?.addEventListener('change', function() {
    const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const selectedFiles = Array.from(this.files);
    const knownFiles = new Set(professionalGalleryFiles.map(professionalGalleryFileKey));
    let rejectedFiles = 0;
    let duplicateFiles = 0;

    selectedFiles.forEach(file => {
        if (!acceptedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
            rejectedFiles++;
            return;
        }

        const key = professionalGalleryFileKey(file);
        if (knownFiles.has(key)) {
            duplicateFiles++;
            return;
        }

        if (professionalGalleryFiles.length >= professionalGalleryRemainingSlots) {
            rejectedFiles++;
            return;
        }

        professionalGalleryFiles.push(file);
        knownFiles.add(key);
    });

    syncProfessionalGalleryInput();
    renderProfessionalGallerySelection(professionalGalleryFiles);
    updateProfessionalGalleryAvailability();

    if (rejectedFiles > 0) {
        setProfessionalGalleryFeedback(
            `${professionalGalleryFiles.length} photo(s) prête(s). ${rejectedFiles} fichier(s) ignoré(s) : vérifiez le format, la taille de 5 Mo et la limite de 6 photos.`,
            'error'
        );
    } else if (professionalGalleryFiles.length) {
        const duplicateMessage = duplicateFiles > 0 ? ` ${duplicateFiles} doublon(s) ignoré(s).` : '';
        setProfessionalGalleryFeedback(
            `${professionalGalleryFiles.length} nouvelle(s) réalisation(s) prête(s) à être enregistrée(s).${duplicateMessage}`,
            'ready'
        );
    } else {
        setProfessionalGalleryFeedback();
    }
});

const cropModalEl = document.getElementById('avatarCropModal');
const cropModal = window.bootstrap?.Modal ? new window.bootstrap.Modal(cropModalEl) : null;
const cropViewport = document.getElementById('cropViewport');
const cropImage = document.getElementById('cropImage');
const cropLoader = document.getElementById('cropLoader');
const cropZoom = document.getElementById('cropZoom');
const cropReset = document.getElementById('cropReset');
const cropApply = document.getElementById('cropApply');

const cropState = {
    image: null,
    baseScale: 1,
    scale: 1,
    x: 0,
    y: 0,
    dragging: false,
    dragStartX: 0,
    dragStartY: 0,
    dragOriginX: 0,
    dragOriginY: 0,
    applied: false,
};

function setAvatarFeedback(message = '', isError = false) {
    avatarFeedback.textContent = message;
    avatarFeedback.classList.toggle('d-none', message === '');
    avatarFeedback.classList.toggle('text-danger', isError);
    avatarFeedback.classList.toggle('text-success', !isError && message !== '');
}

function cropBoxSize() {
    const box = cropViewport.getBoundingClientRect();

    return {
        width: box.width || cropViewport.clientWidth || 280,
        height: box.height || cropViewport.clientHeight || 280,
    };
}

function clampCropPosition() {
    if (!cropState.image) {
        return;
    }

    const box = cropBoxSize();
    const imgWidth = cropState.image.naturalWidth * cropState.scale;
    const imgHeight = cropState.image.naturalHeight * cropState.scale;

    const minX = Math.min(0, box.width - imgWidth);
    const minY = Math.min(0, box.height - imgHeight);

    cropState.x = Math.min(0, Math.max(minX, cropState.x));
    cropState.y = Math.min(0, Math.max(minY, cropState.y));
}

function renderCropImage() {
    clampCropPosition();
    cropImage.style.transform = `translate(${cropState.x}px, ${cropState.y}px) scale(${cropState.scale})`;
}

function resetCrop() {
    if (!cropState.image) {
        return;
    }

    const box = cropBoxSize();
    const baseX = (box.width - cropState.image.naturalWidth * cropState.baseScale) / 2;
    const baseY = (box.height - cropState.image.naturalHeight * cropState.baseScale) / 2;

    cropState.scale = cropState.baseScale;
    cropState.x = baseX;
    cropState.y = baseY;
    cropZoom.value = '1';
    renderCropImage();
}

function initializeCropViewport() {
    if (!cropState.image) {
        return;
    }

    const box = cropBoxSize();
    cropState.baseScale = Math.max(
        box.width / cropState.image.naturalWidth,
        box.height / cropState.image.naturalHeight
    );
    cropImage.style.width = `${cropState.image.naturalWidth}px`;
    cropImage.style.height = `${cropState.image.naturalHeight}px`;
    resetCrop();
    cropImage.style.opacity = '1';
    cropLoader.classList.add('d-none');
}

function openCropper(dataUrl) {
    if (!cropModal) {
        avatarPreview.src = dataUrl;
        avatarPreview.classList.remove('d-none');
        if (avatarPlaceholder) {
            avatarPlaceholder.classList.add('d-none');
        }
        setAvatarFeedback('Photo sélectionnée. Enregistrez le profil pour confirmer la modification.');
        return;
    }

    const img = new Image();
    img.onload = function() {
        cropState.image = img;
        cropState.applied = false;
        cropLoader.classList.remove('d-none');
        cropImage.style.opacity = '0';
        cropImage.src = dataUrl;
        cropModal.show();
    };
    img.onerror = function() {
        avatarInput.value = '';
        setAvatarFeedback('Cette image ne peut pas être lue. Choisissez un fichier JPG, PNG ou WebP.', true);
    };
    img.src = dataUrl;
}

function pointerPosition(event) {
    const src = event.touches ? event.touches[0] : event;
    return { x: src.clientX, y: src.clientY };
}

function onDragStart(event) {
    if (!cropState.image) {
        return;
    }

    const pos = pointerPosition(event);
    cropState.dragging = true;
    cropState.dragStartX = pos.x;
    cropState.dragStartY = pos.y;
    cropState.dragOriginX = cropState.x;
    cropState.dragOriginY = cropState.y;
}

function onDragMove(event) {
    if (!cropState.dragging) {
        return;
    }

    event.preventDefault();
    const pos = pointerPosition(event);
    cropState.x = cropState.dragOriginX + (pos.x - cropState.dragStartX);
    cropState.y = cropState.dragOriginY + (pos.y - cropState.dragStartY);
    renderCropImage();
}

function onDragEnd() {
    cropState.dragging = false;
}

async function canvasToDataUrlWithTarget(canvas, maxBytes) {
    const qualities = [0.92, 0.85, 0.78, 0.72, 0.66, 0.6, 0.54];

    for (const quality of qualities) {
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
        if (blob && blob.size <= maxBytes) {
            return await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.readAsDataURL(blob);
            });
        }
    }

    return canvas.toDataURL('image/jpeg', 0.5);
}

avatarInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    setAvatarFeedback();
    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        e.target.value = '';
        setAvatarFeedback('Le fichier sélectionné n’est pas une image valide.', true);
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        e.target.value = '';
        setAvatarFeedback('La photo ne doit pas dépasser 5 Mo.', true);
        return;
    }

    const reader = new FileReader();
    reader.onload = function(loadEvent) {
        openCropper(loadEvent.target.result);
    };
    reader.readAsDataURL(file);
});

cropZoom.addEventListener('input', function() {
    if (!cropState.image) {
        return;
    }

    const factor = parseFloat(this.value);
    const previousScale = cropState.scale;
    const box = cropBoxSize();
    const centerX = box.width / 2;
    const centerY = box.height / 2;

    cropState.scale = cropState.baseScale * factor;

    const ratio = cropState.scale / previousScale;
    cropState.x = centerX - (centerX - cropState.x) * ratio;
    cropState.y = centerY - (centerY - cropState.y) * ratio;
    renderCropImage();
});

cropReset.addEventListener('click', resetCrop);
cropViewport.addEventListener('mousedown', onDragStart);
cropViewport.addEventListener('touchstart', onDragStart, { passive: true });
window.addEventListener('mousemove', onDragMove);
window.addEventListener('touchmove', onDragMove, { passive: false });
window.addEventListener('mouseup', onDragEnd);
window.addEventListener('touchend', onDragEnd);

cropApply.addEventListener('click', async function() {
    if (!cropState.image) {
        return;
    }

    cropApply.disabled = true;
    cropApply.textContent = 'Traitement...';

    const box = cropBoxSize();
    const exportSize = 512;
    const canvas = document.createElement('canvas');
    canvas.width = exportSize;
    canvas.height = exportSize;
    const ctx = canvas.getContext('2d');

    const sx = (-cropState.x) / cropState.scale;
    const sy = (-cropState.y) / cropState.scale;
    const sw = box.width / cropState.scale;
    const sh = box.height / cropState.scale;

    ctx.drawImage(cropState.image, sx, sy, sw, sh, 0, 0, exportSize, exportSize);

    const optimizedDataUrl = await canvasToDataUrlWithTarget(canvas, 1800 * 1024);

    avatarPreview.src = optimizedDataUrl;
    avatarPreview.classList.remove('d-none');
    if (avatarPlaceholder) {
        avatarPlaceholder.classList.add('d-none');
    }

    avatarCroppedInput.value = optimizedDataUrl;
    avatarInput.value = '';
    cropState.applied = true;
    cropModal.hide();
    setAvatarFeedback('Photo cadrée. Enregistrez le profil pour confirmer la modification.');

    cropApply.disabled = false;
    cropApply.textContent = 'Appliquer';
});

cropModalEl.addEventListener('shown.bs.modal', function() {
    window.requestAnimationFrame(initializeCropViewport);
});

cropModalEl.addEventListener('hidden.bs.modal', function() {
    cropState.dragging = false;
    cropLoader.classList.add('d-none');
    if (!cropState.applied) {
        avatarInput.value = '';
    }
});

document.getElementById('bio').addEventListener('input', function() {
    document.getElementById('bioCount').textContent = this.value.length;
});

// Delete account - enable button on checkbox
document.getElementById('profile_confirm_delete').addEventListener('change', function() {
    document.getElementById('profileConfirmDeleteBtn').disabled = !this.checked;
});

// Reopen modal on validation error
@if($errors->any() && ($errors->has('password') || $errors->has('delete') || $errors->has('confirm_delete')))
    if (window.bootstrap?.Modal) {
        var modal = new window.bootstrap.Modal(document.getElementById('deleteAccountModal'));
        modal.show();
    }
@endif
});
</script>
@endsection
