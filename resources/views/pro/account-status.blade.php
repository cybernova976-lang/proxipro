@extends('pro.layout')
@section('title', 'Statut du compte - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Statut du compte</li>
            </ol>
        </nav>
        <h1>Statut du compte</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Changez votre statut professionnel selon votre situation.</p>
    </div>
</div>

{{-- Current Status --}}
<div class="pro-card mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
            @if($user->pro_status === 'entreprise') background: rgba(59,130,246,0.1); color: #3b82f6;
            @elseif($user->pro_status === 'auto-entrepreneur') background: rgba(168,85,247,0.1); color: #a855f7;
            @else background: rgba(16,185,129,0.1); color: #10b981; @endif">
            @if($user->pro_status === 'entreprise') <i class="fas fa-building"></i>
            @elseif($user->pro_status === 'auto-entrepreneur') <i class="fas fa-user-tie"></i>
            @else <i class="fas fa-user"></i> @endif
        </div>
        <div>
            <h5 class="fw-bold mb-1">Statut actuel : {{ $user->getAccountTypeLabel() }}</h5>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                @if($user->pro_status === 'entreprise')
                    Vous êtes inscrit en tant qu'entreprise. Vous avez accès à toutes les fonctionnalités professionnelles.
                @elseif($user->pro_status === 'auto-entrepreneur')
                    Vous êtes inscrit en tant qu'auto-entrepreneur / micro-entrepreneur.
                @else
                    Vous êtes inscrit en tant que particulier prestataire.
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Status Options --}}
<form method="POST" action="{{ route('pro.account-status.update') }}">
    @csrf @method('PUT')

    <div class="row g-3 mb-4">
        {{-- Particulier --}}
        <div class="col-md-4">
            <label class="d-block h-100 cursor-pointer">
                <input type="radio" name="pro_status" value="particulier" {{ $user->pro_status === 'particulier' ? 'checked' : '' }} class="d-none status-radio">
                <div class="pro-card mb-0 h-100 status-card {{ $user->pro_status === 'particulier' ? 'active' : '' }}" style="cursor: pointer; transition: all 0.2s;">
                    <div class="text-center">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(16,185,129,0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <h6 class="fw-bold">Particulier</h6>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">
                            Proposez vos services en tant que particulier. Idéal pour les activités occasionnelles (cours, bricolage, etc.).
                        </p>
                        <div class="mt-2">
                            <span class="pro-status pro-status-success" style="font-size: 0.72rem;">Pas de SIRET requis</span>
                        </div>
                    </div>
                </div>
            </label>
        </div>

        {{-- Auto-entrepreneur --}}
        <div class="col-md-4">
            <label class="d-block h-100 cursor-pointer">
                <input type="radio" name="pro_status" value="auto-entrepreneur" {{ $user->pro_status === 'auto-entrepreneur' ? 'checked' : '' }} class="d-none status-radio">
                <div class="pro-card mb-0 h-100 status-card {{ $user->pro_status === 'auto-entrepreneur' ? 'active' : '' }}" style="cursor: pointer; transition: all 0.2s;">
                    <div class="text-center">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(168,85,247,0.1); color: #a855f7; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h6 class="fw-bold">Auto-Entrepreneur</h6>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">
                            Micro-entreprise / auto-entrepreneur. Préparez vos documents puis validez la checklist de conformité avant émission.
                        </p>
                        <div class="mt-2">
                            <span class="pro-status pro-status-info" style="font-size: 0.72rem;">SIRET requis</span>
                        </div>
                    </div>
                </div>
            </label>
        </div>

        {{-- Entreprise --}}
        <div class="col-md-4">
            <label class="d-block h-100 cursor-pointer">
                <input type="radio" name="pro_status" value="entreprise" {{ $user->pro_status === 'entreprise' ? 'checked' : '' }} class="d-none status-radio">
                <div class="pro-card mb-0 h-100 status-card {{ $user->pro_status === 'entreprise' ? 'active' : '' }}" style="cursor: pointer; transition: all 0.2s;">
                    <div class="text-center">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(59,130,246,0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px;">
                            <i class="fas fa-building"></i>
                        </div>
                        <h6 class="fw-bold">Entreprise</h6>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">
                            SARL, SAS, EURL, SA… Outils commerciaux avec identité d’émetteur, TVA et traçabilité des documents.
                        </p>
                        <div class="mt-2">
                            <span class="pro-status pro-status-primary" style="font-size: 0.72rem;">SIRET + KBIS</span>
                        </div>
                    </div>
                </div>
            </label>
        </div>
    </div>

    {{-- Additional fields based on status --}}
    <div class="pro-card" id="statusFields">
        <h6 class="fw-bold mb-3">Informations complémentaires</h6>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nom de l'entreprise / Raison sociale</label>
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $user->company_name) }}" style="border-radius: 10px;">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Immatriculation professionnelle</label>
                <input type="text" name="siret" class="form-control" value="{{ old('siret', $user->siret) }}" style="border-radius: 10px;" placeholder="SIRET (France) ou registre local" maxlength="64">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Numéro TVA intracommunautaire</label>
                <input type="text" name="tva_number" class="form-control" value="{{ old('tva_number', $user->tva_number) }}" style="border-radius: 10px;" placeholder="FR12345678901">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">N° d'assurance RC Pro</label>
                <input type="text" name="insurance_number" class="form-control" value="{{ old('insurance_number', $user->insurance_number) }}" style="border-radius: 10px;">
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-pro-primary btn-lg">
            <i class="fas fa-save me-1"></i> Enregistrer le changement
        </button>
    </div>
</form>

{{-- Info Box --}}
<div class="pro-card mt-4" style="background: linear-gradient(135deg, rgba(59,130,246,0.05), rgba(168,85,247,0.05)); border-left: 4px solid var(--pro-primary);">
    <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2 text-primary"></i>Important à savoir</h6>
    <ul class="mb-0" style="font-size: 0.85rem; color: #64748b;">
        <li>Le changement de statut n'affecte pas vos données existantes (clients, devis, factures).</li>
        <li>Les informations saisies alimentent vos documents ; vous devez vérifier les mentions exigées pour votre activité et votre pays.</li>
        <li>Certains documents peuvent être requis selon votre nouveau statut (KBIS, attestation d'assurance…).</li>
        <li>Le changement est immédiat, mais l’émission de documents reste soumise au profil complet, au badge vérifié, à l’immatriculation et aux conditions PRO.</li>
        <li><a href="{{ route('pro.compliance') }}">Consultez la checklist de conformité PRO</a> avant tout envoi à un client.</li>
    </ul>
</div>

<style>
.status-card { border: 2px solid transparent; }
.status-card.active { border-color: var(--pro-primary); box-shadow: 0 0 0 3px rgba(168,85,247,0.15); }
.status-card:hover { border-color: rgba(168,85,247,0.3); transform: translateY(-2px); }
</style>

<script>
document.querySelectorAll('.status-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.status-card').forEach(c => c.classList.remove('active'));
        this.closest('label').querySelector('.status-card').classList.add('active');
    });
});
</script>
@endsection
