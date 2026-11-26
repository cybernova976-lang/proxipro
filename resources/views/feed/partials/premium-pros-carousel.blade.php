{{-- Premium Professionals Carousel Component --}}
<section class="premium-pros-section" id="premium-pros-section">
    <div class="w-100">
        <!-- Section Header - Dynamic based on category -->
        <div class="premium-pros-header">
            <h3 class="premium-pros-dynamic-title">
                <i class="fas fa-user-tie"></i>
                <span id="pros-section-title">Réserver un professionnel</span>
            </h3>
        </div>

        <!-- Carousel Container -->
        <div class="premium-carousel-wrapper">
            <!-- Left Arrow -->
            <button class="carousel-nav-btn carousel-prev" onclick="scrollPremiumCarousel(-1)" aria-label="Précédent">
                <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Carousel Track - Will be populated dynamically -->
            <div class="premium-carousel-container" id="premiumCarousel">
                <div class="premium-carousel-track" id="premiumCarouselTrack">
                    @if(isset($premiumPros) && $premiumPros->count() > 0)
                        @foreach($premiumPros as $pro)
                        <a href="{{ route('profile.public', $pro->id) }}" class="pro-card-large">
                            <div class="pro-photo-container">
                                @if($pro->avatar)
                                    <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" class="pro-photo">
                                @else
                                    <div class="pro-photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                                @if($pro->hasActiveProSubscription())
                                <div class="pro-badge-premium">
                                    <i class="fas fa-crown"></i> Premium
                                </div>
                                @endif
                            </div>
                            <div class="pro-card-info">
                                <div class="pro-name-large">{{ Str::limit($pro->name, 18) }}</div>
                                <div class="pro-meta">
                                    @if($pro->bio)
                                        <span class="pro-specialty-text">{{ Str::limit($pro->bio, 30) }}</span>
                                    @endif
                                </div>
                                <div class="pro-rating-row">
                                    <div class="pro-stars">
                                        <i class="fas fa-star"></i>
                                        <span>{{ $pro->reviews_avg_rating ? number_format($pro->reviews_avg_rating, 1) : 'Nouveau' }}</span>
                                    </div>
                                    @if($pro->location_preference)
                                    <div class="pro-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>{{ Str::limit($pro->location_preference, 15) }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                        @endforeach
                    @else
                        <div class="no-pros-message" id="no-pros-default">
                            <i class="fas fa-users-slash"></i>
                            <p>Aucun professionnel disponible pour cette catégorie</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Arrow -->
            <button class="carousel-nav-btn carousel-next" onclick="scrollPremiumCarousel(1)" aria-label="Suivant">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<style>
/* =========================================
   PREMIUM PROFESSIONALS CAROUSEL
   Large Photo Cards - Like Reference Image
   ========================================= */

.premium-pros-section {
    padding: 30px 0;
    background: #f8fafc;
    border-radius: 16px;
    margin-top: 20px;
}

/* Header - Dynamic Title */
.premium-pros-header {
    margin-bottom: 20px;
    padding: 0 10px;
}

.premium-pros-dynamic-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.premium-pros-dynamic-title i {
    color: #4f46e5;
    font-size: 1.1rem;
}

/* Carousel Wrapper */
.premium-carousel-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Navigation Buttons */
.carousel-nav-btn {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid #e2e8f0;
    background: white;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    z-index: 10;
}

.carousel-nav-btn:hover {
    background: #4f46e5;
    border-color: #4f46e5;
    color: white;
}

/* Carousel Container */
.premium-carousel-container {
    flex: 1;
    overflow: hidden;
    position: relative;
}

.premium-carousel-track {
    display: flex;
    flex-wrap: nowrap;
    gap: 16px;
    transition: transform 0.4s ease;
    padding: 5px;
}

/* =========================================
   LARGE PRO CARD - Rectangular Photos
   ========================================= */
.pro-card-large {
    flex: 0 0 180px;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
}

.pro-card-large:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
}

/* Photo Container - Large Rectangle */
.pro-photo-container {
    width: 100%;
    height: 200px;
    position: relative;
    overflow: hidden;
    background: #e2e8f0;
}

.pro-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.pro-card-large:hover .pro-photo {
    transform: scale(1.05);
}

.pro-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4f46e5 0%, #8338ec 100%);
    color: white;
    font-size: 3rem;
}

/* Premium Badge */
.pro-badge-premium {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.pro-badge-premium i {
    font-size: 0.6rem;
}

/* Card Info */
.pro-card-info {
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pro-name-large {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
}

.pro-meta {
    min-height: 18px;
}

.pro-specialty-text {
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.3;
}

.pro-rating-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
}

.pro-stars {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: #1e293b;
    font-weight: 600;
}

.pro-stars i {
    color: #fbbf24;
    font-size: 0.75rem;
}

.pro-location {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #64748b;
}

.pro-location i {
    color: #10b981;
    font-size: 0.65rem;
}

/* No Professionals Message */
.no-pros-message {
    width: 100%;
    padding: 40px 20px;
    text-align: center;
    color: #64748b;
}

.no-pros-message i {
    font-size: 2.5rem;
    margin-bottom: 12px;
    opacity: 0.5;
}

.no-pros-message p {
    margin: 0;
    font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 768px) {
    .pro-card-large {
        flex: 0 0 160px;
    }
    
    .pro-photo-container {
        height: 180px;
    }
    
    .premium-carousel-track {
        gap: 12px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 10px;
    }
    
    .premium-carousel-track::-webkit-scrollbar {
        display: none;
    }
    
    .pro-card-large {
        scroll-snap-align: start;
    }
    
    .carousel-nav-btn {
        display: none;
    }
}

/* Loading State */
.pros-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 40px;
    text-align: center;
    color: #64748b;
}

.pros-loading i {
    font-size: 2rem;
    color: #4f46e5;
    animation: spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
(function() {
    const carousel = document.getElementById('premiumCarousel');
    const track = document.getElementById('premiumCarouselTrack');
    
    if (!carousel || !track) return;
    
    let scrollAmount = 0;
    let cardWidth = 196; // card width + gap
    
    // Calculate card width
    function calculateCardWidth() {
        const firstCard = track.querySelector('.pro-card-large');
        if (firstCard) {
            cardWidth = firstCard.offsetWidth + 16; // Including gap
        }
    }
    
    // Manual scroll
    window.scrollPremiumCarousel = function(direction) {
        calculateCardWidth();
        const containerWidth = carousel.offsetWidth;
        const maxScroll = Math.max(0, track.scrollWidth - containerWidth);
        
        scrollAmount += direction * cardWidth * 2;
        
        // Bounds
        if (scrollAmount < 0) scrollAmount = 0;
        if (scrollAmount > maxScroll) scrollAmount = maxScroll;
        
        track.style.transform = `translateX(-${scrollAmount}px)`;
    };
    
    // Global function to update title based on selected category/subcategory
    window.updateProsSectionTitle = function(categoryName) {
        const titleSpan = document.getElementById('pros-section-title');
        if (titleSpan) {
            if (categoryName && categoryName !== 'all' && categoryName !== 'Toutes les catégories') {
                titleSpan.textContent = 'Réserver un professionnel pour "' + categoryName + '"';
            } else {
                titleSpan.textContent = 'Réserver un professionnel';
            }
        }
    };
    
    // Global function to load professionals by category via AJAX
    window.loadProfessionalsByCategory = function(category, subcategory) {
        const track = document.getElementById('premiumCarouselTrack');
        if (!track) return;
        
        // Show loading
        track.innerHTML = '<div class="pros-loading"><i class="fas fa-spinner"></i><p>Chargement...</p></div>';
        scrollAmount = 0;
        track.style.transform = 'translateX(0)';
        
        // Build URL
        let url = '{{ route("feed.professionals") }}?';
        if (category && category !== 'all') {
            url += 'category=' + encodeURIComponent(category);
        }
        if (subcategory) {
            url += '&subcategory=' + encodeURIComponent(subcategory);
        }
        
        fetch(url)
            .then(response => response.text())
            .then(html => {
                if (html && html.trim() !== '') {
                    track.innerHTML = html;
                } else {
                    const catName = subcategory || category || 'cette catégorie';
                    track.innerHTML = `
                        <div class="no-pros-message">
                            <i class="fas fa-users-slash"></i>
                            <p>Aucun professionnel disponible pour "${catName}"</p>
                        </div>
                    `;
                }
                calculateCardWidth();
            })
            .catch(err => {
                console.error('Error loading professionals:', err);
                track.innerHTML = `
                    <div class="no-pros-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Erreur lors du chargement</p>
                    </div>
                `;
            });
    };
    
    function renderProfessionals(professionals) {
        const track = document.getElementById('premiumCarouselTrack');
        if (!track) return;
        
        let html = '';
        professionals.forEach(pro => {
            const avatarHtml = pro.avatar 
                ? `<img src="${pro.avatar}" alt="${pro.name}" class="pro-photo">`
                : `<div class="pro-photo-placeholder"><i class="fas fa-user"></i></div>`;
            
            const badgeHtml = pro.has_active_pro_subscription 
                ? `<div class="pro-badge-premium"><i class="fas fa-crown"></i> Premium</div>`
                : '';
            
            const bioHtml = pro.bio 
                ? `<span class="pro-specialty-text">${pro.bio.substring(0, 30)}${pro.bio.length > 30 ? '...' : ''}</span>`
                : '';
            
            const locationHtml = pro.location 
                ? `<div class="pro-location"><i class="fas fa-map-marker-alt"></i><span>${pro.location.substring(0, 15)}</span></div>`
                : '';
            
            html += `
                <a href="/user/${pro.id}" class="pro-card-large">
                    <div class="pro-photo-container">
                        ${avatarHtml}
                        ${badgeHtml}
                    </div>
                    <div class="pro-card-info">
                        <div class="pro-name-large">${pro.name.substring(0, 18)}</div>
                        <div class="pro-meta">${bioHtml}</div>
                        <div class="pro-rating-row">
                            <div class="pro-stars">
                                <i class="fas fa-star"></i>
                                <span>${pro.rating || '4.5'}</span>
                            </div>
                            ${locationHtml}
                        </div>
                    </div>
                </a>
            `;
        });
        
        track.innerHTML = html;
        calculateCardWidth();
    }
    
    // Initialize
    calculateCardWidth();
    
    // Recalculate on resize
    window.addEventListener('resize', calculateCardWidth);
})();
</script>
