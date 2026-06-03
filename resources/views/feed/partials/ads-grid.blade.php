{{-- Grille des annonces pour AJAX --}}
@forelse($ads as $ad)
    <div class="mission-card" data-ad-id="{{ $ad->id }}">
        <!-- En-tête avec avatar et infos utilisateur -->
        <div class="mission-card-header">
            <a href="{{ $ad->user ? route('profile.public', $ad->user) : '#' }}" class="mission-user-avatar">
                @if($ad->user?->avatar)
                    <img src="{{ storage_url($ad->user->avatar) }}" alt="{{ $ad->user->name }}">
                @else
                    <div class="mission-user-avatar-placeholder">{{ strtoupper(substr($ad->user?->name ?? 'U', 0, 1)) }}</div>
                @endif
            </a>
            <div class="mission-user-info">
                <a href="{{ $ad->user ? route('profile.public', $ad->user) : '#' }}" class="mission-user-name text-decoration-none">{{ $ad->user?->name ?? 'Utilisateur' }}</a>
                <div class="mission-meta">
                    <span>{{ $ad->created_at->diffForHumans() }}</span>
                    <span>·</span>
                    <span><i class="fas fa-map-marker-alt me-1"></i>{{ $ad->city ?? $ad->location ?? 'France' }}</span>
                </div>
            </div>
            <!-- Menu 3 points -->
            <div class="dropdown ms-auto">
                <button class="btn-three-dots" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="{{ route('ads.show', $ad) }}"><i class="fas fa-eye me-2 text-primary"></i>Voir les détails</a></li>
                    <li><a class="dropdown-item" href="#" onclick="saveAd({{ $ad->id }}); return false;"><i class="far fa-bookmark me-2 text-warning"></i>Sauvegarder</a></li>
                    <li><a class="dropdown-item" href="{{ $ad->user ? route('profile.public', $ad->user) : '#' }}"><i class="fas fa-user me-2 text-info"></i>Voir le profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal{{ $ad->id }}"><i class="fas fa-flag me-2"></i>Signaler la publication</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-eye-slash me-2 text-muted"></i>Masquer cette annonce</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Contenu de la publication -->
        <div class="mission-card-body">
            <a href="{{ route('ads.show', $ad) }}" class="text-decoration-none">
                <h4 class="mission-title">{{ $ad->title }}</h4>
            </a>
            <p class="mission-description">{{ Str::limit($ad->description, 150) }}</p>
            
            <!-- Badges catégorie et prix -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="mission-badges">
                    <span class="mission-badge mission-badge-category">{{ $ad->category }}</span>
                    @if($ad->is_urgent)
                    <span class="mission-badge mission-badge-urgent"><i class="fas fa-bolt me-1"></i>Urgent</span>
                    @endif
                </div>
                <span class="mission-price">
                    {{ $ad->formatted_price }}
                </span>
            </div>
            
            <!-- Photos -->
            @php
                $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
                $photos = is_array($photos) ? array_filter($photos) : [];
                $photoCount = count($photos);
            @endphp
            
            @if($photoCount > 0)
            <div class="mission-photos {{ $photoCount == 1 ? 'single-photo' : ($photoCount == 2 ? 'two-photos' : '') }}">
                @foreach(array_slice($photos, 0, 2) as $index => $photo)
                @php $photoUrl = storage_url($photo); @endphp
                <div class="mission-photo" onclick="openPhotoLightbox('{{ $photoUrl }}', '{{ addslashes($ad->title) }}')" style="cursor: pointer;">
                    <img src="{{ $photoUrl }}" alt="{{ $ad->title }}" loading="lazy"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="photo-error" style="display:none;width:100%;height:100%;background:#f1f5f9;align-items:center;justify-content:center;color:#94a3b8;">
                        <i class="fas fa-image fa-2x"></i>
                    </div>
                    @if($index == 1 && $photoCount > 2)
                    <div class="mission-photo-more">+{{ $photoCount - 2 }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        
        <!-- Stats (likes, commentaires) -->
        <div class="mission-stats">
            <div class="mission-stats-left">
                <span>
                    <i class="fas fa-heart" style="color: #dc2626;"></i>
                    <span class="likes-number" id="likes-count-{{ $ad->id }}">{{ $ad->likes_count ?? 0 }}</span>
                </span>
            </div>
            <div class="mission-stats-right"></div>
        </div>
        
        <!-- Boutons d'actions sociales - J'aime, Commenter, Partager -->
        <div class="mission-actions">
            <button class="mission-action-btn" id="like-btn-{{ $ad->id }}" onclick="toggleLike({{ $ad->id }})">
                <i class="far fa-heart"></i>
                <span>J'aime</span>
            </button>
            <button class="mission-action-btn" id="comment-btn-{{ $ad->id }}" onclick="toggleComments({{ $ad->id }})">
                <i class="far fa-comment"></i>
                <span><span class="comments-count-{{ $ad->id }}">{{ $ad->comments()->count() }}</span> Commenter</span>
            </button>
            <div class="share-wrapper">
                <button class="mission-action-btn" onclick="toggleShareMenu({{ $ad->id }}, this)">
                    <i class="fas fa-share"></i>
                    <span><span class="shares-count-{{ $ad->id }}">{{ $ad->shares_count ?? 0 }}</span> Partager</span>
                </button>
                <!-- Menu de partage -->
                <div class="share-menu" id="share-menu-{{ $ad->id }}">
                    <div class="share-menu-header">Partager</div>
                    <div class="share-option copy" onclick="copyLink({{ $ad->id }})">
                        <i class="fas fa-link"></i>
                        <span>Copier le lien</span>
                    </div>
                    <div class="share-option twitter" onclick="shareTo('twitter', {{ $ad->id }}, '{{ addslashes($ad->title) }}')">
                        <i class="fab fa-twitter"></i>
                        <span>Twitter</span>
                    </div>
                    <div class="share-option facebook" onclick="shareTo('facebook', {{ $ad->id }})">
                        <i class="fab fa-facebook-f"></i>
                        <span>Facebook</span>
                    </div>
                    <div class="share-option whatsapp" onclick="shareTo('whatsapp', {{ $ad->id }}, '{{ addslashes($ad->title) }}')">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section commentaires (cachée par défaut) -->
        <div class="comments-section" id="comments-section-{{ $ad->id }}" style="display: none;">
            <div class="comments-list" id="comments-list-{{ $ad->id }}">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            @auth
            <form class="comment-form" onsubmit="submitComment(event, {{ $ad->id }})">
                @csrf
                <div class="comment-input-wrapper">
                    <div class="comment-user-avatar">
                        @if(auth()->user()->avatar)
                            <img src="{{ storage_url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <input type="text" class="comment-input" placeholder="Écrire un commentaire..." id="comment-input-{{ $ad->id }}">
                    <button type="submit" class="comment-submit-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
            @endauth
        </div>
        
        <!-- Modal Signaler -->
        <div class="modal fade" id="reportModal{{ $ad->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-flag text-danger me-2"></i>Signaler cette publication</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Pourquoi souhaitez-vous signaler cette publication ?</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-secondary text-start" onclick="submitReport({{ $ad->id }}, 'spam')">
                                <i class="fas fa-ban me-2"></i>Spam ou arnaque
                            </button>
                            <button class="btn btn-outline-secondary text-start" onclick="submitReport({{ $ad->id }}, 'inappropriate')">
                                <i class="fas fa-exclamation-triangle me-2"></i>Contenu inapproprié
                            </button>
                            <button class="btn btn-outline-secondary text-start" onclick="submitReport({{ $ad->id }}, 'fake')">
                                <i class="fas fa-user-secret me-2"></i>Fausse annonce
                            </button>
                            <button class="btn btn-outline-secondary text-start" onclick="submitReport({{ $ad->id }}, 'other')">
                                <i class="fas fa-ellipsis-h me-2"></i>Autre raison
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="empty-state">
        <i class="fas fa-briefcase"></i>
        <h3>Aucune annonce trouvée</h3>
        <p>Essayez de modifier vos critères de recherche</p>
    </div>
@endforelse

{{-- Pagination --}}
@if($ads->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $ads->links() }}
    </div>
@endif
