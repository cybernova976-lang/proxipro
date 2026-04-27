@extends('layouts.app')

@section('title', $ad->title . ' - ProxiPro')

@push('styles')
<style>
    * { font-family: 'Poppins', sans-serif; }
    
    .ad-detail-container { max-width: 1200px; margin: 0 auto; padding: 30px 15px; }
    .ad-main-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); border-radius: 25px; border: 1px solid rgba(0,0,0,0.08); overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.1); }
    .ad-image-section { background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); min-height: 300px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .ad-image-section i { font-size: 80px; color: rgba(255,255,255,0.3); }
    .ad-image-section img { width: 100%; height: 100%; object-fit: cover; }
    .badge-type { position: absolute; top: 20px; left: 20px; padding: 10px 20px; border-radius: 30px; font-weight: 600; }
    .badge-offre { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
    .badge-demande { background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; }
    .ad-content-section { padding: 35px; }
    .ad-title { color: #2d3748; font-size: 1.8rem; font-weight: 700; margin-bottom: 15px; }
    .ad-meta { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
    .ad-meta-item { display: flex; align-items: center; color: #4a5568; }
    .ad-meta-item i { width: 32px; height: 32px; background: rgba(124, 58, 237,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 8px; color: #7c3aed; font-size: 0.9rem; }
    .ad-price { font-size: 2rem; font-weight: 700; color: #28a745; margin-bottom: 25px; }
    .ad-description { color: #4a5568; line-height: 1.8; padding: 20px; background: #f7fafc; border-radius: 12px; border-left: 4px solid #7c3aed; margin-bottom: 25px; }
    .seller-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid rgba(0,0,0,0.08); padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    .seller-avatar { width: 70px; height: 70px; background: linear-gradient(135deg, #7c3aed, #9333ea); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
    .seller-avatar i { font-size: 28px; color: white; }
    .seller-name { color: #2d3748; font-size: 1.2rem; font-weight: 600; text-align: center; margin-bottom: 5px; }
    .seller-since { color: #718096; text-align: center; margin-bottom: 15px; font-size: 0.9rem; }
    .seller-stats { display: flex; justify-content: space-around; margin-bottom: 20px; padding: 12px 0; border-top: 1px solid rgba(0,0,0,0.08); border-bottom: 1px solid rgba(0,0,0,0.08); }
    .seller-stat { text-align: center; }
    .seller-stat-value { color: #7c3aed; font-size: 1.3rem; font-weight: 700; }
    .seller-stat-label { color: #718096; font-size: 0.8rem; }
    .btn-contact { width: 100%; padding: 12px; border-radius: 12px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; margin-bottom: 10px; }
    .btn-contact-primary { background: linear-gradient(135deg, #7c3aed, #9333ea); color: white; }
    .btn-contact-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(124, 58, 237,0.4); color: white; }
    .btn-contact-secondary { background: #f7fafc; border: 1px solid rgba(0,0,0,0.1); color: #4a5568; }
    .btn-contact-secondary:hover { background: #edf2f7; color: #2d3748; }
    .similar-ads { margin-top: 40px; }
    .similar-ads h3 { color: #2d3748; margin-bottom: 20px; }
    .similar-ad-card { background: rgba(255,255,255,0.95); border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .similar-ad-card:hover { transform: translateY(-3px); border-color: rgba(124, 58, 237,0.5); box-shadow: 0 8px 25px rgba(124, 58, 237,0.15); }
    .similar-ad-title { color: #2d3748; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
    .similar-ad-price { color: #28a745; font-weight: 700; }
    .similar-ad-location { color: #718096; font-size: 0.85rem; }
    .modal-content { background: #ffffff; border: 1px solid rgba(0,0,0,0.1); border-radius: 20px; }
    .modal-header { border-bottom: 1px solid rgba(0,0,0,0.1); }
    .modal-title { color: #2d3748; }
    .form-control-dark { background: #f7fafc; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px; color: #2d3748; padding: 12px 15px; }
    .form-control-dark:focus { background: #ffffff; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237,0.2); color: #2d3748; }
    .form-label-light { color: #4a5568; }
    .btn-close-white { filter: none; }

    /* =========================================
       MOBILE RESPONSIVE
       ========================================= */
    @media (max-width: 992px) {
        .ad-detail-container { padding: 20px 12px; }
        .ad-content-section { padding: 25px 20px; }
        .ad-title { font-size: 1.5rem; }
        .ad-price { font-size: 1.6rem; }
        .ad-image-section { min-height: 250px; }
        .ad-image-section i { font-size: 60px; }
        .seller-card { padding: 20px; }
        .similar-ads { margin-top: 30px; }
    }

    @media (max-width: 768px) {
        .ad-detail-container { padding: 16px 10px; }
        .ad-main-card { border-radius: 16px; }
        .ad-image-section { min-height: 200px; }
        .ad-image-section i { font-size: 50px; }
        .badge-type { top: 12px; left: 12px; padding: 7px 14px; font-size: 0.82rem; }
        .ad-content-section { padding: 20px 16px; }
        .ad-title { font-size: 1.3rem; margin-bottom: 12px; }
        .ad-price { font-size: 1.4rem; margin-bottom: 20px; }
        .ad-meta { gap: 10px; margin-bottom: 20px; }
        .ad-meta-item { font-size: 0.85rem; }
        .ad-meta-item i { width: 28px; height: 28px; font-size: 0.8rem; }
        .ad-description { padding: 14px; font-size: 0.9rem; line-height: 1.7; margin-bottom: 20px; }
        .seller-card { border-radius: 14px; padding: 18px; }
        .seller-avatar { width: 56px; height: 56px; border-radius: 14px; margin-bottom: 12px; }
        .seller-avatar i { font-size: 22px; }
        .seller-name { font-size: 1.05rem; }
        .seller-since { font-size: 0.82rem; }
        .seller-stat-value { font-size: 1.1rem; }
        .seller-stat-label { font-size: 0.75rem; }
        .btn-contact { padding: 10px; font-size: 0.9rem; border-radius: 10px; }
        .similar-ad-card { padding: 12px; border-radius: 10px; }
        .similar-ad-title { font-size: 0.88rem; }
        .modal-content { border-radius: 14px; }
    }

    @media (max-width: 576px) {
        .ad-detail-container { padding: 10px 6px; }
        .ad-main-card { border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
        .ad-image-section { min-height: 180px; }
        .badge-type { top: 8px; left: 8px; padding: 6px 12px; font-size: 0.75rem; border-radius: 20px; }
        .ad-content-section { padding: 16px 14px; }
        .ad-title { font-size: 1.15rem; }
        .ad-price { font-size: 1.25rem; }
        .ad-meta { gap: 8px; flex-direction: column; }
        .ad-meta-item { font-size: 0.82rem; }
        .ad-description { padding: 12px; font-size: 0.85rem; line-height: 1.6; border-radius: 10px; }
        .seller-card { padding: 14px; border-radius: 12px; }
        .seller-avatar { width: 50px; height: 50px; border-radius: 12px; }
        .seller-stats { flex-wrap: wrap; gap: 8px; padding: 10px 0; }
        .seller-stat { min-width: 60px; }
        .btn-contact { font-size: 0.85rem; padding: 10px; }
        .similar-ads h3 { font-size: 1.1rem; }
    }

    @media (max-width: 420px) {
        .ad-image-section { min-height: 160px; }
        .ad-content-section { padding: 14px 12px; }
        .ad-title { font-size: 1.05rem; }
        .ad-price { font-size: 1.15rem; }
        .ad-meta-item i { width: 24px; height: 24px; font-size: 0.75rem; margin-right: 6px; }
        .seller-avatar { width: 44px; height: 44px; }
        .seller-avatar i { font-size: 18px; }
    }
</style>
@endpush

@section('content')
    <div class="ad-detail-container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0" style="background: transparent; font-size: 0.9rem;">
                <li class="breadcrumb-item"><a href="{{ route('homepage') }}" style="color: #718096;" class="text-decoration-none">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ads.index') }}" style="color: #718096;" class="text-decoration-none">Annonces</a></li>
                <li class="breadcrumb-item active" style="color: #2d3748;">{{ Str::limit($ad->title, 25) }}</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="ad-main-card">
                    <div class="ad-image-section">
                        <span class="badge-type {{ $ad->service_type == 'offre' ? 'badge-offre' : 'badge-demande' }}">
                            <i class="fas {{ $ad->service_type == 'offre' ? 'fa-hand-holding' : 'fa-search' }} me-1"></i>
                            {{ $ad->service_type == 'offre' ? 'Offre' : 'Demande' }}
                        </span>
                        @php
                            $photos = $ad->photos ?? [];
                            if (is_string($photos)) {
                                $decoded = json_decode($photos, true);
                                $photos = is_array($decoded) ? $decoded : [];
                            }
                            $photos = array_filter($photos);
                            $photoCount = count($photos);
                        @endphp
                        
                        @if($photoCount > 0)
                            @if($photoCount === 1)
                                <img src="{{ storage_url($photos[0]) }}" alt="Photo" id="main-photo" style="cursor: pointer;" onclick="openLightbox(0)">
                            @else
                                <div class="photo-gallery" style="display: grid; grid-template-columns: 2fr 1fr; gap: 4px; width: 100%; height: 100%;">
                                    <div class="main-photo" style="cursor: pointer;" onclick="openLightbox(0)">
                                        <img src="{{ storage_url($photos[0]) }}" alt="Photo 1" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="side-photos" style="display: flex; flex-direction: column; gap: 4px;">
                                        @foreach(array_slice($photos, 1, 2) as $index => $photo)
                                        <div class="side-photo" style="flex: 1; position: relative; cursor: pointer;" onclick="openLightbox({{ $index + 1 }})">
                                            <img src="{{ storage_url($photo) }}" alt="Photo {{ $index + 2 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @if($index === 1 && $photoCount > 3)
                                            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;">
                                                +{{ $photoCount - 3 }}
                                            </div>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <i class="fas fa-image"></i>
                        @endif
                    </div>
                    
                    <!-- Lightbox Modal -->
                    @if($photoCount > 0)
                    <div id="lightbox-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 9999; align-items: center; justify-content: center;">
                        <button onclick="closeLightbox()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 2rem; cursor: pointer; z-index: 10001;">
                            <i class="fas fa-times"></i>
                        </button>
                        <button onclick="prevPhoto()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: white; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 1.2rem;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <img id="lightbox-image" src="" alt="Photo" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                        <button onclick="nextPhoto()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: white; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 1.2rem;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; font-size: 1rem;">
                            <span id="lightbox-counter">1 / {{ $photoCount }}</span>
                        </div>
                    </div>
                    
                    <script>
                        const photos = @json(array_map(fn($p) => storage_url($p), $photos));
                        let currentPhotoIndex = 0;
                        
                        function openLightbox(index) {
                            currentPhotoIndex = index;
                            document.getElementById('lightbox-image').src = photos[index];
                            document.getElementById('lightbox-counter').textContent = (index + 1) + ' / ' + photos.length;
                            document.getElementById('lightbox-modal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                        
                        function closeLightbox() {
                            document.getElementById('lightbox-modal').style.display = 'none';
                            document.body.style.overflow = '';
                        }
                        
                        function prevPhoto() {
                            currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length;
                            document.getElementById('lightbox-image').src = photos[currentPhotoIndex];
                            document.getElementById('lightbox-counter').textContent = (currentPhotoIndex + 1) + ' / ' + photos.length;
                        }
                        
                        function nextPhoto() {
                            currentPhotoIndex = (currentPhotoIndex + 1) % photos.length;
                            document.getElementById('lightbox-image').src = photos[currentPhotoIndex];
                            document.getElementById('lightbox-counter').textContent = (currentPhotoIndex + 1) + ' / ' + photos.length;
                        }
                        
                        document.addEventListener('keydown', function(e) {
                            if (document.getElementById('lightbox-modal').style.display === 'flex') {
                                if (e.key === 'Escape') closeLightbox();
                                if (e.key === 'ArrowLeft') prevPhoto();
                                if (e.key === 'ArrowRight') nextPhoto();
                            }
                        });
                    </script>
                    @endif
                    
                    <div class="ad-content-section">
                        <span class="badge bg-primary mb-2">{{ $ad->category }}</span>
                        <h1 class="ad-title">{{ $ad->title }}</h1>
                        <div class="ad-meta">
                            <div class="ad-meta-item"><i class="fas fa-map-marker-alt"></i><span>{{ $ad->location }}</span></div>
                            <div class="ad-meta-item"><i class="fas fa-calendar"></i><span>{{ $ad->created_at->diffForHumans() }}</span></div>
                            @if($ad->radius_km)<div class="ad-meta-item"><i class="fas fa-bullseye"></i><span>{{ $ad->radius_km }} km</span></div>@endif
                        </div>
                        <div class="ad-price">@if($ad->price){{ number_format($ad->price, 2, ',', ' ') }} €/h @else Prix à discuter @endif</div>
                        <h6 style="color: #2d3748;" class="mb-2"><i class="fas fa-align-left me-2 text-primary"></i>Description</h6>
                        <div class="ad-description">{!! nl2br(e($ad->description)) !!}</div>
                        <div class="d-flex gap-2 flex-wrap">
                            {{-- Reply restriction badge --}}
                            @if(($ad->reply_restriction ?? 'everyone') === 'pro_only')
                                <span class="badge px-3 py-2" style="background: #dbeafe; color: #2563eb; font-size: 0.78rem;">
                                    <i class="fas fa-briefcase me-1"></i>Réponses PRO uniquement
                                </span>
                            @elseif(($ad->reply_restriction ?? 'everyone') === 'verified_only')
                                <span class="badge px-3 py-2" style="background: #d1fae5; color: #059669; font-size: 0.78rem;">
                                    <i class="fas fa-check-circle me-1"></i>Réponses profils vérifiés uniquement
                                </span>
                            @endif
                            @if(!Auth::check() || Auth::id() !== $ad->user_id)
                            @auth
                                <button
                                    class="btn btn-outline-warning btn-sm"
                                    id="toggleSaveBtn"
                                    data-ad-id="{{ $ad->id }}"
                                    data-saved="{{ $isSaved ? '1' : '0' }}"
                                >
                                    <i class="fas fa-bookmark me-1"></i>
                                    <span>{{ $isSaved ? 'Sauvegardée' : 'Sauvegarder' }}</span>
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-bookmark me-1"></i>Sauvegarder
                                </a>
                            @endauth
                            @endif
                            <button class="btn btn-outline-info btn-sm" id="shareAdBtn">
                                <i class="fas fa-share-alt me-1"></i>Partager
                            </button>
                            @if(!Auth::check() || Auth::id() !== $ad->user_id)
                            @auth
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal">
                                    <i class="fas fa-flag me-1"></i>Signaler
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-flag me-1"></i>Signaler
                                </a>
                            @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="seller-card">
                    <div class="seller-avatar"><i class="fas fa-user"></i></div>
                    <h4 class="seller-name">
                        <a href="{{ route('profile.public', $ad->user_id) }}" class="text-decoration-none text-dark">
                            {{ $ad->user->name ?? 'Utilisateur' }}
                        </a>
                    </h4>
                    @if($ad->user && $ad->user->is_verified)
                        <div class="text-center"><span class="badge bg-success mb-2" style="font-size: 0.72rem;"><i class="fas fa-check-circle me-1"></i>Profil vérifié</span></div>
                    @else
                        <div class="text-center"><span class="badge bg-secondary mb-2" style="font-size: 0.72rem; opacity: 0.8;"><i class="fas fa-user-times me-1"></i>Profil non vérifié</span></div>
                    @endif
                    <p class="seller-since"><i class="fas fa-clock me-1"></i>Membre depuis {{ optional($ad->user)->created_at ? $ad->user->created_at->format('M Y') : 'N/A' }}</p>
                    <div class="seller-stats">
                        <div class="seller-stat"><div class="seller-stat-value">{{ $ad->user ? $ad->user->ads()->count() : 0 }}</div><div class="seller-stat-label">Annonces</div></div>
                        <div class="seller-stat"><div class="seller-stat-value">{{ optional($ad->user)->available_points ?? 0 }}</div><div class="seller-stat-label">Points</div></div>
                    </div>
                    @auth
                        @if(Auth::id() !== $ad->user_id)
                            @php
                                $restriction = $ad->reply_restriction ?? 'everyone';
                                $canReply = true;
                                $restrictionMsg = '';

                                if ($restriction === 'pro_only') {
                                    $isPro = Auth::user()->user_type === 'professionnel'
                                          || Auth::user()->hasActiveProSubscription()
                                          || Auth::user()->hasCompletedProOnboarding();
                                    if (!$isPro) {
                                        $canReply = false;
                                        $restrictionMsg = 'Cette annonce est réservée aux professionnels.';
                                    }
                                } elseif ($restriction === 'verified_only') {
                                    if (!Auth::user()->is_verified) {
                                        $canReply = false;
                                        $restrictionMsg = 'Cette annonce est réservée aux profils vérifiés.';
                                    }
                                }
                            @endphp

                            @if($canReply)
                                <button class="btn btn-contact btn-contact-primary" data-bs-toggle="modal" data-bs-target="#contactModal"><i class="fas fa-paper-plane me-2"></i>Contacter</button>
                                <button class="btn btn-contact" style="background: linear-gradient(135deg, #0f766e, #0f766e); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#secureOrderModal">
                                    <i class="fas fa-shield-alt me-2"></i>Commande securisee
                                </button>
                                <button class="btn btn-contact" id="btnCandidatureShow" onclick="toggleCandidatureShowForm()" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none;">
                                    <i class="fas fa-hand-paper me-2"></i>Envoyer ma candidature
                                </button>
                                <div id="candidatureShowForm" style="display:none; margin-bottom: 10px;">
                                    <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                                        <h6 style="font-size: 0.88rem; font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                                            <i class="fas fa-paper-plane" style="color: #3b82f6; margin-right: 6px;"></i>Envoyer votre candidature
                                        </h6>
                                        <textarea id="candidatureShowMessage" class="form-control mb-2" rows="3" placeholder="Présentez-vous en quelques mots... (optionnel)" maxlength="1000" style="font-size: 0.85rem; border-radius: 8px;"></textarea>
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCandidatureShowForm()">Annuler</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="btnSendCandidatureShow" onclick="submitCandidatureShow()">
                                                <i class="fas fa-paper-plane me-1"></i>Envoyer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mb-2 p-3 rounded-3 text-center" style="background: #fef3c7; border: 1px solid #fcd34d;">
                                    <i class="fas fa-lock me-1" style="color: #d97706;"></i>
                                    <span style="color: #92400e; font-size: 0.85rem; font-weight: 600;">{{ $restrictionMsg }}</span>
                                    @if($restriction === 'pro_only')
                                        <div class="mt-2">
                                            <a href="{{ route('pro.dashboard') }}" class="btn btn-sm btn-warning" style="font-size: 0.8rem;">
                                                <i class="fas fa-crown me-1"></i>Devenir Pro
                                            </a>
                                        </div>
                                    @elseif($restriction === 'verified_only')
                                        <div class="mt-2">
                                            <a href="{{ route('verification.index') }}" class="btn btn-sm btn-success" style="font-size: 0.8rem;">
                                                <i class="fas fa-shield-alt me-1"></i>Vérifier mon profil
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                <button class="btn btn-contact btn-contact-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
                                    <i class="fas fa-lock me-2"></i>Contacter
                                </button>
                            @endif
                            <a href="{{ route('profile.public', $ad->user_id) }}" class="btn btn-contact btn-contact-secondary"><i class="fas fa-user me-2"></i>Voir le profil</a>
                        @else
                            <a href="{{ route('ads.edit', $ad) }}" class="btn btn-contact btn-contact-primary"><i class="fas fa-edit me-2"></i>Modifier</a>
                            <form action="{{ route('ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-contact mt-2" style="background: #fee2e2; color: #dc2626; border: 2px solid #fecaca; width: 100%;">
                                    <i class="fas fa-trash-alt me-2"></i>Supprimer l'annonce
                                </button>
                            </form>
                            
                            <!-- Smart Boost/Urgent Status for Owner -->
                            @php $bStatus = $ad->getBoostStatus(); @endphp
                            
                            @if($bStatus['has_any_visibility'])
                                <div class="mt-2 p-3 rounded-3" style="background: linear-gradient(135deg, #fefce8, #fef3c7); border: 2px solid #fcd34d;">
                                    {{-- Urgent active --}}
                                    @if($bStatus['is_urgent'])
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fas fa-fire" style="color: #ef4444;"></i>
                                        <div>
                                            <strong style="color: #92400e; font-size: 0.9rem;">Urgent actif</strong>
                                            <div style="font-size: 0.78rem; color: #a16207;">
                                                @if($bStatus['is_permanent_urgent'])
                                                    Permanent
                                                @else
                                                    {{ $bStatus['urgent_days_left'] }}j restants — expire le {{ $bStatus['urgent_until']->format('d/m') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Boost active --}}
                                    @if($bStatus['is_boosted'])
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fas fa-rocket" style="color: #f59e0b;"></i>
                                        <div>
                                            <strong style="color: #92400e; font-size: 0.9rem;">Boost actif</strong>
                                            <div style="font-size: 0.78rem; color: #a16207;">
                                                {{ $bStatus['boost_days_left'] }}j restants — expire le {{ $bStatus['boost_end']->format('d/m') }}
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Expiring soon warning --}}
                                    @if($bStatus['is_expiring_soon'])
                                    <div class="p-2 rounded-2 mb-2" style="background: #fee2e2; border: 1px solid #fca5a5;">
                                        <small style="color: #dc2626;"><i class="fas fa-exclamation-triangle me-1"></i>Expire bientôt ! Prolongez votre visibilité.</small>
                                    </div>
                                    @endif
                                    
                                    <a href="{{ route('boost.show', $ad) }}" class="btn btn-sm btn-warning w-100 mt-1" style="font-weight: 600;">
                                        <i class="fas fa-plus me-1"></i>
                                        @if($bStatus['is_expiring_soon'])
                                            Prolonger maintenant
                                        @else
                                            Gérer le boost
                                        @endif
                                    </a>
                                </div>
                            @else
                                <a href="{{ route('boost.show', $ad) }}" class="btn btn-contact mt-2" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none;">
                                    <i class="fas fa-rocket me-2"></i>Booster cette annonce
                                </a>
                            @endif
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-contact btn-contact-primary"><i class="fas fa-sign-in-alt me-2"></i>Connectez-vous</a>
                        <p class="text-center mt-2 small" style="color: #718096;"><i class="fas fa-gift me-1"></i>5 points offerts à l'inscription</p>
                        <a href="{{ route('profile.public', $ad->user_id) }}" class="btn btn-contact btn-contact-secondary"><i class="fas fa-user me-2"></i>Voir le profil</a>
                    @endauth
                    <div class="mt-3 p-2 rounded" style="background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.3);">
                        <small class="text-warning"><i class="fas fa-shield-alt me-1"></i>Conseils: Rencontrez en lieu public</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="similar-ads">
            <h3><i class="fas fa-lightbulb me-2 text-warning"></i>Annonces similaires</h3>
            <div class="row g-3">
                @php $similarAds = \App\Models\Ad::where('category', $ad->category)->where('id', '!=', $ad->id)->where('status', 'active')->limit(4)->get(); @endphp
                @forelse($similarAds as $similar)
                <div class="col-6 col-lg-3">
                    <a href="{{ route('ads.show', $similar) }}" class="text-decoration-none">
                        <div class="similar-ad-card">
                            <div class="similar-ad-title">{{ Str::limit($similar->title, 30) }}</div>
                            <div class="similar-ad-price">@if($similar->price){{ number_format($similar->price, 2, ',', ' ') }} €/h @else À discuter @endif</div>
                            <div class="similar-ad-location"><i class="fas fa-map-marker-alt me-1"></i>{{ Str::limit($similar->location, 15) }}</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-muted">Aucune annonce similaire.</p>
                @endforelse
            </div>
        </div>

        <!-- Section Commentaires -->
        <div class="comments-section mt-4" id="comments">
            <div class="ad-main-card" style="padding: 25px;">
                <h3 style="color: #2d3748; margin-bottom: 20px;">
                    <i class="fas fa-comments me-2 text-primary"></i>
                    Commentaires 
                    <span class="badge bg-primary" id="comments-count">{{ $ad->comments()->count() }}</span>
                </h3>

                <!-- Formulaire d'ajout de commentaire -->
                @auth
                <form id="comment-form" class="mb-4">
                    @csrf
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            @if(Auth::user()->avatar)
                                <img src="{{ storage_url(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" 
                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                            @else
                                <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <textarea name="content" id="comment-content" class="form-control form-control-dark" 
                                      rows="3" placeholder="Écrivez un commentaire..." 
                                      style="resize: none; border-radius: 12px;"></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted"><i class="fas fa-coins me-1"></i>-2 points par commentaire</small>
                                <button type="submit" class="btn btn-primary btn-sm" id="submit-comment" disabled>
                                    <i class="fas fa-paper-plane me-1"></i>Publier
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @else
                <div class="alert alert-info mb-4" style="border-radius: 12px;">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <a href="{{ route('login') }}" class="text-decoration-none">Connectez-vous</a> pour laisser un commentaire.
                </div>
                @endauth

                <!-- Liste des commentaires -->
                <div id="comments-list">
                    @php 
                        $comments = $ad->comments()->whereNull('parent_id')->with(['user', 'replies.user'])->orderBy('created_at', 'desc')->get();
                    @endphp
                    
                    @forelse($comments as $comment)
                    <div class="comment-item mb-3" data-comment-id="{{ $comment->id }}" style="padding: 15px; background: #f7fafc; border-radius: 12px;">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0">
                                @if($comment->user && $comment->user->avatar)
                                    <img src="{{ storage_url($comment->user->avatar) }}" alt="{{ $comment->user->name }}" 
                                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                @else
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem;">
                                        {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <a href="{{ route('profile.public', $comment->user_id) }}" class="fw-bold text-dark text-decoration-none">
                                            {{ $comment->user->name ?? 'Utilisateur' }}
                                        </a>
                                        <span class="text-muted small ms-2">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    @auth
                                        @if(Auth::id() === $comment->user_id || Auth::user()->role === 'admin')
                                        <button class="btn btn-link text-danger btn-sm p-0 delete-comment" data-comment-id="{{ $comment->id }}" title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @endif
                                    @endauth
                                </div>
                                <p class="mb-0 mt-2" style="color: #4a5568; line-height: 1.6;">{{ $comment->content }}</p>
                                
                                <!-- Réponses -->
                                @if($comment->replies->count() > 0)
                                <div class="replies mt-3 ms-4 pt-3" style="border-left: 2px solid #e2e8f0; padding-left: 15px;">
                                    @foreach($comment->replies as $reply)
                                    <div class="reply-item mb-2" data-comment-id="{{ $reply->id }}">
                                        <div class="d-flex gap-2">
                                            <div class="flex-shrink-0">
                                                @if($reply->user && $reply->user->avatar)
                                                    <img src="{{ storage_url($reply->user->avatar) }}" alt="{{ $reply->user->name }}" 
                                                         style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                                @else
                                                    <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.75rem;">
                                                        {{ strtoupper(substr($reply->user->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="{{ route('profile.public', $reply->user_id) }}" class="fw-bold text-dark text-decoration-none small">
                                                    {{ $reply->user->name ?? 'Utilisateur' }}
                                                </a>
                                                <span class="text-muted small ms-1">{{ $reply->created_at->diffForHumans() }}</span>
                                                <p class="mb-0 small" style="color: #4a5568;">{{ $reply->content }}</p>
                                            </div>
                                            @auth
                                                @if(Auth::id() === $reply->user_id || Auth::user()->role === 'admin')
                                                <button class="btn btn-link text-danger btn-sm p-0 delete-comment" data-comment-id="{{ $reply->id }}" title="Supprimer">
                                                    <i class="fas fa-trash-alt small"></i>
                                                </button>
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4" id="no-comments">
                        <i class="far fa-comment-dots fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun commentaire pour le moment.<br>Soyez le premier à commenter !</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @auth
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2 text-primary"></i>Contacter {{ $ad->user->name ?? 'l\'annonceur' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('messages.create.conversation') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="recipient_id" value="{{ $ad->user_id }}">
                        <input type="hidden" name="ad_id" value="{{ $ad->id }}">
                        <div class="mb-3">
                            <label class="form-label-light">Votre message</label>
                            <textarea name="message" class="form-control form-control-dark" rows="5" required>Bonjour,

Je suis intéressé(e) par votre annonce "{{ $ad->title }}".

Cordialement,
{{ Auth::user()->name }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endauth

    @auth
    @if(Auth::id() !== $ad->user_id)
    <div class="modal fade" id="secureOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2 text-success"></i>Lancer une commande securisee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('service-orders.store', $ad) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-success" style="border-radius: 12px;">
                            <strong>Paiement securise</strong><br>
                            Une commission ProxiPro de 10% est previsualisee. Le paiement securise sera branche a l'etape suivante.
                        </div>
                        <div class="mb-3">
                            <label class="form-label-light">Montant convenu</label>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control form-control-dark" value="{{ $ad->price ? number_format((float) $ad->price, 2, '.', '') : '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-light">Date souhaitee</label>
                            <input type="date" name="scheduled_for" class="form-control form-control-dark" min="{{ now()->toDateString() }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-light">Message</label>
                            <textarea name="message" class="form-control form-control-dark" rows="4" placeholder="Precisez le besoin, le perimetre et les attentes.">Bonjour,

Je souhaite lancer une commande securisee pour votre annonce "{{ $ad->title }}".

Cordialement,
{{ Auth::user()->name }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-lock me-1"></i>Envoyer la commande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endauth

    @auth
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="fas fa-flag me-2 text-danger"></i>Signaler l'annonce</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('ads.report', $ad) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label-light">Motif</label>
                            <select name="reason" class="form-control form-control-dark" required>
                                <option value="Contenu inapproprié">Contenu inapproprié</option>
                                <option value="Arnaque ou fraude">Arnaque ou fraude</option>
                                <option value="Spam">Spam</option>
                                <option value="Violation des règles">Violation des règles</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-light">Message (optionnel)</label>
                            <textarea name="message" class="form-control form-control-dark" rows="4" placeholder="Décrivez le problème..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-flag me-1"></i>Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endauth
@endsection

@section('scripts')
<script>
    (function () {
        const shareBtn = document.getElementById('shareAdBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', async () => {
                const url = window.location.href;
                const title = @json($ad->title);
                try {
                    if (navigator.share) {
                        await navigator.share({ title, url });
                    } else if (navigator.clipboard) {
                        await navigator.clipboard.writeText(url);
                        alert('Lien copié dans le presse-papiers.');
                    } else {
                        prompt('Copiez ce lien :', url);
                    }
                } catch (e) {
                    console.error(e);
                }
            });
        }

        const toggleSaveBtn = document.getElementById('toggleSaveBtn');
        if (toggleSaveBtn) {
            toggleSaveBtn.addEventListener('click', async () => {
                const adId = toggleSaveBtn.dataset.adId;
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                try {
                    const res = await fetch(`/ads/${adId}/toggle-save`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        toggleSaveBtn.dataset.saved = data.saved ? '1' : '0';
                        toggleSaveBtn.innerHTML = `<i class="fas fa-bookmark me-1"></i><span>${data.saved ? 'Sauvegardée' : 'Sauvegarder'}</span>`;
                    }
                } catch (e) {
                    console.error(e);
                }
            });
        }

        // ===== COMMENTS FUNCTIONALITY =====
        const commentForm = document.getElementById('comment-form');
        const commentContent = document.getElementById('comment-content');
        const submitBtn = document.getElementById('submit-comment');
        const commentsList = document.getElementById('comments-list');
        const commentsCount = document.getElementById('comments-count');
        const noComments = document.getElementById('no-comments');
        const adId = @json($ad->id);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Enable/disable submit button based on content
        if (commentContent) {
            commentContent.addEventListener('input', function() {
                submitBtn.disabled = this.value.trim().length === 0;
            });
        }

        // Submit comment
        if (commentForm) {
            commentForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const content = commentContent.value.trim();
                if (!content) return;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi...';
                
                try {
                    const response = await fetch(`/ads/${adId}/comments`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ content: content })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Hide "no comments" message
                        if (noComments) noComments.style.display = 'none';
                        
                        // Add new comment to the list
                        const comment = data.comment;
                        const avatarHtml = comment.user.avatar 
                            ? `<img src="${comment.user.avatar}" alt="${comment.user.name}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">`
                            : `<div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem;">${comment.user.initial}</div>`;
                        
                        const commentHtml = `
                            <div class="comment-item mb-3" data-comment-id="${comment.id}" style="padding: 15px; background: #f7fafc; border-radius: 12px; animation: fadeIn 0.3s ease;">
                                <div class="d-flex gap-3">
                                    <div class="flex-shrink-0">${avatarHtml}</div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <a href="/profile/${comment.user.id}" class="fw-bold text-dark text-decoration-none">${comment.user.name}</a>
                                                <span class="text-muted small ms-2">${comment.created_at}</span>
                                            </div>
                                            <button class="btn btn-link text-danger btn-sm p-0 delete-comment" data-comment-id="${comment.id}" title="Supprimer">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                        <p class="mb-0 mt-2" style="color: #4a5568; line-height: 1.6;">${comment.content}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        commentsList.insertAdjacentHTML('afterbegin', commentHtml);
                        
                        // Update count
                        const currentCount = parseInt(commentsCount.textContent) || 0;
                        commentsCount.textContent = currentCount + 1;
                        
                        // Clear form
                        commentContent.value = '';
                        submitBtn.disabled = true;
                        
                        // Show success toast
                        alert(data.message || 'Commentaire ajouté !');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue. Veuillez réessayer.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Publier';
                }
            });
        }

        // Delete comment
        document.addEventListener('click', async function(e) {
            if (e.target.closest('.delete-comment')) {
                const btn = e.target.closest('.delete-comment');
                const commentId = btn.dataset.commentId;
                
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) return;
                
                // Disable button while processing
                btn.disabled = true;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                try {
                    const response = await fetch(`/comments/${commentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        if (response.status === 419) {
                            alert('Session expirée. Veuillez rafraîchir la page et réessayer.');
                            window.location.reload();
                            return;
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        const commentItem = document.querySelector(`.comment-item[data-comment-id="${commentId}"], .reply-item[data-comment-id="${commentId}"]`);
                        if (commentItem) {
                            // Fade out animation
                            commentItem.style.transition = 'opacity 0.3s, transform 0.3s';
                            commentItem.style.opacity = '0';
                            commentItem.style.transform = 'translateX(-20px)';
                            
                            setTimeout(() => {
                                commentItem.remove();
                                
                                // Update count
                                const currentCount = parseInt(commentsCount.textContent) || 0;
                                commentsCount.textContent = Math.max(0, currentCount - 1);
                                
                                // Show "no comments" if empty
                                if (commentsList.querySelectorAll('.comment-item').length === 0) {
                                    commentsList.innerHTML = `
                                        <div class="text-center py-4" id="no-comments">
                                            <i class="far fa-comment-dots fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Aucun commentaire pour le moment.<br>Soyez le premier à commenter !</p>
                                        </div>
                                    `;
                                }
                            }, 300);
                        }
                    } else {
                        alert(data.message || 'Erreur lors de la suppression du commentaire.');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression. Veuillez réessayer.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        });

        // Scroll to comments if hash
        if (window.location.hash === '#comments') {
            setTimeout(() => {
                document.getElementById('comments')?.scrollIntoView({ behavior: 'smooth' });
            }, 300);
        }
    })();

    // Candidature functions for show page
    function toggleCandidatureShowForm() {
        const form = document.getElementById('candidatureShowForm');
        const btn = document.getElementById('btnCandidatureShow');
        if (!form) return;
        if (form.style.display === 'none') {
            form.style.display = 'block';
            if (btn) btn.style.display = 'none';
        } else {
            form.style.display = 'none';
            if (btn) btn.style.display = 'block';
        }
    }

    async function submitCandidatureShow() {
        const btn = document.getElementById('btnSendCandidatureShow');
        const msgInput = document.getElementById('candidatureShowMessage');
        const message = msgInput?.value?.trim() || '';
        const adId = {{ $ad->id }};

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(`/ads/${adId}/candidature`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            if (data.success) {
                const form = document.getElementById('candidatureShowForm');
                if (form) {
                    form.innerHTML = `
                        <div style="padding:14px;background:#ecfdf5;border:1px solid #86efac;border-radius:12px;text-align:center;">
                            <i class="fas fa-check-circle" style="color:#059669;font-size:1.2rem;margin-bottom:4px;"></i>
                            <div style="font-weight:600;color:#065f46;font-size:0.9rem;">Candidature envoyée !</div>
                            <div style="font-size:0.78rem;color:#047857;">L'annonceur a été notifié.</div>
                        </div>
                    `;
                }
            } else {
                alert(data.message || 'Erreur lors de l\'envoi');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
            }
        } catch (error) {
            console.error('submitCandidatureShow error:', error);
            alert('Erreur lors de l\'envoi de la candidature');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
        }
    }
</script>
<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection
