@extends('layouts.app')

@section('title', 'Publier une annonce - ProxiPro')

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
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 10px;
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

    /* ===== URGENT OPTION STYLES ===== */
    .badge-premium-label {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.65rem;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 700;
        letter-spacing: 0.5px;
        vertical-align: middle;
        margin-left: 8px;
    }

    .urgent-option-card {
        background: white;
        border: 2px solid #fee2e2;
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s;
    }

    .urgent-option-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, #ef4444, #f97316, #ef4444);
    }

    .urgent-option-card.locked {
        background: #f9fafb;
        border-color: #e5e7eb;
    }

    .urgent-toggle-container {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .urgent-toggle {
        position: relative;
        display: inline-block;
        width: 56px;
        height: 30px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .urgent-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .urgent-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        border-radius: 30px;
        transition: 0.3s;
    }

    .urgent-toggle-slider::before {
        content: "";
        position: absolute;
        height: 24px;
        width: 24px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }

    .urgent-toggle input:checked + .urgent-toggle-slider {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .urgent-toggle input:checked + .urgent-toggle-slider::before {
        transform: translateX(26px);
    }

    .urgent-benefits {
        margin-top: 16px;
        padding: 16px;
        background: linear-gradient(135deg, #fef2f2, #fff7ed);
        border-radius: 12px;
        border: 1px solid #fecaca;
    }

    .urgent-benefit-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
        font-size: 0.9rem;
        color: #374151;
    }

    .urgent-benefit-item i {
        width: 20px;
        text-align: center;
    }

    .locked-overlay {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 8px;
    }

    .locked-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .locked-icon i {
        font-size: 1.4rem;
        color: #9ca3af;
    }

    .locked-text {
        flex: 1;
    }

    .locked-text strong {
        color: #374151;
        font-size: 0.95rem;
    }

    .locked-text p {
        color: #6b7280;
        font-size: 0.85rem;
        margin-top: 4px;
    }

    .btn-upgrade-premium {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 8px 20px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-upgrade-premium:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        color: white;
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

    /* Pre-fill banner */
    .prefill-banner {
        background: linear-gradient(135deg, #ede9fe, #f0f4ff);
        border: 2px solid #c4b5fd;
        border-radius: 14px;
        padding: 16px 22px;
        margin-bottom: 24px;
        display: none;
        align-items: center;
        gap: 14px;
    }
    .prefill-banner.visible { display: flex; }
    .prefill-banner-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #7c3aed, #6366f1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .prefill-banner-text { flex: 1; }
    .prefill-banner-text strong {
        color: #4c1d95;
        display: block;
        margin-bottom: 2px;
    }
    .prefill-banner-text small { color: #6b7280; }
    .prefill-banner-close {
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        font-size: 1.1rem;
        padding: 4px;
    }
    .prefill-banner-close:hover { color: #6b7280; }
</style>
@endpush

@section('content')
<div class="publish-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 id="page-title"><i class="fas fa-plus-circle me-2"></i>Publier une annonce</h1>
        <p id="page-subtitle">Décrivez votre service pour atteindre des milliers de personnes</p>
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

    <!-- Pre-fill Banner (shown when coming from popup) -->
    <div class="prefill-banner" id="prefill-banner">
        <div class="prefill-banner-icon"><i class="fas fa-magic"></i></div>
        <div class="prefill-banner-text">
            <strong id="prefill-banner-title"></strong>
            <small>Complétez les informations ci-dessous pour finaliser votre publication.</small>
        </div>
        <button class="prefill-banner-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    
    <form method="POST" action="{{ route('ads.store') }}" enctype="multipart/form-data" class="form-card">
        @csrf
        <input type="hidden" name="service_type" id="service_type" value="offre">

        <!-- Catégorie -->
        <div class="form-section" id="category-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-th-large"></i></div>
                <h4 class="section-title" id="category-section-title">Que souhaitez-vous proposer ?</h4>
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
            
            <input type="hidden" name="main_category" id="main_category" value="{{ old('main_category') }}">
            <input type="hidden" name="category" id="category" value="{{ old('category') }}">
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
                       id="title" name="title" value="{{ old('title') }}" 
                       placeholder="Ex: Plombier disponible pour dépannage urgent" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div>
                <label for="description" class="form-label">Description détaillée <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="5" 
                          placeholder="Décrivez votre service en détail : expérience, disponibilités, conditions..." required>{{ old('description') }}</textarea>
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
                    <label for="country" class="form-label">Pays / Département <span class="text-danger">*</span></label>
                    <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
                        <option value="">-- Sélectionner un pays / département --</option>
                        <option value="France" {{ old('country') == 'France' ? 'selected' : '' }}>🇫🇷 France</option>
                        <option value="Mayotte" {{ old('country') == 'Mayotte' ? 'selected' : '' }}>🇾🇹 Mayotte</option>
                        <option value="Madagascar" {{ old('country') == 'Madagascar' ? 'selected' : '' }}>🇲🇬 Madagascar</option>
                        <option value="La Réunion" {{ old('country') == 'La Réunion' ? 'selected' : '' }}>🇷🇪 La Réunion</option>
                        <option value="Maurice" {{ old('country') == 'Maurice' ? 'selected' : '' }}>🇲🇺 Maurice</option>
                        <option value="Belgique" {{ old('country') == 'Belgique' ? 'selected' : '' }}>🇧🇪 Belgique</option>
                        <option value="Suisse" {{ old('country') == 'Suisse' ? 'selected' : '' }}>🇨🇭 Suisse</option>
                        <option value="Canada" {{ old('country') == 'Canada' ? 'selected' : '' }}>🇨🇦 Canada</option>
                        <option value="Sénégal" {{ old('country') == 'Sénégal' ? 'selected' : '' }}>🇸🇳 Sénégal</option>
                        <option value="Côte d'Ivoire" {{ old('country') == "Côte d'Ivoire" ? 'selected' : '' }}>🇨🇮 Côte d'Ivoire</option>
                        <option value="Maroc" {{ old('country') == 'Maroc' ? 'selected' : '' }}>🇲🇦 Maroc</option>
                        <option value="Tunisie" {{ old('country') == 'Tunisie' ? 'selected' : '' }}>🇹🇳 Tunisie</option>
                        <option value="Algérie" {{ old('country') == 'Algérie' ? 'selected' : '' }}>🇩🇿 Algérie</option>
                    </select>
                    @error('country')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="city" class="form-label">Ville <span class="text-danger">*</span></label>
                    <select class="form-select" id="city" name="city" disabled>
                        <option value="">-- Sélectionnez d'abord un pays --</option>
                    </select>
                    <input type="text" class="form-control mt-2 @error('location') is-invalid @enderror" 
                           id="location" name="location" value="{{ old('location') }}" 
                           placeholder="Ou saisissez votre ville manuellement" style="display: none;">
                    @error('location')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div id="location-result" class="form-hint"></div>
                </div>
                
                <div class="col-md-4">
                    <label for="radius_km" class="form-label">Rayon d'intervention</label>
                    <select class="form-select" id="radius_km" name="radius_km">
                        <option value="5" {{ old('radius_km') == 5 ? 'selected' : '' }}>5 km</option>
                        <option value="10" {{ old('radius_km', 10) == 10 ? 'selected' : '' }}>10 km</option>
                        <option value="25" {{ old('radius_km') == 25 ? 'selected' : '' }}>25 km</option>
                        <option value="50" {{ old('radius_km') == 50 ? 'selected' : '' }}>50 km</option>
                        <option value="100" {{ old('radius_km') == 100 ? 'selected' : '' }}>100 km</option>
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
                    <label for="price" class="form-label">Tarif horaire</label>
                    <div class="price-input-group">
                        <input type="number" step="0.01" min="0" 
                               class="form-control @error('price') is-invalid @enderror" 
                               id="price" name="price" value="{{ old('price') }}" 
                               placeholder="Ex : 10, 15, 25...">
                        <span class="input-suffix">€/h</span>
                    </div>
                    @error('price')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <p class="form-hint">Indiquez votre tarif horaire. Laissez vide si le prix est à discuter.</p>
                </div>
            </div>
        </div>
        
        <!-- Paramètres de publication -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><i class="fas fa-cog"></i></div>
                <h4 class="section-title">Paramètres de publication</h4>
            </div>
            
            <div class="row">
                <!-- Qui peut répondre -->
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold"><i class="fas fa-users me-1"></i> Qui peut répondre ?</label>
                    <div class="d-flex flex-column gap-2 mt-2">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border {{ old('reply_restriction', 'everyone') === 'everyone' ? 'border-primary bg-light' : '' }}" style="cursor:pointer;">
                            <input type="radio" name="reply_restriction" value="everyone" {{ old('reply_restriction', 'everyone') === 'everyone' ? 'checked' : '' }} onchange="this.closest('.col-md-6').querySelectorAll('label.border').forEach(l => l.classList.remove('border-primary','bg-light')); this.closest('label').classList.add('border-primary','bg-light');">
                            <div>
                                <strong><i class="fas fa-globe me-1 text-primary"></i> Tout le monde</strong>
                                <small class="d-block text-muted">Tous les utilisateurs peuvent répondre</small>
                            </div>
                        </label>
                        <label class="d-flex align-items-center gap-2 p-2 rounded border {{ old('reply_restriction') === 'pro_only' ? 'border-primary bg-light' : '' }}" style="cursor:pointer;">
                            <input type="radio" name="reply_restriction" value="pro_only" {{ old('reply_restriction') === 'pro_only' ? 'checked' : '' }} onchange="this.closest('.col-md-6').querySelectorAll('label.border').forEach(l => l.classList.remove('border-primary','bg-light')); this.closest('label').classList.add('border-primary','bg-light');">
                            <div>
                                <strong><i class="fas fa-briefcase me-1 text-success"></i> Professionnels uniquement</strong>
                                <small class="d-block text-muted">Seuls les profils identifiés comme PRO peuvent répondre</small>
                            </div>
                        </label>
                        <label class="d-flex align-items-center gap-2 p-2 rounded border {{ old('reply_restriction') === 'verified_only' ? 'border-primary bg-light' : '' }}" style="cursor:pointer;">
                            <input type="radio" name="reply_restriction" value="verified_only" {{ old('reply_restriction') === 'verified_only' ? 'checked' : '' }} onchange="this.closest('.col-md-6').querySelectorAll('label.border').forEach(l => l.classList.remove('border-primary','bg-light')); this.closest('label').classList.add('border-primary','bg-light');">
                            <div>
                                <strong><i class="fas fa-check-circle me-1 text-info"></i> Profils vérifiés uniquement</strong>
                                <small class="d-block text-muted">Seuls les profils vérifiés peuvent répondre</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Visibilité de la publication -->
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold"><i class="fas fa-eye me-1"></i> Visibilité de la publication</label>
                    <div class="d-flex flex-column gap-2 mt-2">
                        <label class="d-flex align-items-center gap-2 p-2 rounded border {{ old('visibility', 'public') === 'public' ? 'border-primary bg-light' : '' }}" style="cursor:pointer;">
                            <input type="radio" name="visibility" value="public" {{ old('visibility', 'public') === 'public' ? 'checked' : '' }} onchange="this.closest('.col-md-6').querySelectorAll('label.border').forEach(l => l.classList.remove('border-primary','bg-light')); this.closest('label').classList.add('border-primary','bg-light'); document.getElementById('targetCategoriesWrapper').style.display='none';">
                            <div>
                                <strong><i class="fas fa-globe-americas me-1 text-primary"></i> Page publique</strong>
                                <small class="d-block text-muted">Visible par tous sur le feed public</small>
                            </div>
                        </label>
                        <label class="d-flex align-items-center gap-2 p-2 rounded border {{ old('visibility') === 'pro_targeted' ? 'border-primary bg-light' : '' }}" style="cursor:pointer;">
                            <input type="radio" name="visibility" value="pro_targeted" {{ old('visibility') === 'pro_targeted' ? 'checked' : '' }} onchange="this.closest('.col-md-6').querySelectorAll('label.border').forEach(l => l.classList.remove('border-primary','bg-light')); this.closest('label').classList.add('border-primary','bg-light'); document.getElementById('targetCategoriesWrapper').style.display='block';">
                            <div>
                                <strong><i class="fas fa-paper-plane me-1 text-warning"></i> Envoyé aux professionnels</strong>
                                <small class="d-block text-muted">Envoyé uniquement aux pros inscrits dans les catégories sélectionnées</small>
                            </div>
                        </label>
                    </div>

                    <!-- Sélection des catégories ciblées -->
                    <div id="targetCategoriesWrapper" class="mt-3" style="display: {{ old('visibility') === 'pro_targeted' ? 'block' : 'none' }};">
                        <label class="form-label"><i class="fas fa-tags me-1"></i> Catégories ciblées</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background: #f8f9fa;">
                            @foreach($categories as $cat)
                                <label class="d-flex align-items-center gap-2 mb-1" style="cursor:pointer; font-size: 0.9rem;">
                                    <input type="checkbox" name="target_categories[]" value="{{ $cat }}" 
                                        {{ is_array(old('target_categories')) && in_array($cat, old('target_categories')) ? 'checked' : '' }}>
                                    {{ $cat }}
                                </label>
                            @endforeach
                        </div>
                        <small class="text-muted">Sélectionnez les catégories de professionnels à cibler</small>
                    </div>
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
                        <input type="checkbox" name="accept_conditions" id="accept_conditions" required 
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
            <button type="submit" class="btn-publish" id="btn-publish" disabled>
                <i class="fas fa-paper-plane me-2"></i>Publier mon annonce
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
        
        // Clear existing files if new selection from input
        if (files.length > 0 && uploadedFiles.length === 0) {
            previewGrid.innerHTML = '';
        }
        
        const filesToAdd = Array.from(files);
        
        for (let i = 0; i < filesToAdd.length; i++) {
            const file = filesToAdd[i];
            
            if (uploadedFiles.length >= maxFiles) {
                alert('Maximum ' + maxFiles + ' photos autorisées');
                break;
            }
            
            if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
                alert('Format non supporté: ' + file.name);
                continue;
            }
            
            if (file.size > maxSize) {
                alert('Fichier trop volumineux (max 5MB): ' + file.name);
                continue;
            }
            
            // Check for duplicate files
            const isDuplicate = uploadedFiles.some(f => f.name === file.name && f.size === file.size);
            if (isDuplicate) {
                alert('Fichier déjà ajouté: ' + file.name);
                continue;
            }
            
            uploadedFiles.push(file);
            displayPreview(file, uploadedFiles.length - 1);
        }
        
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
        console.log('Updated file input with', uploadedFiles.length, 'files');
    }

    // ===== CONDITIONS CHECKBOX =====
    document.getElementById('accept_conditions').addEventListener('change', function() {
        document.getElementById('btn-publish').disabled = !this.checked;
    });

    // ===== FORM SUBMIT VALIDATION =====
    document.querySelector('form.form-card').addEventListener('submit', function(e) {
        const countryVal = document.getElementById('country').value;
        const cityVal = document.getElementById('city').value;
        const locationVal = document.getElementById('location').value;
        const categoryVal = document.getElementById('category').value;
        
        // Validate country is selected
        if (!countryVal) {
            e.preventDefault();
            alert('Veuillez sélectionner un pays.');
            document.getElementById('country').focus();
            return false;
        }
        
        // Validate city/location
        if (!locationVal && (!cityVal || cityVal === '__other__')) {
            e.preventDefault();
            alert('Veuillez sélectionner une ville ou saisir une adresse.');
            if (cityVal === '__other__') {
                document.getElementById('location').focus();
            } else {
                document.getElementById('city').focus();
            }
            return false;
        }
        
        // If city is selected (not "__other__"), copy to location field
        if (cityVal && cityVal !== '__other__' && !locationVal) {
            document.getElementById('location').value = cityVal;
        }
        
        // Validate category
        if (!categoryVal) {
            e.preventDefault();
            alert('Veuillez sélectionner une catégorie.');
            document.getElementById('category-section').scrollIntoView({ behavior: 'smooth' });
            return false;
        }
        
        // Final update of file input before submit
        updateFileInput();
        
        return true;
    });

    // ===== RESTORE OLD VALUES + URL PARAMS =====
    document.addEventListener('DOMContentLoaded', function() {
        // 1) Restore old values after validation error
        const oldMainCat = document.getElementById('main_category').value;
        const oldCat = document.getElementById('category').value;
        
        if (oldMainCat && oldCat && categoriesData[oldMainCat]) {
            const icon = categoriesData[oldMainCat].icon;
            selectSubCategory(oldCat, oldMainCat, icon);
        }

        // 2) Parse URL parameters (from feed popup or "Proposer mes services")
        const urlParams = new URLSearchParams(window.location.search);
        const paramCategory = urlParams.get('category');
        const paramSubcategory = urlParams.get('subcategory');
        const paramDescription = urlParams.get('description');
        const paramType = urlParams.get('type');

        // Set service type
        if (paramType === 'demande') {
            document.getElementById('service_type').value = 'demande';
            document.getElementById('page-title').innerHTML = '<i class="fas fa-search me-2"></i>Publier une demande';
            document.getElementById('page-subtitle').textContent = 'D\u00e9crivez votre besoin pour trouver le professionnel id\u00e9al';
            document.getElementById('category-section-title').textContent = 'Quel service recherchez-vous ?';
            // Update placeholders for demande context
            const titleField = document.getElementById('title');
            const descField = document.getElementById('description');
            if (titleField) titleField.placeholder = 'Ex: Recherche plombier pour fuite urgente à Mamoudzou';
            if (descField) descField.placeholder = 'Décrivez précisément ce dont vous avez besoin : type de travaux, lieu, urgence, budget...';
        } else if (paramType === 'service') {
            document.getElementById('service_type').value = 'offre';
            document.getElementById('page-title').innerHTML = '<i class="fas fa-briefcase me-2"></i>Proposer mes services';
            document.getElementById('page-subtitle').textContent = 'Présentez votre expertise pour attirer de nouveaux clients';
            document.getElementById('category-section-title').textContent = 'Quel service proposez-vous ?';
        }

        // Auto-select category from URL
        if (paramCategory && !oldMainCat) {
            // Find matching category in categoriesData
            let matchedCat = null;
            let matchedIcon = null;
            for (const [catName, catData] of Object.entries(categoriesData)) {
                if (catName === paramCategory) {
                    matchedCat = catName;
                    matchedIcon = catData.icon;
                    break;
                }
            }

            if (matchedCat && matchedIcon) {
                if (paramSubcategory) {
                    // Auto-select subcategory too
                    const subs = categoriesData[matchedCat].subcategories || [];
                    const matchedSub = subs.find(s => s === paramSubcategory);
                    if (matchedSub) {
                        selectSubCategory(matchedSub, matchedCat, matchedIcon);
                    } else {
                        selectMainCategory(matchedCat, matchedIcon);
                    }
                } else {
                    selectMainCategory(matchedCat, matchedIcon);
                }
            }
        }

        // Pre-fill description from URL
        if (paramDescription && !document.getElementById('description').value) {
            document.getElementById('description').value = paramDescription;
        }

        // Show pre-fill banner if params were present
        if (paramCategory || paramDescription) {
            const banner = document.getElementById('prefill-banner');
            const bannerTitle = document.getElementById('prefill-banner-title');
            if (paramCategory && paramSubcategory) {
                bannerTitle.textContent = paramCategory + ' \u2192 ' + paramSubcategory;
            } else if (paramCategory) {
                bannerTitle.textContent = paramCategory;
            } else {
                bannerTitle.textContent = 'Données pré-remplies';
            }
            banner.classList.add('visible');

            // Scroll to first empty required section
            setTimeout(() => {
                const titleField = document.getElementById('title');
                if (titleField && !titleField.value) {
                    titleField.closest('.form-section').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    titleField.focus();
                }
            }, 400);
        }

        // Clean URL without reloading
        if (window.history.replaceState && (paramCategory || paramType || paramDescription)) {
            window.history.replaceState({}, '', window.location.pathname);
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

    countrySelect.addEventListener('change', function() {
        const country = this.value;
        citySelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
        
        if (country && citiesByCountry[country]) {
            citySelect.disabled = false;
            citiesByCountry[country].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
            // Add "Autre" option for manual input
            const otherOption = document.createElement('option');
            otherOption.value = "__other__";
            otherOption.textContent = "🔤 Autre ville (saisir manuellement)";
            citySelect.appendChild(otherOption);
        } else {
            citySelect.disabled = true;
        }
        
        // Reset location input
        locationInput.style.display = 'none';
        locationInput.value = '';
    });

    citySelect.addEventListener('change', function() {
        if (this.value === "__other__") {
            locationInput.style.display = 'block';
            locationInput.focus();
            locationInput.required = true;
        } else if (this.value) {
            locationInput.style.display = 'none';
            locationInput.value = this.value;
            locationInput.required = false;
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
                        // Try to find and select the country
                        if (data.country) {
                            for (let i = 0; i < countrySelect.options.length; i++) {
                                if (countrySelect.options[i].value === data.country || 
                                    countrySelect.options[i].text.includes(data.country)) {
                                    countrySelect.selectedIndex = i;
                                    countrySelect.dispatchEvent(new Event('change'));
                                    break;
                                }
                            }
                        }
                        
                        // Set the city
                        setTimeout(() => {
                            let cityFound = false;
                            for (let i = 0; i < citySelect.options.length; i++) {
                                if (citySelect.options[i].value === data.city) {
                                    citySelect.selectedIndex = i;
                                    citySelect.dispatchEvent(new Event('change'));
                                    cityFound = true;
                                    break;
                                }
                            }
                            
                            if (!cityFound) {
                                // Select "Autre" and fill manual input
                                for (let i = 0; i < citySelect.options.length; i++) {
                                    if (citySelect.options[i].value === "__other__") {
                                        citySelect.selectedIndex = i;
                                        citySelect.dispatchEvent(new Event('change'));
                                        locationInput.value = data.city;
                                        break;
                                    }
                                }
                            }
                        }, 100);
                        
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
