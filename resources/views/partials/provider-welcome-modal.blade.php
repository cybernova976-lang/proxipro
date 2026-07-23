{{-- Modal de bienvenue pour les nouveaux utilisateurs OAuth --}}
{{-- Propose de devenir prestataire avec les avantages --}}
<div class="modal fade" id="providerWelcomeModal" tabindex="-1" aria-labelledby="providerWelcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content welcome-modal-content">
            <!-- Header -->
            <div class="welcome-modal-header">
                <div class="welcome-confetti">🎉</div>
                <h4 class="welcome-title" id="providerWelcomeModalLabel">
                    Bienvenue sur Lunamars !
                </h4>
                <p class="welcome-subtitle">
                    Votre compte a été créé avec succès. Saviez-vous que vous pouvez aussi proposer vos services ?
                </p>
                <button type="button" class="btn-close btn-close-white welcome-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Body -->
            <div class="welcome-modal-body">
                <div class="welcome-section-title">
                    <i class="fas fa-star text-warning"></i>
                    Devenez prestataire et profitez de nombreux avantages
                </div>

                <div class="welcome-advantages">
                    <div class="advantage-item">
                        <div class="advantage-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="advantage-text">
                            <strong>Publiez plus d'annonces</strong>
                            <span>Jusqu'à 20 annonces actives simultanément</span>
                        </div>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="advantage-text">
                            <strong>Soyez visible</strong>
                            <span>Apparaissez dans l'annuaire des prestataires</span>
                        </div>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="advantage-text">
                            <strong>Badge de confiance</strong>
                            <span>Obtenez un badge vérifié pour rassurer vos clients</span>
                        </div>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="advantage-text">
                            <strong>Plus de points</strong>
                            <span>Gagnez 100 points bonus à l'inscription professionnelle</span>
                        </div>
                    </div>
                </div>

                <div class="welcome-note">
                    <i class="fas fa-info-circle"></i>
                    <span>L'inscription est gratuite et ne prend que 2 minutes !</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="welcome-modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-later" data-bs-dismiss="modal">
                    <i class="fas fa-clock"></i> Plus tard
                </button>
                @if(!Auth::user()->hasCompleteVerificationProfile())
                    <a href="{{ route('profile.edit') }}" class="btn btn-become-pro">
                        <i class="fas fa-user-edit"></i> Compléter mon profil
                    </a>
                @elseif(!Auth::user()->hasVerifiedProfileBadge())
                    <a href="{{ route('verification.index') }}" class="btn btn-become-pro">
                        <i class="fas fa-shield-alt"></i> Vérifier mon profil
                    </a>
                @else
                    <button type="button" class="btn btn-become-pro" id="welcomeBecomeProviderBtn">
                        <i class="fas fa-rocket"></i> Devenir prestataire
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* ===== WELCOME MODAL ===== */
.welcome-modal-content {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}

.welcome-modal-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
    color: white;
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    position: relative;
}

.welcome-confetti {
    font-size: 3rem;
    margin-bottom: 0.75rem;
    animation: welcomeBounce 1s ease-in-out;
}

@keyframes welcomeBounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}

.welcome-title {
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.welcome-subtitle {
    opacity: 0.9;
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.5;
}

.welcome-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.welcome-modal-body {
    padding: 1.5rem 2rem;
    background: #f8fafc;
}

.welcome-section-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.welcome-advantages {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.advantage-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.advantage-item:hover {
    border-color: #6366f1;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1);
}

.advantage-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.advantage-text {
    display: flex;
    flex-direction: column;
}

.advantage-text strong {
    color: #1f2937;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.advantage-text span {
    color: #6b7280;
    font-size: 0.8rem;
}

.welcome-note {
    margin-top: 1.25rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
    border-radius: 10px;
    font-size: 0.85rem;
    color: #4f46e5;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.welcome-modal-footer {
    padding: 1rem 2rem 1.5rem;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.welcome-modal-footer .btn-later {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-color: #d1d5db;
    color: #6b7280;
}

.welcome-modal-footer .btn-later:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
}

.welcome-modal-footer .btn-become-pro {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
    transition: all 0.2s;
    flex: 1;
}

.welcome-modal-footer .btn-become-pro:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
}

@media (max-width: 576px) {
    .welcome-modal-header {
        padding: 1.5rem 1.25rem 1.25rem;
    }
    .welcome-modal-body {
        padding: 1.25rem;
    }
    .welcome-modal-footer {
        padding: 1rem 1.25rem 1.25rem;
        flex-direction: column-reverse;
    }
    .welcome-title {
        font-size: 1.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const welcomeModal = document.getElementById('providerWelcomeModal');
    if (!welcomeModal) return;

    // Afficher automatiquement le modal après un court délai
    setTimeout(function() {
        const modal = new bootstrap.Modal(welcomeModal);
        modal.show();
    }, 1000);

    // Bouton "Devenir prestataire" : fermer le welcome modal et ouvrir le modal d'inscription
    const becomeBtn = document.getElementById('welcomeBecomeProviderBtn');
    if (becomeBtn) {
        becomeBtn.addEventListener('click', function() {
            // Fermer le modal de bienvenue
            const welcomeInstance = bootstrap.Modal.getInstance(welcomeModal);
            if (welcomeInstance) {
                welcomeInstance.hide();
            }

            // Attendre la fermeture puis ouvrir le modal d'inscription professionnel
            welcomeModal.addEventListener('hidden.bs.modal', function openProviderModal() {
                welcomeModal.removeEventListener('hidden.bs.modal', openProviderModal);
                
                // Ouvrir le formulaire commun aux particuliers prestataires
                const providerFormModal = document.getElementById('becomeProviderModal');
                if (providerFormModal) {
                    const providerModal = new bootstrap.Modal(providerFormModal);
                    providerModal.show();
                }
            }, { once: true });
        });
    }
});
</script>
