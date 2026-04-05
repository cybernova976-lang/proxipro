{{-- Modal Devenir Prestataire --}}
<div class="modal fade" id="becomeProviderModal" tabindex="-1" aria-labelledby="becomeProviderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content provider-modal-content">
            <!-- Header -->
            <div class="modal-header provider-modal-header">
                <div class="provider-modal-title-wrapper">
                    <div class="provider-modal-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="becomeProviderModalLabel">
                            @if(Auth::user()->is_service_provider)
                                Gérer mes services
                            @else
                                Devenir Prestataire
                            @endif
                        </h5>
                        <p class="provider-modal-subtitle">
                            @if(Auth::user()->is_service_provider)
                                Modifiez vos compétences et domaines d'expertise
                            @else
                                Proposez vos services et apparaissez dans les recherches
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body provider-modal-body">
                <!-- Step Indicator -->
                <div class="provider-steps" id="providerSteps">
                    <div class="provider-step active" data-step="1">
                        <div class="step-number">1</div>
                        <span class="step-label">Catégories</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="provider-step" data-step="2">
                        <div class="step-number">2</div>
                        <span class="step-label">Détails</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="provider-step" data-step="3">
                        <div class="step-number">3</div>
                        <span class="step-label">Abonnement</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="provider-step" data-step="4">
                        <div class="step-number">4</div>
                        <span class="step-label">Récapitulatif</span>
                    </div>
                </div>

                <!-- Step 1: Sélection des catégories -->
                <div class="provider-step-content active" id="step1Content">
                    <h6 class="step-title">
                        <i class="fas fa-th-large"></i>
                        Sélectionnez vos domaines d'expertise
                    </h6>
                    <p class="step-description">
                        Choisissez les catégories correspondant à vos compétences. 
                        Vous pouvez sélectionner plusieurs catégories si vous êtes polyvalent.
                    </p>
                    
                    <div class="categories-grid" id="categoriesGrid">
                        <!-- Chargé dynamiquement via JS -->
                        <div class="loading-categories">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Chargement des catégories...</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Sélection des sous-catégories et détails -->
                <div class="provider-step-content" id="step2Content">
                    <h6 class="step-title">
                        <i class="fas fa-list-check"></i>
                        Précisez vos services
                    </h6>
                    <p class="step-description">
                        Pour chaque catégorie sélectionnée, choisissez les services spécifiques que vous proposez.
                    </p>
                    
                    <div class="selected-categories-detail" id="selectedCategoriesDetail">
                        <!-- Généré dynamiquement -->
                    </div>
                </div>

                <!-- Step 3: Abonnement -->
                <div class="provider-step-content" id="step3Content">
                    <h6 class="step-title">
                        <i class="fas fa-crown"></i>
                        Choisissez votre abonnement
                    </h6>
                    <p class="step-description">
                        Accédez à tous les outils professionnels pour développer votre activité.
                    </p>

                    <div class="subscription-plans-grid">
                        <!-- Plan Mensuel -->
                        <div class="plan-card" data-plan="monthly" onclick="selectPlan('monthly')">
                            <div class="plan-header-label">Mensuel</div>
                            <div class="plan-price">9,99€</div>
                            <div class="plan-period">/mois</div>
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Profil professionnel vérifié</li>
                                <li><i class="fas fa-check"></i> Devis & factures illimités</li>
                                <li><i class="fas fa-check"></i> Gestion de clientèle</li>
                                <li><i class="fas fa-check"></i> Badge Pro visible</li>
                                <li><i class="fas fa-check"></i> Jusqu'à 4 photos par annonce</li>
                                <li><i class="fas fa-check"></i> Notifications en temps réel</li>
                                <li><i class="fas fa-check"></i> Support prioritaire</li>
                            </ul>
                        </div>

                        <!-- Plan Annuel -->
                        <div class="plan-card plan-recommended" data-plan="annual" onclick="selectPlan('annual')">
                            <div class="plan-badge">RECOMMANDÉ · -30%</div>
                            <div class="plan-header-label">Annuel</div>
                            <div class="plan-price">85€</div>
                            <div class="plan-period">/an <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.78rem;">119,88€</span></div>
                            <div style="font-size: 0.78rem; color: #059669; font-weight: 600; margin-top: 2px;">soit 7,08€/mois</div>
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Tout le plan mensuel inclus</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Statistiques avancées</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Position prioritaire</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Badge « Pro Premium »</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Jusqu'à 4 photos par annonce</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Export comptable</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Support dédié 7j/7</li>
                            </ul>
                        </div>
                    </div>

                    <div class="skip-subscription-wrapper" style="text-align:center;margin-top:16px;">
                        <button type="button" class="btn btn-outline-secondary" onclick="skipSubscription()" style="font-size:0.92rem;padding:10px 28px;border-radius:10px;border:2px solid #cbd5e1;font-weight:600;">
                            <i class="fas fa-forward me-1"></i> Continuer sans abonnement
                        </button>
                        <p class="skip-note" style="font-size:0.8rem;color:#94a3b8;margin-top:8px;">Vous pourrez souscrire à tout moment depuis votre espace pro.</p>
                    </div>
                </div>

                <!-- Step 4: Récapitulatif et confirmation -->
                <div class="provider-step-content" id="step4Content">
                    <h6 class="step-title">
                        <i class="fas fa-check-double"></i>
                        Récapitulatif
                    </h6>
                    <p class="step-description">
                        Vérifiez vos informations personnelles et vos services avant de valider.
                    </p>
                    
                    <div class="services-summary" id="servicesSummary">
                        <!-- Généré dynamiquement -->
                    </div>
                    
                    <div class="alert" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #2563eb; margin-top: 2px; flex-shrink: 0;"></i>
                        <div style="font-size: 0.88rem; color: #1e40af;">
                            <strong>Note :</strong> Ces informations s'ajouteront à votre profil existant. Vos catégories et services déjà enregistrés ne seront pas supprimés. Vous pouvez les modifier à tout moment depuis votre profil.
                        </div>
                    </div>

                    <div class="provider-benefits">
                        <h6><i class="fas fa-gift"></i> Avantages du statut Prestataire</h6>
                        <ul>
                            <li><i class="fas fa-check text-success"></i> Apparaître dans les recherches de professionnels</li>
                            <li><i class="fas fa-check text-success"></i> Recevoir des demandes de service directes</li>
                            <li><i class="fas fa-check text-success"></i> Badge "Prestataire" sur votre profil</li>
                            <li><i class="fas fa-check text-success"></i> Accès aux outils de gestion de services</li>
                        </ul>
                    </div>
                </div>

                <!-- Messages d'erreur -->
                <div class="provider-error-message" id="providerErrorMessage" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="providerErrorText"></span>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer provider-modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                
                <div class="footer-nav-buttons">
                    <button type="button" class="btn btn-secondary" id="btnPrevStep" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" class="btn btn-primary" id="btnNextStep">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="btnSubmitProvider" style="display: none;">
                        <i class="fas fa-check"></i> Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== MODAL DEVENIR PRESTATAIRE ===== */
.provider-modal-content {
    border: none;
    border-radius: 20px;
    overflow: hidden;
}

.provider-modal-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 24px;
    border: none;
}

.provider-modal-title-wrapper {
    display: flex;
    align-items: center;
    gap: 16px;
}

.provider-modal-icon {
    width: 56px;
    height: 56px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.provider-modal-header .modal-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
}

.provider-modal-subtitle {
    margin: 4px 0 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

.provider-modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.provider-modal-body {
    padding: 24px;
    background: #f8fafc;
    max-height: 60vh;
    overflow-y: auto;
}

/* Steps Indicator */
.provider-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 30px;
    padding: 16px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.provider-step {
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0.5;
    transition: all 0.3s;
}

.provider-step.active {
    opacity: 1;
}

.provider-step.completed {
    opacity: 1;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.provider-step.active .step-number {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.provider-step.completed .step-number {
    background: #10b981;
    color: white;
}

.provider-step.completed .step-number::after {
    content: '✓';
    font-size: 0.8rem;
}

.step-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
}

.step-connector {
    width: 40px;
    height: 2px;
    background: #e2e8f0;
}

/* Step Content */
.provider-step-content {
    display: none;
}

.provider-step-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.step-title i {
    color: #10b981;
}

.step-description {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 24px;
}

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.category-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.category-card:hover {
    border-color: #10b981;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.category-card.selected {
    border-color: #10b981;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
}

.category-card.selected .category-check {
    display: flex;
}

.category-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 10px;
    color: white;
}

.category-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1e293b;
    margin-bottom: 4px;
}

.category-count {
    font-size: 0.75rem;
    color: #64748b;
}

.category-check {
    display: none;
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}

.category-card {
    position: relative;
}

/* Selected Categories Detail (Step 2) */
.selected-categories-detail {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.category-detail-section {
    background: white;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.category-detail-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.category-detail-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
}

.category-detail-title {
    font-weight: 700;
    font-size: 1rem;
    color: #1e293b;
    margin: 0;
}

.subcategories-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.subcategory-chip {
    padding: 8px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.subcategory-chip:hover {
    border-color: #10b981;
    color: #10b981;
}

.subcategory-chip.selected {
    border-color: #10b981;
    background: #10b981;
    color: white;
}

/* Experience input for each subcategory */
.subcategory-experience {
    display: none;
    margin-top: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
}

.subcategory-chip.selected + .subcategory-experience {
    display: block;
}

.experience-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    padding: 8px 12px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.experience-row label {
    font-size: 0.85rem;
    color: #475569;
    white-space: nowrap;
}

.experience-row input[type="number"] {
    width: 70px;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
}

.experience-row textarea {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.85rem;
    resize: none;
    min-height: 60px;
}

/* Services Summary (Step 3) */
.services-summary {
    background: white;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 20px;
}

.summary-service-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.summary-service-item:last-child {
    border-bottom: none;
}

.summary-service-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
    flex-shrink: 0;
}

.summary-service-info h6 {
    font-weight: 600;
    font-size: 0.95rem;
    color: #1e293b;
    margin: 0 0 4px;
}

.summary-service-info p {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
}

.summary-service-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 10px;
    background: #e2e8f0;
    color: #475569;
    margin-left: auto;
}

/* Provider Benefits */
.provider-benefits {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border-radius: 14px;
    padding: 20px;
    border: 1px solid #a7f3d0;
}

.provider-benefits h6 {
    font-weight: 700;
    color: #166534;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.provider-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.provider-benefits li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 0.9rem;
    color: #166534;
}

/* Subscription Plans */
.subscription-plans-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.plan-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px 18px;
    cursor: pointer;
    transition: all 0.25s;
    text-align: center;
    position: relative;
}

.plan-card:hover {
    border-color: #10b981;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15);
}

.plan-card.selected {
    border-color: #10b981;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.plan-recommended {
    border-color: #6366f1;
}

.plan-recommended:hover {
    border-color: #6366f1;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
}

.plan-recommended.selected {
    border-color: #6366f1;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.plan-header-label {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.plan-price {
    font-size: 2.2rem;
    font-weight: 800;
    color: #10b981;
    line-height: 1;
}

.plan-recommended .plan-price {
    color: #6366f1;
}

.plan-period {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 16px;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.plan-features li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 0.82rem;
    color: #475569;
}

.plan-features li i {
    color: #10b981;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.skip-subscription-wrapper {
    text-align: center;
    margin-top: 8px;
}

.skip-subscription-wrapper .btn-link {
    font-size: 0.85rem;
    text-decoration: none;
}

.skip-subscription-wrapper .skip-note {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 4px;
}

@media (max-width: 576px) {
    .subscription-plans-grid {
        grid-template-columns: 1fr;
    }
}

/* Error Message */
.provider-error-message {
    background: #fef2f2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 10px;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

/* Footer */
.provider-modal-footer {
    padding: 16px 24px;
    background: white;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.footer-nav-buttons {
    display: flex;
    gap: 10px;
}

.provider-modal-footer .btn {
    padding: 10px 20px;
    font-weight: 600;
    border-radius: 10px;
}

.provider-modal-footer .btn-primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
}

.provider-modal-footer .btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
}

/* Loading */
.loading-categories {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.loading-categories i {
    font-size: 2rem;
    color: #10b981;
    margin-bottom: 12px;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .provider-steps {
        gap: 4px;
    }
    
    .step-label {
        display: none;
    }
    
    .step-connector {
        width: 20px;
    }
    
    .provider-modal-body {
        padding: 16px;
    }
}
</style>

<script>
(function() {
    // State
    let currentStep = 1;
    let categoriesData = {};
    let selectedCategories = {};
    let selectedServices = [];
    let selectedPlan = null;
    
    // User info for recap
    const currentUser = @json(Auth::check() ? Auth::user()->only(['name', 'email', 'phone', 'city', 'country', 'address']) : []);
    
    // DOM Elements
    const modal = document.getElementById('becomeProviderModal');
    if (!modal) return;
    
    const stepsIndicator = document.getElementById('providerSteps');
    const step1Content = document.getElementById('step1Content');
    const step2Content = document.getElementById('step2Content');
    const step3Content = document.getElementById('step3Content');
    const step4Content = document.getElementById('step4Content');
    const categoriesGrid = document.getElementById('categoriesGrid');
    const selectedCategoriesDetail = document.getElementById('selectedCategoriesDetail');
    const servicesSummary = document.getElementById('servicesSummary');
    const btnPrev = document.getElementById('btnPrevStep');
    const btnNext = document.getElementById('btnNextStep');
    const btnSubmit = document.getElementById('btnSubmitProvider');
    const errorMessage = document.getElementById('providerErrorMessage');
    const errorText = document.getElementById('providerErrorText');
    
    // Load categories when modal opens
    modal.addEventListener('show.bs.modal', loadCategories);
    
    // Navigation buttons
    btnNext.addEventListener('click', nextStep);
    btnPrev.addEventListener('click', prevStep);
    btnSubmit.addEventListener('click', submitProvider);
    
    async function loadCategories() {
        categoriesGrid.innerHTML = '<div class="loading-categories"><i class="fas fa-spinner fa-spin"></i><span>Chargement des catégories...</span></div>';
        
        try {
            const response = await fetch('{{ route("service-provider.categories") }}');
            const data = await response.json();
            
            if (data.success) {
                categoriesData = data.categories;
                renderCategories();
                
                // Load existing services if user is already a provider
                await loadExistingServices();
            }
        } catch (error) {
            console.error('Error loading categories:', error);
            categoriesGrid.innerHTML = '<div class="loading-categories"><i class="fas fa-exclamation-triangle text-danger"></i><span>Erreur de chargement</span></div>';
        }
    }
    
    async function loadExistingServices() {
        try {
            const response = await fetch('{{ route("service-provider.my-services") }}');
            const data = await response.json();
            
            if (data.success && data.services && data.services.length > 0) {
                // Pre-select existing services
                data.services.forEach(service => {
                    if (!selectedCategories[service.main_category]) {
                        selectedCategories[service.main_category] = [];
                    }
                    selectedCategories[service.main_category].push({
                        subcategory: service.subcategory,
                        experience_years: service.experience_years,
                        description: service.description
                    });
                    
                    // Mark category card as selected
                    const categoryCard = document.querySelector(`[data-category="${service.main_category}"]`);
                    if (categoryCard) {
                        categoryCard.classList.add('selected');
                    }
                });
            }
        } catch (error) {
            console.error('Error loading existing services:', error);
        }
    }
    
    function renderCategories() {
        let html = '';
        
        for (const [name, data] of Object.entries(categoriesData)) {
            const isSelected = selectedCategories[name] ? 'selected' : '';
            const safeName = name.replace(/'/g, "\\'");
            html += `
                <div class="category-card ${isSelected}" data-category="${name}" onclick="toggleCategory('${safeName}')">
                    <div class="category-check"><i class="fas fa-check"></i></div>
                    <div class="category-icon" style="background: ${data.color};">
                        <i class="${data.icon}"></i>
                    </div>
                    <div class="category-name">${name}</div>
                    <div class="category-count">${data.subcategories.length} services</div>
                </div>
            `;
        }
        
        categoriesGrid.innerHTML = html;
    }
    
    // Global function for onclick
    window.toggleCategory = function(categoryName) {
        const card = document.querySelector(`[data-category="${categoryName}"]`);
        
        if (selectedCategories[categoryName]) {
            delete selectedCategories[categoryName];
            card.classList.remove('selected');
        } else {
            selectedCategories[categoryName] = [];
            card.classList.add('selected');
        }
        
        hideError();
    };
    
    function renderSubcategoriesStep() {
        let html = '';
        
        for (const categoryName of Object.keys(selectedCategories)) {
            const categoryData = categoriesData[categoryName];
            if (!categoryData) continue;
            
            html += `
                <div class="category-detail-section">
                    <div class="category-detail-header">
                        <div class="category-detail-icon" style="background: ${categoryData.color};">
                            <i class="${categoryData.icon}"></i>
                        </div>
                        <h6 class="category-detail-title">${categoryName}</h6>
                    </div>
                    <div class="subcategories-grid">
            `;
            
            categoryData.subcategories.forEach(sub => {
                const existingService = selectedCategories[categoryName].find(s => s.subcategory === sub);
                const isSelected = existingService ? 'selected' : '';
                const safeCatName = categoryName.replace(/'/g, "\\'");
                const safeSub = sub.replace(/'/g, "\\'");
                
                html += `
                    <div class="subcategory-chip ${isSelected}" 
                         data-category="${categoryName}" 
                         data-subcategory="${sub}"
                         onclick="toggleSubcategory('${safeCatName}', '${safeSub}')">
                        ${sub}
                    </div>
                `;
            });
            
            html += '</div></div>';
        }
        
        if (html === '') {
            html = '<div class="text-center text-muted py-4">Aucune catégorie sélectionnée</div>';
        }
        
        selectedCategoriesDetail.innerHTML = html;
    }
    
    window.toggleSubcategory = function(categoryName, subcategory) {
        const chip = document.querySelector(`[data-category="${categoryName}"][data-subcategory="${subcategory}"]`);
        const existingIndex = selectedCategories[categoryName].findIndex(s => s.subcategory === subcategory);
        
        if (existingIndex >= 0) {
            selectedCategories[categoryName].splice(existingIndex, 1);
            chip.classList.remove('selected');
        } else {
            selectedCategories[categoryName].push({
                subcategory: subcategory,
                experience_years: 0,
                description: ''
            });
            chip.classList.add('selected');
        }
        
        hideError();
    };
    
    function renderSummary() {
        let html = '';
        let totalServices = 0;
        
        // Personal info recap
        html += `
            <div style="margin-bottom: 20px; padding: 16px; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius: 12px; border: 1px solid #7dd3fc;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <i class="fas fa-user-circle" style="color: #0284c7; font-size: 1.3rem;"></i>
                    <strong style="color: #0c4a6e; font-size: 0.95rem;">Vos informations personnelles</strong>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.88rem;">
                    <div><span style="color: #64748b;">Nom :</span> <strong style="color: #1e293b;">${currentUser.name || '—'}</strong></div>
                    <div><span style="color: #64748b;">Email :</span> <strong style="color: #1e293b;">${currentUser.email || '—'}</strong></div>
                    <div><span style="color: #64748b;">Téléphone :</span> <strong style="color: #1e293b;">${currentUser.phone || '—'}</strong></div>
                    <div><span style="color: #64748b;">Ville :</span> <strong style="color: #1e293b;">${currentUser.city || '—'}</strong></div>
                    ${currentUser.country ? `<div><span style="color: #64748b;">Pays :</span> <strong style="color: #1e293b;">${currentUser.country}</strong></div>` : ''}
                    ${currentUser.address ? `<div style="grid-column: span 2;"><span style="color: #64748b;">Adresse :</span> <strong style="color: #1e293b;">${currentUser.address}</strong></div>` : ''}
                </div>
            </div>
        `;
        
        for (const [categoryName, services] of Object.entries(selectedCategories)) {
            const categoryData = categoriesData[categoryName];
            if (!categoryData || services.length === 0) continue;
            
            services.forEach(service => {
                totalServices++;
                html += `
                    <div class="summary-service-item">
                        <div class="summary-service-icon" style="background: ${categoryData.color};">
                            <i class="${categoryData.icon}"></i>
                        </div>
                        <div class="summary-service-info">
                            <h6>${service.subcategory}</h6>
                            <p>${categoryName}</p>
                        </div>
                    </div>
                `;
            });
        }
        
        if (totalServices === 0) {
            html = '<div class="text-center text-muted py-4">Aucun service sélectionné</div>';
        } else {
            html = `<div class="mb-3"><strong>${totalServices} service(s)</strong> sélectionné(s)</div>` + html;
        }
        
        // Afficher l'abonnement choisi
        if (selectedPlan) {
            const planLabel = selectedPlan === 'annual' ? 'Annuel (85€/an)' : 'Mensuel (9,99€/mois)';
            html += `
                <div style="margin-top: 16px; padding: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.06), rgba(5,150,105,0.06)); border-radius: 12px; border: 1px solid rgba(16,185,129,0.2);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-crown" style="color: #059669; font-size: 1.2rem;"></i>
                        <div>
                            <strong style="color: #166534;">Abonnement ${planLabel}</strong>
                            <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Accès complet aux outils professionnels</p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div style="margin-top: 16px; padding: 14px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #64748b;"></i>
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Sans abonnement — Vous pourrez souscrire plus tard depuis votre espace pro.</p>
                    </div>
                </div>
            `;
        }
        
        servicesSummary.innerHTML = html;
    }
    
    function nextStep() {
        hideError();
        
        if (currentStep === 1) {
            // Validate at least one category selected
            if (Object.keys(selectedCategories).length === 0) {
                showError('Veuillez sélectionner au moins une catégorie.');
                return;
            }
            goToStep(2);
            renderSubcategoriesStep();
        } else if (currentStep === 2) {
            // Validate at least one subcategory selected
            let hasServices = false;
            for (const services of Object.values(selectedCategories)) {
                if (services.length > 0) {
                    hasServices = true;
                    break;
                }
            }
            if (!hasServices) {
                showError('Veuillez sélectionner au moins un service.');
                return;
            }
            goToStep(3);
        } else if (currentStep === 3) {
            goToStep(4);
            renderSummary();
        }
    }
    
    function prevStep() {
        if (currentStep > 1) {
            goToStep(currentStep - 1);
        }
    }
    
    function goToStep(step) {
        currentStep = step;
        
        // Update step indicators
        document.querySelectorAll('#providerSteps .provider-step').forEach(el => {
            const stepNum = parseInt(el.dataset.step);
            el.classList.remove('active', 'completed');
            if (stepNum < step) el.classList.add('completed');
            if (stepNum === step) el.classList.add('active');
        });
        
        // Show/hide content
        step1Content.classList.toggle('active', step === 1);
        step2Content.classList.toggle('active', step === 2);
        step3Content.classList.toggle('active', step === 3);
        step4Content.classList.toggle('active', step === 4);
        
        // Show/hide buttons
        btnPrev.style.display = step > 1 ? 'inline-flex' : 'none';
        btnNext.style.display = step < 4 ? 'inline-flex' : 'none';
        btnSubmit.style.display = step === 4 ? 'inline-flex' : 'none';
    }
    
    async function submitProvider() {
        hideError();
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        
        // Build services array
        const services = [];
        for (const [categoryName, categoryServices] of Object.entries(selectedCategories)) {
            categoryServices.forEach(service => {
                services.push({
                    main_category: categoryName,
                    subcategory: service.subcategory,
                    experience_years: service.experience_years || 0,
                    description: service.description || ''
                });
            });
        }
        
        const bodyData = { services };
        if (selectedPlan) {
            bodyData.plan = selectedPlan;
        }
        
        try {
            const response = await fetch('{{ route("service-provider.register") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(bodyData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Si paiement Stripe requis, rediriger vers la page de paiement
                if (data.requires_payment && data.checkout_url) {
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirection vers le paiement...';
                    window.location.href = data.checkout_url;
                    return;
                }
                showSuccessStep(data);
            } else {
                showError(data.message || 'Une erreur est survenue.');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Une erreur est survenue. Veuillez réessayer.');
        }
        
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-check"></i> Confirmer';
    }
    
    // Sélection du plan d'abonnement
    window.selectPlan = function(plan) {
        selectedPlan = plan;
        document.querySelectorAll('#step3Content .plan-card').forEach(c => c.classList.remove('selected'));
        if (plan) {
            const card = document.querySelector(`#step3Content [data-plan="${plan}"]`);
            if (card) card.classList.add('selected');
        }
        hideError();
    };

    // Continuer sans abonnement — avancer directement à l'étape suivante
    window.skipSubscription = function() {
        selectedPlan = null;
        document.querySelectorAll('#step3Content .plan-card').forEach(c => c.classList.remove('selected'));
        hideError();
        goToStep(4);
        renderSummary();
    };
    
    function showSuccessStep(result) {
        // Cacher toutes les étapes et indicateurs
        document.querySelectorAll('.provider-step-content').forEach(c => c.classList.remove('active'));
        document.getElementById('providerSteps').style.display = 'none';
        
        // Cacher les boutons de navigation
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
        btnSubmit.style.display = 'none';
        
        // Cacher le bouton Annuler
        const cancelBtn = modal.querySelector('.modal-footer .btn-outline-secondary');
        if (cancelBtn) cancelBtn.style.display = 'none';
        
        let successDiv = document.getElementById('providerSuccessStep');
        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.id = 'providerSuccessStep';
            successDiv.className = 'provider-step-content active';
            
            let subscriptionInfo = '';
            if (result.has_subscription) {
                const planLabel = result.plan === 'annual' ? 'Annuel (85€/an)' : 'Mensuel (9,99€/mois)';
                subscriptionInfo = `
                    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(5,150,105,0.08)); border: 1px solid rgba(16,185,129,0.2); border-radius: 12px; padding: 14px; margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-crown" style="color: #059669; margin-right: 6px;"></i>
                        <strong style="color: #166534;">Abonnement ${planLabel} activé !</strong>
                    </div>
                `;
            }
            
            successDiv.innerHTML = `
                <div style="text-align: center; padding: 1rem 0;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; animation: scaleIn 0.5s ease;">
                        <i class="fas fa-check" style="color: white; font-size: 2rem;"></i>
                    </div>
                    <h5 style="color: #1f2937; font-weight: 700; margin-bottom: 0.5rem;">Félicitations ! 🎉</h5>
                    <p style="color: #6b7280; margin-bottom: 1.5rem;">Vos nouvelles informations ont été ajoutées à votre profil. Les catégories et services précédents ont été conservés. Vous pouvez les gérer depuis votre profil.</p>
                    
                    ${subscriptionInfo}
                    
                    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08)); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem; flex-shrink: 0;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h6 style="margin: 0; color: #4f46e5; font-weight: 600;">Obtenez votre badge vérifié</h6>
                                <p style="margin: 0; font-size: 0.85rem; color: #6b7280;">Renforcez la confiance de vos clients avec un profil vérifié</p>
                            </div>
                        </div>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="padding: 0.25rem 0; font-size: 0.85rem; color: #4b5563;"><i class="fas fa-check" style="color: #10b981; margin-right: 0.5rem;"></i> Badge "Profil vérifié" visible</li>
                            <li style="padding: 0.25rem 0; font-size: 0.85rem; color: #4b5563;"><i class="fas fa-check" style="color: #10b981; margin-right: 0.5rem;"></i> Plus de visibilité dans les résultats</li>
                            <li style="padding: 0.25rem 0; font-size: 0.85rem; color: #4b5563;"><i class="fas fa-check" style="color: #10b981; margin-right: 0.5rem;"></i> Annonces illimitées</li>
                        </ul>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: center;">
                        <button type="button" class="btn btn-outline-secondary" onclick="providerCloseAndReload()" style="border-radius: 10px; padding: 0.625rem 1.25rem;">
                            <i class="fas fa-home"></i> Plus tard
                        </button>
                        <button type="button" class="btn btn-primary" onclick="providerGoToVerification()" style="border-radius: 10px; padding: 0.625rem 1.25rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;">
                            <i class="fas fa-shield-alt"></i> Vérifier mon profil
                        </button>
                    </div>
                </div>
            `;
            modal.querySelector('.provider-modal-body').appendChild(successDiv);
        } else {
            successDiv.classList.add('active');
        }
    }
    
    // Fermer et recharger
    window.providerCloseAndReload = function() {
        bootstrap.Modal.getInstance(modal).hide();
        window.location.reload();
    };
    
    // Aller à la vérification
    window.providerGoToVerification = function() {
        bootstrap.Modal.getInstance(modal).hide();
        window.location.href = '{{ route("verification.index") }}';
    };
    
    function showError(message) {
        errorText.textContent = message;
        errorMessage.style.display = 'flex';
    }
    
    function hideError() {
        errorMessage.style.display = 'none';
    }
    
    // Reset state when modal closes
    modal.addEventListener('hidden.bs.modal', function() {
        currentStep = 1;
        selectedCategories = {};
        selectedPlan = null;
        hideError();
        
        // Reset success step
        const successDiv = document.getElementById('providerSuccessStep');
        if (successDiv) successDiv.remove();
        
        // Restore step indicators
        document.getElementById('providerSteps').style.display = 'flex';
        
        // Restore cancel button
        const cancelBtn = modal.querySelector('.modal-footer .btn-outline-secondary');
        if (cancelBtn) cancelBtn.style.display = '';
        
        // Reset plan selection UI
        document.querySelectorAll('#step3Content .plan-card').forEach(c => c.classList.remove('selected'));
        
        goToStep(1);
    });
})();
</script>

<style>
/* Success Toast */
.provider-success-toast {
    position: fixed;
    top: 80px;
    right: 20px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 16px 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    z-index: 9999;
    transform: translateX(120%);
    transition: transform 0.3s ease;
}

.provider-success-toast.show {
    transform: translateX(0);
}

.provider-success-toast i {
    font-size: 1.3rem;
}

@keyframes scaleIn {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
