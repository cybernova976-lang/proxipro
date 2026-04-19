@extends('layouts.app')

@section('title', 'Modifier le profil - ProxiPro')

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
                        <div class="text-center mb-4">
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
                                <input type="file" id="avatar" name="avatar" class="d-none" accept="image/*">
                                <input type="hidden" id="avatar_cropped" name="avatar_cropped" value="">
                            </div>
                            <div class="text-muted small mt-2">Astuce: recadrez et zoomez votre photo avant enregistrement.</div>
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
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}" 
                                       placeholder="0639 XX XX XX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Localisation -->
                            <div class="col-md-6">
                                <label for="location" class="form-label">Localisation</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                       id="location" name="location" value="{{ old('location', $user->location) }}" 
                                       placeholder="Mamoudzou, Mayotte">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Bio -->
                            <div class="col-12">
                                <label for="bio" class="form-label">À propos de moi</label>
                                <textarea class="form-control @error('bio') is-invalid @enderror" 
                                          id="bio" name="bio" rows="4" 
                                          placeholder="Décrivez-vous en quelques mots..." 
                                          maxlength="500">{{ old('bio', $user->bio) }}</textarea>
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
                        @endif
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('profile.show') }}" class="btn btn-light">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
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
                <div id="cropViewport" class="mx-auto mb-3" style="width:280px; height:280px; border-radius:14px; overflow:hidden; background:#f1f5f9; border:1px solid #e2e8f0; position:relative; touch-action:none;">
                    <img id="cropImage" alt="Prévisualisation du recadrage" style="position:absolute; left:0; top:0; transform-origin: top left; user-select:none; -webkit-user-drag:none; max-width:none;">
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

@section('scripts')
<script>
const avatarInput = document.getElementById('avatar');
const avatarPreview = document.getElementById('avatarPreview');
const avatarPlaceholder = document.getElementById('avatarPlaceholder');
const avatarCroppedInput = document.getElementById('avatar_cropped');

const cropModalEl = document.getElementById('avatarCropModal');
const cropModal = new bootstrap.Modal(cropModalEl);
const cropViewport = document.getElementById('cropViewport');
const cropImage = document.getElementById('cropImage');
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
};

function clampCropPosition() {
    if (!cropState.image) {
        return;
    }

    const box = cropViewport.getBoundingClientRect();
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

    const box = cropViewport.getBoundingClientRect();
    const baseX = (box.width - cropState.image.naturalWidth * cropState.baseScale) / 2;
    const baseY = (box.height - cropState.image.naturalHeight * cropState.baseScale) / 2;

    cropState.scale = cropState.baseScale;
    cropState.x = baseX;
    cropState.y = baseY;
    cropZoom.value = '1';
    renderCropImage();
}

function openCropper(dataUrl) {
    const img = new Image();
    img.onload = function() {
        cropState.image = img;
        cropImage.src = dataUrl;

        const box = cropViewport.getBoundingClientRect();
        cropState.baseScale = Math.max(box.width / img.naturalWidth, box.height / img.naturalHeight);
        resetCrop();
        cropModal.show();
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
    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        e.target.value = '';
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
    const box = cropViewport.getBoundingClientRect();
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

    const box = cropViewport.getBoundingClientRect();
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
    cropModal.hide();

    cropApply.disabled = false;
    cropApply.textContent = 'Appliquer';
});

cropModalEl.addEventListener('hidden.bs.modal', function() {
    cropState.dragging = false;
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
    var modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
    modal.show();
@endif
</script>
@endsection
