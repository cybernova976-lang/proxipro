@extends('layouts.app')

@section('title', 'Contact - ProxiPro')

@push('styles')
<style>
    .contact-header {
        padding: 30px 0 10px;
        text-align: center;
    }
    .contact-header .breadcrumb {
        margin-bottom: 8px;
        font-size: 0.85rem;
    }
    .contact-header .breadcrumb a {
        color: var(--primary);
        text-decoration: none;
    }
    .contact-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 4px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .contact-header h1 i {
        color: var(--primary);
        font-size: 1.4rem;
    }
    .contact-header p {
        font-size: 0.95rem;
        color: var(--text-secondary);
        margin-bottom: 0;
    }
    .contact-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        border: 1px solid var(--border-subtle);
    }
    .form-label {
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
    }
    .form-control {
        border: 2px solid var(--border-subtle);
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-light);
    }
    .form-control::placeholder {
        color: #94a3b8;
    }
    textarea.form-control {
        min-height: 180px;
        resize: vertical;
    }
    .btn-submit {
        background: var(--primary);
        color: white;
        border: none;
        padding: 16px 40px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.05rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .btn-submit:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
    }
    .contact-info {
        background: var(--bg-body);
        border-radius: 16px;
        padding: 30px;
    }
    .contact-info-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
    }
    .contact-info-item:last-child {
        margin-bottom: 0;
    }
    .contact-info-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .contact-info-content h5 {
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    .contact-info-content p {
        color: var(--text-secondary);
        margin: 0;
    }
    .alert-success-custom {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        border: 1px solid #86efac;
        color: #166534;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
</style>
@endpush

@section('content')
<div class="container pb-5">
    <div class="contact-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home"></i> Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
        </nav>
        <h1><i class="fas fa-envelope"></i> Contactez-nous</h1>
        <p>Une question ? Une suggestion ? Notre équipe est là pour vous aider.</p>
    </div>
    <hr class="mb-4">
    @if(session('success'))
        <div class="alert-success-custom mb-4">
            <i class="fas fa-check-circle fa-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="contact-card">
                <h3 class="mb-4" style="font-weight: 700; color: var(--text-main);">
                    <i class="fas fa-paper-plane me-2" style="color: var(--primary);"></i>
                    Envoyez-nous un message
                </h3>
                
                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Votre nom *</label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', Auth::user()->name ?? '') }}"
                                   placeholder="Jean Dupont"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Votre email *</label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', Auth::user()->email ?? '') }}"
                                   placeholder="jean@exemple.com"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Sujet *</label>
                            <select name="subject" class="form-control @error('subject') is-invalid @enderror" required>
                                <option value="">Choisissez un sujet...</option>
                                <option value="Question générale" {{ old('subject') == 'Question générale' ? 'selected' : '' }}>Question générale</option>
                                <option value="Problème technique" {{ old('subject') == 'Problème technique' ? 'selected' : '' }}>Problème technique</option>
                                <option value="Signaler un abus" {{ old('subject') == 'Signaler un abus' ? 'selected' : '' }}>Signaler un abus</option>
                                <option value="Demande de partenariat" {{ old('subject') == 'Demande de partenariat' ? 'selected' : '' }}>Demande de partenariat</option>
                                <option value="Facturation" {{ old('subject') == 'Facturation' ? 'selected' : '' }}>Facturation / Paiement</option>
                                <option value="Suggestion" {{ old('subject') == 'Suggestion' ? 'selected' : '' }}>Suggestion d'amélioration</option>
                                <option value="Autre" {{ old('subject') == 'Autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Votre message *</label>
                            <textarea name="message" 
                                      class="form-control @error('message') is-invalid @enderror" 
                                      placeholder="Décrivez votre demande en détail..."
                                      required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i>
                                Envoyer le message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="contact-info">
                <h4 class="mb-4" style="font-weight: 700; color: var(--text-main);">
                    Informations de contact
                </h4>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info-content">
                        <h5>Email</h5>
                        <p>support@ProxiPro.com</p>
                    </div>
                </div>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-info-content">
                        <h5>Délai de réponse</h5>
                        <p>24 à 48 heures ouvrées</p>
                    </div>
                </div>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="contact-info-content">
                        <h5>FAQ</h5>
                        <p>Consultez notre FAQ pour des réponses rapides</p>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="text-muted small mb-3">Suivez-nous sur les réseaux</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
