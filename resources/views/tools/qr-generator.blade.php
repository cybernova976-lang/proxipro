@extends('layouts.app')

@section('title', 'Générateur QR Code - Lunamars')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Générateur QR Code</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('info'))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                        </div>
                    @endif
                    
                    <p class="text-muted mb-4">
                        Créez des QR codes personnalisés pour vos liens, textes, ou coordonnées.
                    </p>
                    
                    <form action="{{ route('tools.generate-qr') }}" method="POST">
                        @csrf
                        
                        <!-- Content Type -->
                        <div class="mb-4">
                            <label class="form-label">Type de contenu</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type" id="type-url" value="url" checked>
                                <label class="btn btn-outline-dark" for="type-url">
                                    <i class="fas fa-link me-1"></i>URL
                                </label>
                                
                                <input type="radio" class="btn-check" name="type" id="type-text" value="text">
                                <label class="btn btn-outline-dark" for="type-text">
                                    <i class="fas fa-font me-1"></i>Texte
                                </label>
                                
                                <input type="radio" class="btn-check" name="type" id="type-email" value="email">
                                <label class="btn btn-outline-dark" for="type-email">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                
                                <input type="radio" class="btn-check" name="type" id="type-phone" value="phone">
                                <label class="btn btn-outline-dark" for="type-phone">
                                    <i class="fas fa-phone me-1"></i>Téléphone
                                </label>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="mb-4">
                            <label for="content" class="form-label">Contenu du QR Code</label>
                            <textarea class="form-control" id="content" name="content" rows="3" 
                                      placeholder="https://example.com" required maxlength="2000"></textarea>
                            <div class="form-text">Maximum 2000 caractères</div>
                            @error('content')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Size -->
                        <div class="mb-4">
                            <label for="size" class="form-label d-flex justify-content-between">
                                <span>Taille du QR Code</span>
                                <span id="sizeValue" class="badge bg-dark">300px</span>
                            </label>
                            <input type="range" class="form-range" id="size" name="size" 
                                   min="100" max="1000" value="300" step="50">
                            <div class="d-flex justify-content-between text-muted small">
                                <span>100px</span>
                                <span>1000px</span>
                            </div>
                            @error('size')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fas fa-qrcode me-2"></i>Générer le QR Code
                        </button>
                    </form>
                    
                    <!-- Preview Area -->
                    <div id="qrPreview" class="text-center mt-4 p-4 bg-light rounded-3 d-none">
                        <div id="qrCode"></div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" id="downloadBtn">
                                <i class="fas fa-download me-1"></i>Télécharger
                            </button>
                        </div>
                    </div>
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
                    <a href="{{ route('tools.image-compressor') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-compress fa-2x text-primary mb-3"></i>
                            <h6 class="mb-0">Compresseur d'images</h6>
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
document.getElementById('size').addEventListener('input', function() {
    document.getElementById('sizeValue').textContent = this.value + 'px';
});

// Update placeholder based on type
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const content = document.getElementById('content');
        switch(this.value) {
            case 'url':
                content.placeholder = 'https://example.com';
                break;
            case 'text':
                content.placeholder = 'Votre texte ici...';
                break;
            case 'email':
                content.placeholder = 'email@example.com';
                break;
            case 'phone':
                content.placeholder = '+33 6 12 34 56 78';
                break;
        }
    });
});
</script>
@endsection
