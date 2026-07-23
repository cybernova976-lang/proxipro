@extends('layouts.app')

@section('title', 'Convertisseur PDF - Lunamars')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Convertisseur PDF</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('info'))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                        </div>
                    @endif
                    
                    <p class="text-muted mb-4">
                        Convertissez vos fichiers vers ou depuis le format PDF. Formats supportés : PDF, Word, JPG, PNG.
                    </p>
                    
                    <form action="{{ route('tools.convert-pdf') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="file" class="form-label">Fichier à convertir</label>
                            <div class="border-2 border-dashed rounded-3 p-5 text-center" id="dropzone">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-2">Glissez-déposez votre fichier ici</p>
                                <p class="text-muted small mb-3">ou</p>
                                <label for="file" class="btn btn-outline-primary">
                                    <i class="fas fa-folder-open me-2"></i>Parcourir
                                </label>
                                <input type="file" class="d-none" id="file" name="file" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <p class="text-muted small mt-3 mb-0" id="fileName">Max. 10MB</p>
                            </div>
                            @error('file')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="format" class="form-label">Format de sortie</label>
                            <select class="form-select" id="format" name="format" required>
                                <option value="">Choisir un format...</option>
                                <option value="pdf">PDF</option>
                                <option value="jpg">Image JPG</option>
                                <option value="png">Image PNG</option>
                                <option value="docx">Word (DOCX)</option>
                            </select>
                            @error('format')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-sync-alt me-2"></i>Convertir
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Other Tools -->
            <div class="row g-3 mt-4">
                <div class="col-md-6">
                    <a href="{{ route('tools.image-compressor') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-compress fa-2x text-primary mb-3"></i>
                            <h6 class="mb-0">Compresseur d'images</h6>
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
document.getElementById('file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Max. 10MB';
    document.getElementById('fileName').textContent = fileName;
});

// Drag and drop
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file');

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
        document.getElementById('fileName').textContent = e.dataTransfer.files[0].name;
    }
});
</script>
@endsection
