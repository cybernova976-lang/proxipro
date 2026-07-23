@extends('layouts.app')

@section('title', 'Compresseur d\'images - Lunamars')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-compress me-2"></i>Compresseur d'images</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('info'))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                        </div>
                    @endif
                    
                    <p class="text-muted mb-4">
                        Réduisez la taille de vos images sans perdre en qualité. Idéal pour les annonces !
                    </p>
                    
                    <form action="{{ route('tools.compress-image') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="image" class="form-label">Image à compresser</label>
                            <div class="border-2 border-dashed rounded-3 p-5 text-center" id="dropzone">
                                <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                <p class="mb-2">Glissez-déposez votre image ici</p>
                                <p class="text-muted small mb-3">ou</p>
                                <label for="image" class="btn btn-outline-primary">
                                    <i class="fas fa-folder-open me-2"></i>Parcourir
                                </label>
                                <input type="file" class="d-none" id="image" name="image" 
                                       accept=".jpg,.jpeg,.png,.gif" required>
                                <p class="text-muted small mt-3 mb-0" id="fileName">JPG, PNG, GIF - Max. 10MB</p>
                            </div>
                            <div id="imagePreview" class="mt-3 text-center d-none">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                            @error('image')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="quality" class="form-label d-flex justify-content-between">
                                <span>Qualité de compression</span>
                                <span id="qualityValue" class="badge bg-primary">80%</span>
                            </label>
                            <input type="range" class="form-range" id="quality" name="quality" 
                                   min="10" max="100" value="80">
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Plus petite taille</span>
                                <span>Meilleure qualité</span>
                            </div>
                            @error('quality')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-compress me-2"></i>Compresser
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Other Tools -->
            <div class="row g-3 mt-4">
                <div class="col-md-6">
                    <a href="{{ route('tools.pdf-converter') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-file-pdf fa-2x text-danger mb-3"></i>
                            <h6 class="mb-0">Convertisseur PDF</h6>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('tools.qr-generator') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-qrcode fa-2x text-dark mb-3"></i>
                            <h6 class="mb-0">Générateur QR Code</h6>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('quality').addEventListener('input', function() {
    document.getElementById('qualityValue').textContent = this.value + '%';
});

document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = file.name;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.querySelector('img').src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
});

// Drag and drop
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('image');

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('border-primary', 'bg-light');
});

dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-primary', 'bg-light');
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-primary', 'bg-light');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        const event = new Event('change');
        fileInput.dispatchEvent(event);
    }
});
</script>
@endsection
