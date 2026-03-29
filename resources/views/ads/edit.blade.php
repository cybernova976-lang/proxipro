@extends('layouts.app')

@section('title', 'Modifier une annonce - ProxiPro')

@push('styles')
<style>
    /* ===== MODERN PUBLICATION FORM ===== */
    body { background: #f0f2f5; }
    
    .publish-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 30px 20px 60px;
    }
    
    /* Page Header */
    .page-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        color: #111b21;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 8px;
    }
    
    .page-header p {
        color: #667781;
        font-size: 1rem;
    }
    
    /* Points Banner */
    .points-banner {
        background: linear-gradient(135deg, #00a884, #25d366);
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        color: white;
    }
    
    .points-banner-icon {
        width: 56px;
        height: 56px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .points-banner h5 {
        margin: 0 0 4px;
        font-weight: 600;
    }
    
    .points-banner p {
        margin: 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    /* Form Card */
    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .form-section {
        padding: 28px 32px;
        border-bottom: 1px solid #e9edef;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .section-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
    }
    
    .section-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #00a884, #25d366);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    
    .section-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #111b21;
    }
    
    /* Service Type Selector */
    .type-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .type-option {
        position: relative;
        background: #f7f8fa;
        border: 2px solid #e9edef;
        border-radius: 14px;
        padding: 24px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    
    .type-option:hover {
        border-color: #00a884;
        background: #f0faf7;
    }
    
    .type-option.selected {
        border-color: #00a884;
        background: linear-gradient(135deg, rgba(0, 168, 132, 0.08), rgba(37, 211, 102, 0.08));
    }
    
    .type-option input {
        position: absolute;
        opacity: 0;
    }
    
    .type-option-icon {
        font-size: 2.5rem;
        margin-bottom: 12px;
    }
    
    .type-option h6 {
        margin: 0 0 4px;
        font-weight: 600;
        color: #111b21;
    }
    
    .type-option p {
        margin: 0;
        font-size: 0.85rem;
        color: #667781;
    }
    
    /* Category Grid */
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }
    
    .category-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 20px 12px;
        background: #f7f8fa;
        border: 2px solid #e9edef;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .category-btn:hover {
        border-color: #00a884;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,168,132,0.15);
    }
    
    .category-btn .cat-icon {
        font-size: 2rem;
    }
    
    .category-btn .cat-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: #111b21;
        text-align: center;
    }
    
    /* Subcategory Grid */
    .subcategory-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
    }
    
    .subcategory-btn {
        padding: 14px 18px;
        background: #f7f8fa;
        border: 2px solid #e9edef;
        border-radius: 10px;
        text-align: left;
        font-weight: 500;
        color: #111b21;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .subcategory-btn:hover {
        border-color: #00a884;
        background: #f0faf7;
    }
    
    /* Selected Category Display */
    .selected-category {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: linear-gradient(135deg, rgba(0, 168, 132, 0.08), rgba(37, 211, 102, 0.08));
        border: 2px solid #00a884;
        border-radius: 14px;
    }
    
    .selected-category-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .selected-category-icon {
        font-size: 2rem;
    }
    
    .selected-category-text small {
        color: #667781;
        font-size: 0.8rem;
    }
    
    .selected-category-text strong {
        display: block;
        color: #111b21;
        font-size: 1rem;
    }
    
    .btn-change-category {
        background: white;
        border: 1px solid #e9edef;
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #667781;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-change-category:hover {
        background: #f7f8fa;
        color: #111b21;
    }
    
    /* Breadcrumb */
    .category-breadcrumb {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: #f7f8fa;
        border-radius: 10px;
        margin-bottom: 16px;
    }
    
    .breadcrumb-back {
        background: #00a884;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
    }
    
    .breadcrumb-back:hover {
        background: #008f72;
    }
    
    .breadcrumb-current {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #00a884;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* Form Controls */
    .form-label {
        font-weight: 500;
        color: #111b21;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        background: #f7f8fa;
        border: 2px solid #e9edef;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 0.95rem;
        color: #111b21;
        transition: all 0.2s;
    }
    
    .form-control:focus, .form-select:focus {
        background: white;
        border-color: #00a884;
        box-shadow: 0 0 0 3px rgba(0, 168, 132, 0.15);
    }
    
    .form-control::placeholder {
        color: #8696a0;
    }
    
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
    
    .form-hint {
        font-size: 0.8rem;
        color: #8696a0;
        margin-top: 6px;
    }
    
    /* ===== PHOTO UPLOAD SECTION ===== */
    .photo-upload-area {
        border: 3px dashed #d1d5db;
        border-radius: 16px;
        padding: 40px 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafbfc;
    }
    
    .photo-upload-area:hover {
        border-color: #00a884;
        background: #f0faf7;
    }
    
    .photo-upload-area.dragover {
        border-color: #00a884;
        background: linear-gradient(135deg, rgba(0, 168, 132, 0.1), rgba(37, 211, 102, 0.1));
        transform: scale(1.01);
    }
    
    .upload-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #00a884, #25d366);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .upload-icon i {
        font-size: 2rem;
        color: white;
    }
    
    .upload-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #111b21;
        margin-bottom: 8px;
    }
    
    .upload-subtitle {
        color: #667781;
        font-size: 0.9rem;
        margin-bottom: 16px;
    }
    
    .btn-browse {
        background: linear-gradient(135deg, #00a884, #25d366);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-browse:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 168, 132, 0.35);
    }
    
    .upload-formats {
        margin-top: 16px;
        font-size: 0.8rem;
        color: #8696a0;
    }
    
    /* Photo Preview Grid */
    .photo-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        margin-top: 20px;
    }
    
    .photo-preview-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .photo-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .photo-preview-item .remove-photo {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 28px;
        height: 28px;
        background: rgba(0,0,0,0.6);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 0.8rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .photo-preview-item .remove-photo:hover {
        background: #dc3545;
    }
    
    .photo-preview-item.main-photo::after {
        content: 'Photo principale';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #00a884, #25d366);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
        padding: 4px;
    }
    
    /* Location Input */
    .location-input-group {
        display: flex;
        gap: 0;
    }
    
    .location-input-group .form-control {
        border-radius: 10px 0 0 10px;
    }
    
    .btn-detect-location {
        background: linear-gradient(135deg, #00a884, #25d366);
        border: none;
        color: white;
        padding: 0 20px;
        border-radius: 0 10px 10px 0;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-detect-location:hover {
        opacity: 0.9;
    }
    
    /* Price Input */
    .price-input-group {
        display: flex;
        gap: 0;
    }
    
    .price-input-group .form-control {
        border-radius: 10px 0 0 10px;
    }
    
    .price-input-group .input-suffix {
        background: #e9edef;
        border: 2px solid #e9edef;
        border-left: none;
        border-radius: 0 10px 10px 0;
        padding: 0 16px;
        display: flex;
        align-items: center;
        font-weight: 600;
        color: #667781;
    }
    
    /* Disclaimer */
    .disclaimer-box {
        background: #fffbeb;
        border: 2px solid #f59e0b;
        border-radius: 14px;
        overflow: hidden;
    }
    
    .disclaimer-header {
        background: rgba(245, 158, 11, 0.15);
        padding: 14px 20px;
        font-weight: 600;
        color: #92400e;
    }
    
    .disclaimer-content {
        padding: 20px;
        font-size: 0.9rem;
        color: #4a5568;
        line-height: 1.6;
        max-height: 180px;
        overflow-y: auto;
    }
    
    .disclaimer-checkbox {
        background: rgba(255,255,255,0.7);
        padding: 18px 20px;
        border-top: 1px solid rgba(245, 158, 11, 0.3);
    }
    
    .disclaimer-checkbox label {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
    }
    
    .disclaimer-checkbox input[type="checkbox"] {
        width: 22px;
        height: 22px;
        margin-top: 2px;
        accent-color: #00a884;
        cursor: pointer;
    }
    
    /* Form Actions */
    .form-actions {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 24px 32px;
        background: #f7f8fa;
    }
    
    .btn-cancel {
        background: white;
        border: 2px solid #e9edef;
        color: #667781;
        padding: 14px 28px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-cancel:hover {
        background: #f7f8fa;
        color: #111b21;
    }
    
    .btn-publish {
        background: linear-gradient(135deg, #00a884, #25d366);
        border: none;
        color: white;
        padding: 14px 36px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-publish:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 168, 132, 0.35);
    }
    
    .btn-publish:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Error Alert */
    .error-alert {
        background: #fee2e2;
        border: 2px solid #dc3545;
        border-radius: 14px;
        padding: 18px 22px;
        margin-bottom: 20px;
        color: #991b1b;
    }
    
    .error-alert ul {
        margin: 10px 0 0;
        padding-left: 20px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .publish-container {
            padding: 20px 16px 50px;
        }
        
        .form-section {
            padding: 24px 20px;
        }
        
        .type-selector {
            grid-template-columns: 1fr;
        }
        
        .category-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .form-actions {
            flex-direction: column-reverse;
        }
        
        .btn-cancel, .btn-publish {
            width: 100%;
            text-align: center;
        }
    }
</style>
@endpush

@section('content')
<div class="publish-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-edit me-2"></i>Modifier une annonce</h1>
        <p>Mettez a jour votre annonce pour la garder attractive</p>
    </div>
    
    <!-- Points Banner -->
    <div class="points-banner">
        <div class="points-banner-icon">🎁</div>
        <div>
            <h5>Mise a jour</h5>
            <p>Modifiez votre annonce pour une meilleure visibilite</p>
        </div>
    </div>
    
    @if ($errors->any())
    <div class="error-alert">
        <strong><i class="fas fa-exclamation-triangle me-2"></i>Veuillez corriger les erreurs suivantes :</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    
    <form method="POST" action="{{ route('ads.update', $ad) }}" enctype="multipart/form-data" class="form-card">
        @csrf
        @method('PUT')
<!-- Type de service -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-hand-holding-heart"></i></div>
                <h4 class="section-title">Quel type de service ?</h4>
            </div>
            
            <div class="type-selector">
                <label class="type-option {{ old('service_type', $ad->service_type) == 'offre' ? 'selected' : '' }}">
                    <input type="radio" name="service_type" value="offre" {{ old('service_type', $ad->service_type) == 'offre' ? 'checked' : '' }} required>
                    <div class="type-option-icon">🤝</div>
                    <h6>Je propose un service</h6>
                    <p>Partagez vos compétences</p>
                </label>
                <label class="type-option {{ old('service_type', $ad->service_type) == 'demande' ? 'selected' : '' }}">
                    <input type="radio" name="service_type" value="demande" {{ old('service_type', $ad->service_type) == 'demande' ? 'checked' : '' }}>
                    <div class="type-option-icon">🔍</div>
                    <h6>Je recherche un service</h6>
                    <p>Trouvez de l'aide</p>
                </label>
            </div>
        </div>
        
        <!-- Catégorie -->
        <div class="form-section" id="category-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-th-large"></i></div>
                <h4 class="section-title">Que souhaitez-vous proposer ?</h4>
            </div>
            
            <!-- Breadcrumb -->
            <div class="category-breadcrumb" id="category-breadcrumb" style="display: none;">
                <button type="button" class="breadcrumb-back" onclick="resetCategories()">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </button>
                <span style="color: #8696a0;">→</span>
                <span class="breadcrumb-current" id="selected-main-cat-badge"></span>
            </div>
            
            <!-- Main Categories Grid -->
            <div id="main-categories-grid" class="category-grid">
                @php
                // Catégories depuis config/categories.php (source unique)
                $categoriesData = [];
                foreach (config('categories.services') as $name => $data) {
                    $categoriesData[$name] = ['icon' => $data['icon'], 'subcategories' => $data['subcategories']];
                }
                foreach (config('categories.marketplace') as $name => $data) {
                    $categoriesData[$name] = ['icon' => $data['icon'], 'subcategories' => $data['subcategories']];
                }
                @endphp
                
                @foreach($categoriesData as $catName => $catData)
                <button type="button" class="category-btn" onclick="selectMainCategory('{{ $catName }}', '{{ $catData['icon'] }}')">
                    <span class="cat-icon">{{ $catData['icon'] }}</span>
                    <span class="cat-name">{{ $catName }}</span>
                </button>
                @endforeach
            </div>
            
            <!-- Subcategories Grid -->
            <div id="subcategories-grid" class="subcategory-grid" style="display: none;"></div>
            
            <!-- Selected Category Display -->
            <div id="selected-category-display" class="selected-category" style="display: none;">
                <div class="selected-category-info">
                    <span class="selected-category-icon" id="display-icon"></span>
                    <div class="selected-category-text">
                        <small id="display-main-cat"></small>
                        <strong id="display-sub-cat"></strong>
                    </div>
                </div>
                <button type="button" class="btn-change-category" onclick="resetCategories()">
                    <i class="fas fa-pen me-1"></i> Modifier
                </button>
            </div>
            
            @php
                $selectedCategory = old('category', $ad->category);
                $selectedMain = old('main_category');
                if (!$selectedMain && $selectedCategory) {
                    foreach ($categoriesData as $catName => $catData) {
                        if (in_array($selectedCategory, $catData['subcategories'], true)) {
                            $selectedMain = $catName;
                            break;
                        }
                    }
                }
            @endphp
            <input type="hidden" name="main_category" id="main_category" value="{{ $selectedMain }}">
            <input type="hidden" name="category" id="category" value="{{ $selectedCategory }}">
        </div>
        
        <!-- Photos -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-camera"></i></div>
                <h4 class="section-title">Photos de votre annonce</h4>
            </div>
            
            <div class="photo-upload-area" id="photo-upload-area" onclick="document.getElementById('photo-input').click()">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-title">Glissez-déposez vos photos ici</div>
                <div class="upload-subtitle">ou cliquez pour parcourir vos fichiers</div>
                <button type="button" class="btn-browse">
                    <i class="fas fa-folder-open me-2"></i>Choisir des photos
                </button>
                <div class="upload-formats">
                    <i class="fas fa-info-circle me-1"></i>
                    Formats acceptés : JPG, PNG, WEBP • Max {{ Auth::check() && Auth::user()->hasActiveProSubscription() ? '4' : '2' }} photos • 5 MB par photo
                </div>
            </div>
            
            <input type="file" id="photo-input" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
            
            <div class="photo-preview-grid" id="photo-preview-grid"></div>

            @if(!empty($ad->photos))
                <div class="photo-preview-grid mt-3" id="existing-photos-grid">
                    @foreach($ad->photos as $index => $photo)
                        <div class="photo-preview-item" id="existing-photo-{{ $index }}">
                            <img src="{{ asset('storage/'.$photo) }}" alt="Photo">
                            <button type="button" class="remove-photo" title="Supprimer cette photo"
                                    onclick="deleteExistingPhoto({{ $ad->id }}, {{ $index }}, this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                <p class="form-hint mt-2">Vous pouvez supprimer une photo existante ou en importer de nouvelles (remplacera les anciennes).</p>
            @endif
        </div>
        
        <!-- Titre et Description -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-edit"></i></div>
                <h4 class="section-title">Détails de l'annonce</h4>
            </div>
            
            <div class="mb-4">
                <label for="title" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title', $ad->title) }}" 
                       placeholder="Ex: Plombier disponible pour dépannage urgent" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div>
                <label for="description" class="form-label">Description détaillée <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="5" 
                          placeholder="Décrivez votre service en détail : expérience, disponibilités, conditions..." required>{{ old('description', $ad->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="form-hint"><i class="fas fa-lightbulb me-1"></i>Conseil : Une description détaillée attire plus de clients !</p>
            </div>
        </div>
        
        <!-- Localisation -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h4 class="section-title">Localisation</h4>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <label for="country" class="form-label">Pays <span class="text-danger">*</span></label>
                    <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
                        <option value="">-- Sélectionner un pays --</option>
                        <option value="France" {{ old('country', $ad->country) == 'France' ? 'selected' : '' }}>🇫🇷 France</option>
                        <option value="Mayotte" {{ old('country', $ad->country) == 'Mayotte' ? 'selected' : '' }}>🇾🇹 Mayotte</option>
                        <option value="Madagascar" {{ old('country', $ad->country) == 'Madagascar' ? 'selected' : '' }}>🇲🇬 Madagascar</option>
                        <option value="La Réunion" {{ old('country', $ad->country) == 'La Réunion' ? 'selected' : '' }}>🇷🇪 La Réunion</option>
                        <option value="Maurice" {{ old('country', $ad->country) == 'Maurice' ? 'selected' : '' }}>🇲🇺 Maurice</option>
                        <option value="Belgique" {{ old('country', $ad->country) == 'Belgique' ? 'selected' : '' }}>🇧🇪 Belgique</option>
                        <option value="Suisse" {{ old('country', $ad->country) == 'Suisse' ? 'selected' : '' }}>🇨🇭 Suisse</option>
                        <option value="Canada" {{ old('country', $ad->country) == 'Canada' ? 'selected' : '' }}>🇨🇦 Canada</option>
                        <option value="Sénégal" {{ old('country', $ad->country) == 'Sénégal' ? 'selected' : '' }}>🇸🇳 Sénégal</option>
                        <option value="Côte d'Ivoire" {{ old('country', $ad->country) == "Côte d'Ivoire" ? 'selected' : '' }}>🇨🇮 Côte d'Ivoire</option>
                        <option value="Maroc" {{ old('country', $ad->country) == 'Maroc' ? 'selected' : '' }}>🇲🇦 Maroc</option>
                        <option value="Tunisie" {{ old('country', $ad->country) == 'Tunisie' ? 'selected' : '' }}>🇹🇳 Tunisie</option>
                        <option value="Algérie" {{ old('country', $ad->country) == 'Algérie' ? 'selected' : '' }}>🇩🇿 Algérie</option>
                    </select>
                    @error('country')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="city" class="form-label">Ville <span class="text-danger">*</span></label>
                    <select class="form-select" id="city" name="city">
                        <option value="">-- Sélectionnez d'abord un pays --</option>
                    </select>
                    <input type="text" class="form-control mt-2 @error('location') is-invalid @enderror" 
                           id="location" name="location" value="{{ old('location', $ad->location) }}" 
                           placeholder="Ou saisissez votre ville manuellement">
                    @error('location')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div id="location-result" class="form-hint"></div>
                </div>
                
                <div class="col-md-4">
                    <label for="radius_km" class="form-label">Rayon d'intervention</label>
                    <select class="form-select" id="radius_km" name="radius_km">
                        <option value="5" {{ old('radius_km', $ad->radius_km) == 5 ? 'selected' : '' }}>5 km</option>
                        <option value="10" {{ old('radius_km', $ad->radius_km) == 10 ? 'selected' : '' }}>10 km</option>
                        <option value="25" {{ old('radius_km', $ad->radius_km) == 25 ? 'selected' : '' }}>25 km</option>
                        <option value="50" {{ old('radius_km', $ad->radius_km) == 50 ? 'selected' : '' }}>50 km</option>
                        <option value="100" {{ old('radius_km', $ad->radius_km) == 100 ? 'selected' : '' }}>100 km</option>
                    </select>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="detect-location">
                        <i class="fas fa-location-arrow me-1"></i>Détecter ma position automatiquement
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Prix -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-euro-sign"></i></div>
                <h4 class="section-title">Tarification (optionnel)</h4>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <label for="price" class="form-label">Prix</label>
                    <div class="price-input-group">
                        <input type="number" step="0.01" min="0" 
                               class="form-control @error('price') is-invalid @enderror" 
                               id="price" name="price" value="{{ old('price', $ad->price) }}" 
                               placeholder="Laisser vide si gratuit">
                        <span class="input-suffix">€</span>
                    </div>
                    @error('price')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <p class="form-hint">Laissez vide si le prix est à discuter ou gratuit</p>
                </div>
            </div>
        </div>
        
        <!-- Conditions -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-file-contract"></i></div>
                <h4 class="section-title">Conditions de publication</h4>
            </div>
            
            <div class="disclaimer-box">
                <div class="disclaimer-header">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Important : Lisez attentivement avant de publier
                </div>
                <div class="disclaimer-content">
                    <p>L'utilisateur est entièrement responsable du contenu de toutes les annonces publiées sur cette plateforme, y compris, mais sans s'y limiter, les descriptions, les images, les prix et toute autre information fournie.</p>
                    <p>En cas de plainte, de litige ou de réclamation lié au contenu d'une annonce, l'utilisateur s'engage à assumer l'entière responsabilité.</p>
                    <p>La plateforme se réserve le droit de supprimer toute annonce qui enfreint les présentes conditions.</p>
                </div>
                <div class="disclaimer-checkbox">
                    <label>
                        <input type="checkbox" name="accept_conditions" id="accept_conditions" required checked 
                               class="@error('accept_conditions') is-invalid @enderror">
                        <span>
                            <strong>J'accepte les conditions de publication</strong> <span class="text-danger">*</span><br>
                            <small style="color: #667781;">En cochant cette case, vous confirmez avoir lu et accepté les conditions ci-dessus.</small>
                        </span>
                    </label>
                    @error('accept_conditions')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <a href="{{ route('ads.index') }}" class="btn-cancel">
                <i class="fas fa-times me-2"></i>Annuler
            </a>
            <button type="submit" class="btn-publish" id="btn-publish">
                <i class="fas fa-save me-2"></i>Mettre a jour l'annonce
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    // ===== CATEGORY SELECTION =====
    // Généré dynamiquement depuis config/categories.php (source unique de vérité)
    const categoriesData = @json($categoriesData);

    function selectMainCategory(catName, icon) {
        document.getElementById('main-categories-grid').style.display = 'none';
        document.getElementById('category-breadcrumb').style.display = 'flex';
        document.getElementById('selected-main-cat-badge').innerHTML = icon + ' ' + catName;
        
        const subcatGrid = document.getElementById('subcategories-grid');
        subcatGrid.innerHTML = '';
        
        const subcategories = categoriesData[catName]?.subcategories || [];
        subcategories.forEach(subcat => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'subcategory-btn';
            btn.textContent = subcat;
            btn.onclick = () => selectSubCategory(subcat, catName, icon);
            subcatGrid.appendChild(btn);
        });
        
        subcatGrid.style.display = 'grid';
        document.getElementById('main_category').value = catName;
    }

    function selectSubCategory(subcat, mainCat, icon) {
        document.getElementById('subcategories-grid').style.display = 'none';
        document.getElementById('category-breadcrumb').style.display = 'none';
        
        const display = document.getElementById('selected-category-display');
        document.getElementById('display-icon').textContent = icon;
        document.getElementById('display-main-cat').textContent = mainCat;
        document.getElementById('display-sub-cat').textContent = subcat;
        display.style.display = 'flex';
        
        document.getElementById('main_category').value = mainCat;
        document.getElementById('category').value = subcat;
    }

    function resetCategories() {
        document.getElementById('main-categories-grid').style.display = 'grid';
        document.getElementById('subcategories-grid').style.display = 'none';
        document.getElementById('category-breadcrumb').style.display = 'none';
        document.getElementById('selected-category-display').style.display = 'none';
        document.getElementById('main_category').value = '';
        document.getElementById('category').value = '';
    }

    // ===== TYPE SELECTION =====
    document.querySelectorAll('.type-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.type-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // ===== PHOTO UPLOAD =====
    const uploadArea = document.getElementById('photo-upload-area');
    const photoInput = document.getElementById('photo-input');
    const previewGrid = document.getElementById('photo-preview-grid');
    let uploadedFiles = [];

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    photoInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        const maxFiles = {{ Auth::check() && Auth::user()->hasActiveProSubscription() ? 4 : 2 }};
        const maxSize = 5 * 1024 * 1024; // 5MB
        const existingCount = document.querySelectorAll('#existing-photos-grid .photo-preview-item').length;
        
        Array.from(files).forEach(file => {
            if (uploadedFiles.length + existingCount >= maxFiles) {
                alert('Maximum ' + maxFiles + ' photos autorisées (vous en avez déjà ' + existingCount + ')');
                return;
            }
            
            if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
                alert('Format non supporté: ' + file.name);
                return;
            }
            
            if (file.size > maxSize) {
                alert('Fichier trop volumineux: ' + file.name);
                return;
            }
            
            uploadedFiles.push(file);
            displayPreview(file, uploadedFiles.length - 1);
        });
        
        updateFileInput();
    }

    function displayPreview(file, index) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'photo-preview-item' + (index === 0 ? ' main-photo' : '');
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-photo" onclick="removePhoto(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            previewGrid.appendChild(div);
        };
        reader.readAsDataURL(file);
    }

    function removePhoto(index) {
        uploadedFiles.splice(index, 1);
        previewGrid.innerHTML = '';
        uploadedFiles.forEach((file, i) => displayPreview(file, i));
        updateFileInput();
    }

    function updateFileInput() {
        const dt = new DataTransfer();
        uploadedFiles.forEach(file => dt.items.add(file));
        photoInput.files = dt.files;
    }

    // ===== DELETE EXISTING PHOTO =====
    function deleteExistingPhoto(adId, index, btn) {
        if (!confirm('Supprimer cette photo ?')) return;
        fetch('/ads/' + adId + '/photos/' + index, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(response => {
            if (response.ok) {
                btn.closest('.photo-preview-item').remove();
            } else {
                alert('Erreur lors de la suppression de la photo.');
            }
        }).catch(() => alert('Erreur réseau.'));
    }

    // ===== CONDITIONS CHECKBOX =====
    document.getElementById('accept_conditions').addEventListener('change', function() {
        document.getElementById('btn-publish').disabled = !this.checked;
    });

    // ===== RESTORE OLD VALUES =====
    document.addEventListener('DOMContentLoaded', function() {
        const oldMainCat = document.getElementById('main_category').value;
        const oldCat = document.getElementById('category').value;
        
        if (oldMainCat && oldCat && categoriesData[oldMainCat]) {
            const icon = categoriesData[oldMainCat].icon;
            selectSubCategory(oldCat, oldMainCat, icon);
        }
        
        // Initialize city select based on current country
        const country = document.getElementById('country').value;
        if (country) {
            document.getElementById('country').dispatchEvent(new Event('change'));
        }
    });

    // ===== CITIES DATA BY COUNTRY =====
    const citiesByCountry = {
        "France": ["Paris", "Marseille", "Lyon", "Toulouse", "Nice", "Nantes", "Strasbourg", "Montpellier", "Bordeaux", "Lille", "Rennes", "Reims", "Le Havre", "Saint-Étienne", "Toulon", "Grenoble", "Dijon", "Angers", "Nîmes", "Villeurbanne", "Clermont-Ferrand", "Le Mans", "Aix-en-Provence", "Brest", "Tours", "Amiens", "Limoges", "Perpignan", "Metz", "Besançon", "Orléans", "Rouen", "Mulhouse", "Caen", "Nancy", "Argenteuil", "Saint-Denis", "Montreuil", "Roubaix", "Avignon"],
        "Mayotte": ["Mamoudzou", "Koungou", "Dzaoudzi", "Dembeni", "Bandraboua", "Tsingoni", "Sada", "Ouangani", "Chiconi", "Pamandzi", "Mtsamboro", "Acoua", "Chirongui", "Bouéni", "Kani-Kéli", "Bandrélé", "M'Tsangamouji"],
        "Madagascar": ["Antananarivo", "Toamasina", "Antsirabe", "Fianarantsoa", "Mahajanga", "Toliara", "Antsiranana", "Ambatondrazaka", "Antalaha", "Nosy Be", "Sainte-Marie", "Morondava", "Ambositra", "Mananjary", "Sambava"],
        "La Réunion": ["Saint-Denis", "Saint-Paul", "Saint-Pierre", "Le Tampon", "Saint-André", "Saint-Louis", "Saint-Benoît", "Le Port", "Saint-Joseph", "Sainte-Marie", "Sainte-Suzanne", "Saint-Leu", "La Possession", "Bras-Panon", "Cilaos", "Salazie"],
        "Maurice": ["Port-Louis", "Beau Bassin-Rose Hill", "Vacoas-Phoenix", "Curepipe", "Quatre Bornes", "Triolet", "Goodlands", "Centre de Flacq", "Mahébourg", "Grand Baie", "Flic en Flac", "Tamarin"],
        "Belgique": ["Bruxelles", "Anvers", "Gand", "Charleroi", "Liège", "Bruges", "Namur", "Louvain", "Mons", "Alost", "Malines", "La Louvière", "Courtrai", "Ostende", "Hasselt", "Tournai", "Genk", "Seraing", "Verviers", "Mouscron"],
        "Suisse": ["Zurich", "Genève", "Bâle", "Lausanne", "Berne", "Winterthour", "Lucerne", "Saint-Gall", "Lugano", "Bienne", "Thoune", "Köniz", "Fribourg", "Schaffhouse", "Neuchâtel", "Sion"],
        "Canada": ["Toronto", "Montréal", "Vancouver", "Calgary", "Edmonton", "Ottawa", "Winnipeg", "Québec", "Hamilton", "Kitchener", "London", "Victoria", "Halifax", "Oshawa", "Windsor", "Saskatoon", "Regina", "Sherbrooke", "Laval", "Gatineau"],
        "Sénégal": ["Dakar", "Thiès", "Rufisque", "Kaolack", "M'Bour", "Saint-Louis", "Ziguinchor", "Diourbel", "Louga", "Tambacounda", "Kolda", "Richard-Toll", "Tivaouane", "Touba", "Kédougou"],
        "Côte d'Ivoire": ["Abidjan", "Bouaké", "Yamoussoukro", "Korhogo", "San-Pédro", "Man", "Divo", "Daloa", "Gagnoa", "Abengourou", "Anyama", "Agboville", "Dabou", "Grand-Bassam", "Bingerville"],
        "Maroc": ["Casablanca", "Rabat", "Fès", "Marrakech", "Tanger", "Agadir", "Meknès", "Oujda", "Kénitra", "Tétouan", "Safi", "El Jadida", "Nador", "Béni Mellal", "Essaouira", "Ouarzazate"],
        "Tunisie": ["Tunis", "Sfax", "Sousse", "Kairouan", "Bizerte", "Gabès", "Ariana", "Gafsa", "El Mourouj", "Kasserine", "Monastir", "La Marsa", "Hammamet", "Djerba", "Tozeur"],
        "Algérie": ["Alger", "Oran", "Constantine", "Annaba", "Blida", "Batna", "Sétif", "Djelfa", "Sidi Bel Abbès", "Biskra", "Tébessa", "Skikda", "Tiaret", "Béjaïa", "Tlemcen", "Ouargla"]
    };

    // ===== COUNTRY/CITY SELECTION =====
    const countrySelect = document.getElementById('country');
    const citySelect = document.getElementById('city');
    const locationInput = document.getElementById('location');
    const currentLocation = @json($ad->location);

    countrySelect.addEventListener('change', function() {
        const country = this.value;
        citySelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
        
        if (country && citiesByCountry[country]) {
            citySelect.disabled = false;
            citiesByCountry[country].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                // Pre-select if matches current location
                if (city === currentLocation) {
                    option.selected = true;
                }
                citySelect.appendChild(option);
            });
            // Add "Autre" option for manual input
            const otherOption = document.createElement('option');
            otherOption.value = "__other__";
            otherOption.textContent = "🔤 Autre ville (saisir manuellement)";
            citySelect.appendChild(otherOption);
            
            // If current location not found in list, show manual input
            if (currentLocation && !citiesByCountry[country].includes(currentLocation)) {
                citySelect.value = "__other__";
                locationInput.style.display = 'block';
            }
        } else {
            citySelect.disabled = true;
        }
    });

    citySelect.addEventListener('change', function() {
        if (this.value === "__other__") {
            locationInput.style.display = 'block';
            locationInput.focus();
        } else if (this.value) {
            locationInput.style.display = 'none';
            locationInput.value = this.value;
        } else {
            locationInput.style.display = 'none';
            locationInput.value = '';
        }
    });

    // ===== GEOLOCATION =====
    document.getElementById('detect-location').addEventListener('click', function() {
        const button = this;
        const resultDiv = document.getElementById('location-result');
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Détection en cours...';
        
        if (!navigator.geolocation) {
            resultDiv.innerHTML = '<span style="color: #f59e0b;"><i class="fas fa-exclamation-triangle me-1"></i>Géolocalisation non supportée</span>';
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-location-arrow me-1"></i>Détecter ma position automatiquement';
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            async function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                try {
                    const response = await fetch(`/api/reverse-geocode?lat=${lat}&lng=${lng}`);
                    const data = await response.json();
                    
                    if (data.city) {
                        locationInput.value = data.city;
                        locationInput.style.display = 'block';
                        resultDiv.innerHTML = '<span style="color: #00a884;"><i class="fas fa-check-circle me-1"></i>Position détectée : ' + data.city + '</span>';
                    } else {
                        locationInput.style.display = 'block';
                        locationInput.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                        resultDiv.innerHTML = '<span style="color: #00a884;"><i class="fas fa-info-circle me-1"></i>Coordonnées détectées</span>';
                    }
                } catch (error) {
                    locationInput.style.display = 'block';
                    locationInput.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    resultDiv.innerHTML = '<span style="color: #00a884;"><i class="fas fa-info-circle me-1"></i>Position détectée</span>';
                }
                
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-location-arrow me-1"></i>Détecter ma position automatiquement';
            },
            function(error) {
                resultDiv.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle me-1"></i>Impossible de détecter la position</span>';
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-location-arrow me-1"></i>Détecter ma position automatiquement';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });
</script>
@endsection







