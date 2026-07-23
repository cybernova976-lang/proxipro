@extends('layouts.app')

@section('title', 'Signaler un objet - Lunamars')

@push('styles')
<style>
    .create-hero {
        background: linear-gradient(135deg, #f97316, #ea580c, #dc2626);
        padding: 48px 16px;
        position: relative;
        overflow: hidden;
    }
    .create-hero::before {
        content: '';
        position: absolute;
        top: 10px;
        left: 10px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        filter: blur(40px);
    }
    .create-hero::after {
        content: '';
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 300px;
        height: 300px;
        background: rgba(234, 179, 8, 0.2);
        border-radius: 50%;
        filter: blur(40px);
    }
    .form-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        padding: 32px;
        max-width: 700px;
        margin: -60px auto 40px;
        position: relative;
        z-index: 10;
    }
    .type-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .type-option {
        padding: 24px;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
    }
    .type-option:hover {
        border-color: #d1d5db;
        background: #f9fafb;
    }
    .type-option.active-lost {
        border-color: #ef4444;
        background: #fef2f2;
    }
    .type-option.active-found {
        border-color: #22c55e;
        background: #f0fdf4;
    }
    .type-option input {
        display: none;
    }
    .type-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    .type-label {
        font-weight: 700;
        color: #111827;
    }
    .form-label-custom {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
        font-size: 0.875rem;
    }
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.2s;
        background: #f9fafb;
    }
    .form-input:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        background: white;
    }
    .form-select-custom {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        background: #f9fafb;
        cursor: pointer;
    }
    .form-select-custom:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
    }
    .image-upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }
    .image-upload-zone:hover {
        border-color: #f97316;
        background: #fff7ed;
    }
    .image-upload-zone.dragover {
        border-color: #f97316;
        background: #fff7ed;
    }
    .upload-icon {
        font-size: 3rem;
        margin-bottom: 8px;
    }
    .btn-submit {
        background: linear-gradient(135deg, #f97316, #dc2626);
        color: white;
        font-weight: 700;
        padding: 16px 32px;
        border-radius: 12px;
        border: none;
        width: 100%;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
    }
    .btn-submit:hover {
        background: linear-gradient(135deg, #ea580c, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4);
    }
    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .type-selector {
            grid-template-columns: 1fr;
        }
    }
    .preview-images {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }
    .preview-image {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #e5e7eb;
    }
    .back-link {
        color: white;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        opacity: 0.9;
        transition: opacity 0.2s;
    }
    .back-link:hover {
        color: white;
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div style="min-height: 100vh; background: #f9fafb;">
    <!-- Hero -->
    <section class="create-hero">
        <div style="position: relative; z-index: 10; max-width: 700px; margin: 0 auto; text-align: center; color: white;">
            <a href="{{ route('lost-items.index') }}" class="back-link" style="margin-bottom: 16px; display: inline-flex;">
                <i class="fas fa-arrow-left"></i> Retour aux annonces
            </a>
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 8px;">Signaler un objet</h1>
            <p style="color: #fed7aa;">Remplissez le formulaire pour signaler un objet perdu ou trouvé</p>
        </div>
    </section>

    <!-- Formulaire -->
    <div class="form-card">
        @if($errors->any())
            <div class="alert alert-danger mb-4" style="border-radius: 12px;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lost-items.store') }}" method="POST" enctype="multipart/form-data" id="lostItemForm">
            @csrf

            <!-- Type de signalement -->
            <div class="type-selector">
                <label class="type-option {{ old('type', 'lost') == 'lost' ? 'active-lost' : '' }}" id="type-lost">
                    <input type="radio" name="type" value="lost" {{ old('type', 'lost') == 'lost' ? 'checked' : '' }}>
                    <div class="type-icon">🔍</div>
                    <div class="type-label">J'ai perdu</div>
                </label>
                <label class="type-option {{ old('type') == 'found' ? 'active-found' : '' }}" id="type-found">
                    <input type="radio" name="type" value="found" {{ old('type') == 'found' ? 'checked' : '' }}>
                    <div class="type-icon">✅</div>
                    <div class="type-label">J'ai trouvé</div>
                </label>
            </div>

            <!-- Catégorie -->
            <div style="margin-bottom: 20px;">
                <label class="form-label-custom">Catégorie *</label>
                <select name="category" class="form-select-custom" required>
                    <option value="">Sélectionnez une catégorie</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Titre -->
            <div style="margin-bottom: 20px;">
                <label class="form-label-custom">Titre / Description courte *</label>
                <input type="text" name="title" class="form-input" value="{{ old('title') }}" 
                       placeholder="Ex: iPhone 14 Pro noir, Clés avec porte-clé bleu..." required>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 20px;">
                <label class="form-label-custom">Description détaillée *</label>
                <textarea name="description" class="form-input" rows="4" 
                          placeholder="Décrivez l'objet en détail et les circonstances de la perte/découverte..." required>{{ old('description') }}</textarea>
            </div>

            <!-- Lieu et Date -->
            <div class="form-row" style="margin-bottom: 20px;">
                <div>
                    <label class="form-label-custom">Lieu *</label>
                    <input type="text" name="location" class="form-input" value="{{ old('location') }}" 
                           placeholder="Ville, quartier, adresse..." required>
                </div>
                <div>
                    <label class="form-label-custom">Date *</label>
                    <input type="date" name="date" class="form-input" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Téléphone et Récompense -->
            <div class="form-row" style="margin-bottom: 20px;">
                <div>
                    <label class="form-label-custom">Téléphone de contact</label>
                    <input type="tel" name="contact_phone" class="form-input" value="{{ old('contact_phone') }}" 
                           placeholder="06 XX XX XX XX">
                </div>
                <div>
                    <label class="form-label-custom">Récompense (€)</label>
                    <input type="number" name="reward" class="form-input" value="{{ old('reward') }}" 
                           placeholder="0" min="0" step="1">
                </div>
            </div>

            <!-- Upload d'images -->
            <div style="margin-bottom: 24px;">
                <label class="form-label-custom">Photos (optionnel)</label>
                <div class="image-upload-zone" id="dropZone">
                    <div class="upload-icon">📷</div>
                    <p style="color: #64748b; margin-bottom: 8px;">Glissez vos images ici ou cliquez pour sélectionner</p>
                    <p style="color: #9ca3af; font-size: 0.875rem;">JPG, PNG - Max 2MB par image</p>
                    <input type="file" name="images[]" id="imageInput" multiple accept="image/*" style="display: none;">
                </div>
                <div class="preview-images" id="previewImages"></div>
            </div>

            <!-- Bouton de soumission -->
            <button type="submit" class="btn-submit" id="submitBtn">
                📢 Publier le signalement
            </button>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Gestion des types
    document.querySelectorAll('.type-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.type-option').forEach(o => {
                o.classList.remove('active-lost', 'active-found');
            });
            const radio = this.querySelector('input');
            if (radio.value === 'lost') {
                this.classList.add('active-lost');
            } else {
                this.classList.add('active-found');
            }
        });
    });

    // Gestion de l'upload d'images
    const dropZone = document.getElementById('dropZone');
    const imageInput = document.getElementById('imageInput');
    const previewImages = document.getElementById('previewImages');

    dropZone.addEventListener('click', () => imageInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    imageInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        previewImages.innerHTML = '';
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-image');
                    previewImages.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Empêcher double soumission
    document.getElementById('lostItemForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Publication en cours...';
    });
</script>
@endsection
