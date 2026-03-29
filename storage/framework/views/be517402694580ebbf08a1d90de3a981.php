
<div class="modal fade" id="becomeProviderOAuthModal" tabindex="-1" aria-labelledby="becomeProviderOAuthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content oauth-provider-modal">
            <!-- Header -->
            <div class="modal-header oauth-modal-header">
                <div class="oauth-modal-title-wrapper">
                    <div class="oauth-modal-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="becomeProviderOAuthModalLabel">
                            Devenir Prestataire
                        </h5>
                        <p class="oauth-modal-subtitle">
                            Complétez votre profil professionnel en quelques étapes
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body oauth-modal-body">
                <!-- Step Indicator -->
                <div class="oauth-steps" id="oauthProviderSteps">
                    <div class="oauth-step active" data-step="1">
                        <div class="step-circle">1</div>
                        <span class="step-text">Type d'activité</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="oauth-step" data-step="2">
                        <div class="step-circle">2</div>
                        <span class="step-text">Identité</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="oauth-step" data-step="3">
                        <div class="step-circle">3</div>
                        <span class="step-text">Métier</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="oauth-step" data-step="4">
                        <div class="step-circle">4</div>
                        <span class="step-text">Localisation</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="oauth-step" data-step="5">
                        <div class="step-circle">5</div>
                        <span class="step-text">Abonnement</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="oauth-step" data-step="6">
                        <div class="step-circle">6</div>
                        <span class="step-text">Confirmation</span>
                    </div>
                </div>

                <!-- Step 1: Type d'activité -->
                <div class="oauth-step-content active" id="oauthStep1">
                    <div class="step-header">
                        <h6><i class="fas fa-building"></i> Quel est votre type d'activité ?</h6>
                        <p>Choisissez le statut qui correspond à votre situation professionnelle</p>
                    </div>
                    
                    <div class="business-type-grid">
                        <div class="business-type-card" data-type="entreprise">
                            <div class="card-icon">🏢</div>
                            <div class="card-content">
                                <h5>Entreprise</h5>
                                <p>SARL, SAS, EURL, SA...</p>
                                <ul class="card-features">
                                    <li><i class="fas fa-check"></i> Jusqu'à 20 annonces actives</li>
                                    <li><i class="fas fa-check"></i> Badge "Entreprise"</li>
                                    <li><i class="fas fa-check"></i> Annonces illimitées après vérification</li>
                                </ul>
                            </div>
                        </div>
                        <div class="business-type-card" data-type="auto_entrepreneur">
                            <div class="card-icon">👨‍💼</div>
                            <div class="card-content">
                                <h5>Auto-entrepreneur</h5>
                                <p>Micro-entreprise, indépendant</p>
                                <ul class="card-features">
                                    <li><i class="fas fa-check"></i> Jusqu'à 10 annonces actives</li>
                                    <li><i class="fas fa-check"></i> Badge "Auto-entrepreneur"</li>
                                    <li><i class="fas fa-check"></i> Simple et rapide</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="oauth_business_type" value="">
                </div>

                <!-- Step 2: Nom commercial -->
                <div class="oauth-step-content" id="oauthStep2">
                    <div class="step-header">
                        <h6><i class="fas fa-id-card"></i> Votre nom commercial</h6>
                        <p>Ce nom apparaîtra sur votre profil et vos annonces</p>
                    </div>
                    
                    <div class="name-comparison">
                        <div class="current-name-box">
                            <label>Nom actuel sur votre profil</label>
                            <div class="current-name" id="oauthCurrentName"><?php echo e(Auth::user()->name); ?></div>
                        </div>
                        
                        <div class="name-change-toggle">
                            <label class="toggle-container">
                                <input type="checkbox" id="changeNameToggle">
                                <span class="toggle-label">Utiliser un nom commercial différent</span>
                            </label>
                        </div>
                        
                        <div class="new-name-input" id="newNameInputWrapper" style="display: none;">
                            <label for="oauth_company_name">Nouveau nom commercial</label>
                            <input type="text" id="oauth_company_name" class="form-control form-control-lg" 
                                   placeholder="Ex: Jean Services, Ménage Pro, etc.">
                            <small class="text-muted">Ce nom remplacera votre nom actuel sur le profil</small>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Catégorie et métier -->
                <div class="oauth-step-content" id="oauthStep3">
                    <div class="step-header">
                        <h6><i class="fas fa-briefcase"></i> Votre métier / spécialité</h6>
                        <p>Sélectionnez votre domaine d'activité principal</p>
                    </div>
                    
                    <div class="category-selection">
                        <label>Catégorie principale</label>
                        <div class="categories-chips" id="oauthCategoriesChips">
                            <!-- Généré dynamiquement -->
                        </div>
                    </div>
                    
                    <div class="subcategory-selection" id="oauthSubcategoryWrapper" style="display: none;">
                        <label>Votre métier / spécialité</label>
                        <div class="subcategories-list" id="oauthSubcategoriesList">
                            <!-- Généré dynamiquement -->
                        </div>
                    </div>
                    
                    <input type="hidden" id="oauth_category" value="">
                    <input type="hidden" id="oauth_subcategory" value="">
                </div>

                <!-- Step 4: Localisation -->
                <div class="oauth-step-content" id="oauthStep4">
                    <div class="step-header">
                        <h6><i class="fas fa-map-marker-alt"></i> Votre localisation</h6>
                        <p>Indiquez où vous exercez votre activité</p>
                    </div>
                    
                    <div class="location-selects">
                        <div class="form-group">
                            <label for="oauth_country">Pays / Département <span class="text-danger">*</span></label>
                            <select id="oauth_country" class="form-select form-select-lg">
                                <option value="">-- Sélectionner un pays / département --</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="oauth_city">Ville <span class="text-danger">*</span></label>
                            <select id="oauth_city" class="form-select form-select-lg" disabled>
                                <option value="">-- Sélectionnez d'abord un pays --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Abonnement -->
                <div class="oauth-step-content" id="oauthStep5">
                    <div class="step-header">
                        <h6><i class="fas fa-crown"></i> Choisissez votre abonnement</h6>
                        <p>Accédez à tous les outils professionnels pour développer votre activité.</p>
                    </div>

                    <div class="oauth-subscription-plans">
                        <div class="oauth-plan-card" data-plan="monthly" onclick="selectOAuthPlan('monthly')">
                            <div class="oauth-plan-name">Mensuel</div>
                            <div class="oauth-plan-price">9,99€</div>
                            <div class="oauth-plan-period">/mois</div>
                            <ul class="oauth-plan-features">
                                <li><i class="fas fa-check"></i> Profil professionnel vérifié</li>
                                <li><i class="fas fa-check"></i> Devis & factures illimités</li>
                                <li><i class="fas fa-check"></i> Gestion de clientèle</li>
                                <li><i class="fas fa-check"></i> Badge Pro visible</li>
                                <li><i class="fas fa-check"></i> Jusqu'à 4 photos par annonce</li>
                                <li><i class="fas fa-check"></i> Notifications en temps réel</li>
                                <li><i class="fas fa-check"></i> Support prioritaire</li>
                            </ul>
                        </div>

                        <div class="oauth-plan-card oauth-plan-recommended" data-plan="annual" onclick="selectOAuthPlan('annual')">
                            <div class="oauth-plan-badge">RECOMMANDÉ · -30%</div>
                            <div class="oauth-plan-name">Annuel</div>
                            <div class="oauth-plan-price">85€</div>
                            <div class="oauth-plan-period">/an <span style="text-decoration:line-through;color:#94a3b8;font-size:0.78rem;">119,88€</span></div>
                            <div style="font-size:0.78rem;color:#059669;font-weight:600;margin-top:2px;">soit 7,08€/mois</div>
                            <ul class="oauth-plan-features">
                                <li><i class="fas fa-check"></i> Tout le plan mensuel inclus</li>
                                <li><i class="fas fa-star" style="color:#f59e0b!important;"></i> Statistiques avancées</li>
                                <li><i class="fas fa-star" style="color:#f59e0b!important;"></i> Position prioritaire</li>
                                <li><i class="fas fa-star" style="color:#f59e0b!important;"></i> Badge « Pro Premium »</li>
                                <li><i class="fas fa-star" style="color:#f59e0b!important;"></i> Jusqu'à 4 photos par annonce</li>
                                <li><i class="fas fa-star" style="color:#f59e0b!important;"></i> Export comptable</li>
                                <li><i class="fas fa-star" style="color:#f59e0b!important;"></i> Support dédié 7j/7</li>
                            </ul>
                        </div>
                    </div>

                    <div style="text-align:center;margin-top:16px;">
                        <button type="button" class="btn btn-outline-secondary" onclick="skipOAuthSubscription()" style="font-size:0.92rem;padding:10px 28px;border-radius:10px;border:2px solid #cbd5e1;font-weight:600;">
                            <i class="fas fa-forward me-1"></i> Continuer sans abonnement
                        </button>
                        <p style="font-size:0.8rem;color:#94a3b8;margin-top:8px;">Vous pourrez souscrire à tout moment depuis votre espace pro.</p>
                    </div>
                </div>

                <!-- Step 6: Confirmation -->
                <div class="oauth-step-content" id="oauthStep6">
                    <div class="step-header">
                        <h6><i class="fas fa-check-double"></i> Récapitulatif</h6>
                        <p>Vérifiez vos informations avant de valider</p>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-row">
                            <span class="summary-label">Type d'activité</span>
                            <span class="summary-value" id="summaryBusinessType">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Nom commercial</span>
                            <span class="summary-value" id="summaryName">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Métier</span>
                            <span class="summary-value" id="summaryProfession">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Localisation</span>
                            <span class="summary-value" id="summaryLocation">-</span>
                        </div>
                    </div>
                    
                    <div class="verification-notice">
                        <div class="notice-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="notice-content">
                            <h6>Vérification du profil</h6>
                            <p>Après validation, vous pourrez vérifier votre profil pour obtenir un badge de confiance et débloquer plus d'avantages.</p>
                        </div>
                    </div>
                </div>

                <!-- Message d'erreur -->
                <div class="oauth-error-message" id="oauthErrorMessage" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="oauthErrorText"></span>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer oauth-modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                
                <div class="footer-buttons">
                    <button type="button" class="btn btn-secondary" id="oauthBtnPrev" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" class="btn btn-primary" id="oauthBtnNext">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="oauthBtnSubmit" style="display: none;">
                        <i class="fas fa-check"></i> Valider et continuer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== MODAL OAUTH PRESTATAIRE ===== */
.oauth-provider-modal {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}

.oauth-modal-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    padding: 1.5rem;
    border: none;
}

.oauth-modal-title-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.oauth-modal-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.oauth-modal-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.oauth-modal-body {
    padding: 1.5rem;
    background: #f8fafc;
}

/* Steps */
.oauth-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 2rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.oauth-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.oauth-step.active .step-circle {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.oauth-step.completed .step-circle {
    background: #10b981;
    color: white;
}

.step-text {
    font-size: 0.7rem;
    color: #6b7280;
    font-weight: 500;
    white-space: nowrap;
}

.oauth-step.active .step-text {
    color: #6366f1;
    font-weight: 600;
}

.step-line {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    margin: 0 0.5rem;
    max-width: 40px;
}

/* Step Content */
.oauth-step-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.oauth-step-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.step-header h6 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.step-header h6 i {
    color: #6366f1;
    margin-right: 0.5rem;
}

.step-header p {
    color: #6b7280;
    margin: 0;
}

/* Business Type Cards */
.business-type-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.business-type-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.business-type-card:hover {
    border-color: #6366f1;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
}

.business-type-card.selected {
    border-color: #6366f1;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
}

.card-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.card-content h5 {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #1f2937;
}

.card-content p {
    color: #6b7280;
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.card-features {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.card-features li {
    font-size: 0.8rem;
    color: #4b5563;
    padding: 0.25rem 0;
}

.card-features li i {
    color: #10b981;
    margin-right: 0.5rem;
    font-size: 0.7rem;
}

/* Name Section */
.name-comparison {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
}

.current-name-box {
    text-align: center;
    padding: 1rem;
    background: #f3f4f6;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.current-name-box label {
    font-size: 0.8rem;
    color: #6b7280;
    display: block;
    margin-bottom: 0.5rem;
}

.current-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.name-change-toggle {
    margin: 1.5rem 0;
}

.toggle-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.toggle-container input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #6366f1;
}

.toggle-label {
    font-weight: 500;
    color: #374151;
}

.new-name-input {
    margin-top: 1rem;
}

.new-name-input label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #374151;
}

.new-name-input .form-control {
    border-radius: 12px;
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
}

.new-name-input .form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Categories */
.category-selection, .subcategory-selection {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.category-selection label, .subcategory-selection label {
    display: block;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #374151;
}

.categories-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.category-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.category-chip:hover {
    background: #e5e7eb;
}

.category-chip.selected {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border-color: #6366f1;
}

.category-chip .chip-icon {
    font-size: 1.1rem;
}

.subcategories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.subcategory-chip {
    padding: 0.625rem 1rem;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.85rem;
}

.subcategory-chip:hover {
    background: #e5e7eb;
}

.subcategory-chip.selected {
    background: #6366f1;
    color: white;
}

/* Location */
.location-selects {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
}

.location-selects .form-group {
    margin-bottom: 1rem;
}

.location-selects .form-group:last-child {
    margin-bottom: 0;
}

.location-selects label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #374151;
}

.location-selects .form-select {
    border-radius: 12px;
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
}

.location-selects .form-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Summary */
.summary-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    color: #6b7280;
    font-weight: 500;
}

.summary-value {
    color: #1f2937;
    font-weight: 600;
}

.verification-notice {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 12px;
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.notice-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.notice-content h6 {
    margin: 0 0 0.25rem;
    color: #4f46e5;
    font-weight: 600;
}

.notice-content p {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

/* Subscription Plans */
.oauth-subscription-plans {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.oauth-plan-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 16px;
    cursor: pointer;
    transition: all 0.25s;
    text-align: center;
    position: relative;
}

.oauth-plan-card:hover {
    border-color: #6366f1;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
}

.oauth-plan-card.selected {
    border-color: #6366f1;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.oauth-plan-recommended {
    border-color: #8b5cf6;
}

.oauth-plan-badge {
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

.oauth-plan-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}

.oauth-plan-price {
    font-size: 2rem;
    font-weight: 800;
    color: #6366f1;
    line-height: 1;
}

.oauth-plan-period {
    font-size: 0.82rem;
    color: #64748b;
    margin-bottom: 14px;
}

.oauth-plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.oauth-plan-features li {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 4px 0;
    font-size: 0.8rem;
    color: #475569;
}

.oauth-plan-features li i {
    color: #6366f1;
    font-size: 0.72rem;
    flex-shrink: 0;
}

@media (max-width: 576px) {
    .oauth-subscription-plans {
        grid-template-columns: 1fr;
    }
}

/* Error Message */
.oauth-error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 1rem;
    border-radius: 12px;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.oauth-error-message i {
    font-size: 1.25rem;
}

/* Footer */
.oauth-modal-footer {
    padding: 1rem 1.5rem;
    background: white;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
}

.footer-buttons {
    display: flex;
    gap: 0.5rem;
}

.oauth-modal-footer .btn {
    border-radius: 10px;
    padding: 0.625rem 1.25rem;
    font-weight: 500;
}

.oauth-modal-footer .btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
}

.oauth-modal-footer .btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
}

/* Responsive */
@media (max-width: 576px) {
    .business-type-grid {
        grid-template-columns: 1fr;
    }
    
    .oauth-steps {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .step-line {
        display: none;
    }
    
    .step-text {
        font-size: 0.65rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('becomeProviderOAuthModal');
    if (!modal) return;

    let currentStep = 1;
    const totalSteps = 6;
    let formData = {
        business_type: '',
        company_name: '<?php echo e(Auth::user()->name); ?>',
        category: '',
        subcategory: '',
        country: '',
        city: '',
        plan: null
    };
    let categoriesData = {};
    let citiesData = {};

    // Éléments
    const steps = modal.querySelectorAll('.oauth-step');
    const stepContents = modal.querySelectorAll('.oauth-step-content');
    const btnPrev = document.getElementById('oauthBtnPrev');
    const btnNext = document.getElementById('oauthBtnNext');
    const btnSubmit = document.getElementById('oauthBtnSubmit');
    const errorMessage = document.getElementById('oauthErrorMessage');
    const errorText = document.getElementById('oauthErrorText');

    // Charger les données au démarrage
    modal.addEventListener('show.bs.modal', async function() {
        await loadFormData();
    });

    async function loadFormData() {
        try {
            const response = await fetch('<?php echo e(route("become-provider.data")); ?>');
            const data = await response.json();
            
            if (data.success) {
                categoriesData = data.categories;
                citiesData = data.cities;
                
                // Remplir les catégories
                renderCategories();
                
                // Remplir les pays
                renderCountries(data.countries);
            }
        } catch (error) {
            console.error('Erreur chargement données:', error);
        }
    }

    function renderCategories() {
        const container = document.getElementById('oauthCategoriesChips');
        container.innerHTML = '';
        
        for (const [name, cat] of Object.entries(categoriesData)) {
            const chip = document.createElement('div');
            chip.className = 'category-chip';
            chip.dataset.category = name;
            chip.innerHTML = `<span class="chip-icon">${cat.icon}</span><span>${name}</span>`;
            chip.addEventListener('click', () => selectCategory(name, cat));
            container.appendChild(chip);
        }
    }

    function selectCategory(name, cat) {
        // Désélectionner tous
        modal.querySelectorAll('.category-chip').forEach(c => c.classList.remove('selected'));
        // Sélectionner celui-ci
        modal.querySelector(`.category-chip[data-category="${name}"]`).classList.add('selected');
        
        formData.category = name;
        formData.subcategory = '';
        
        // Afficher les sous-catégories
        const wrapper = document.getElementById('oauthSubcategoryWrapper');
        const list = document.getElementById('oauthSubcategoriesList');
        
        list.innerHTML = '';
        cat.subcategories.forEach(sub => {
            const chip = document.createElement('div');
            chip.className = 'subcategory-chip';
            chip.textContent = sub;
            chip.addEventListener('click', () => selectSubcategory(sub));
            list.appendChild(chip);
        });
        
        wrapper.style.display = 'block';
    }

    function selectSubcategory(name) {
        modal.querySelectorAll('.subcategory-chip').forEach(c => c.classList.remove('selected'));
        event.target.classList.add('selected');
        formData.subcategory = name;
    }

    function renderCountries(countries) {
        const select = document.getElementById('oauth_country');
        select.innerHTML = '<option value="">-- Sélectionner un pays / département --</option>';
        
        for (const [name, flag] of Object.entries(countries)) {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = `${flag} ${name}`;
            select.appendChild(option);
        }
    }

    // Event: Changement de pays
    document.getElementById('oauth_country').addEventListener('change', function() {
        const country = this.value;
        formData.country = country;
        formData.city = '';
        
        const citySelect = document.getElementById('oauth_city');
        
        if (country && citiesData[country]) {
            citySelect.disabled = false;
            citySelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
            
            citiesData[country].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        } else {
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">-- Sélectionnez d\'abord un pays --</option>';
        }
    });

    // Event: Changement de ville
    document.getElementById('oauth_city').addEventListener('change', function() {
        formData.city = this.value;
    });

    // Event: Toggle nom commercial
    document.getElementById('changeNameToggle').addEventListener('change', function() {
        const wrapper = document.getElementById('newNameInputWrapper');
        wrapper.style.display = this.checked ? 'block' : 'none';
        
        if (!this.checked) {
            formData.company_name = '<?php echo e(Auth::user()->name); ?>';
            document.getElementById('oauth_company_name').value = '';
        }
    });

    // Event: Changement nom commercial
    document.getElementById('oauth_company_name').addEventListener('input', function() {
        formData.company_name = this.value || '<?php echo e(Auth::user()->name); ?>';
    });

    // Event: Sélection type business
    modal.querySelectorAll('.business-type-card').forEach(card => {
        card.addEventListener('click', function() {
            modal.querySelectorAll('.business-type-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            formData.business_type = this.dataset.type;
        });
    });

    // Navigation
    btnNext.addEventListener('click', () => navigateStep(1));
    btnPrev.addEventListener('click', () => navigateStep(-1));
    btnSubmit.addEventListener('click', submitForm);

    function navigateStep(direction) {
        // Validation avant de passer à l'étape suivante
        if (direction > 0 && !validateCurrentStep()) {
            return;
        }

        hideError();
        currentStep += direction;
        
        if (currentStep < 1) currentStep = 1;
        if (currentStep > totalSteps) currentStep = totalSteps;

        updateStepUI();
        
        // Mettre à jour le récapitulatif si dernière étape
        if (currentStep === totalSteps) {
            updateSummary();
        }
    }

    function validateCurrentStep() {
        switch (currentStep) {
            case 1:
                if (!formData.business_type) {
                    showError('Veuillez sélectionner votre type d\'activité.');
                    return false;
                }
                break;
            case 2:
                if (document.getElementById('changeNameToggle').checked) {
                    const newName = document.getElementById('oauth_company_name').value.trim();
                    if (!newName) {
                        showError('Veuillez saisir votre nom commercial.');
                        return false;
                    }
                    formData.company_name = newName;
                }
                break;
            case 3:
                if (!formData.category) {
                    showError('Veuillez sélectionner une catégorie.');
                    return false;
                }
                if (!formData.subcategory) {
                    showError('Veuillez sélectionner votre métier.');
                    return false;
                }
                break;
            case 4:
                if (!formData.country) {
                    showError('Veuillez sélectionner votre pays.');
                    return false;
                }
                if (!formData.city) {
                    showError('Veuillez sélectionner votre ville.');
                    return false;
                }
                break;
        }
        return true;
    }

    function updateStepUI() {
        // Mise à jour des indicateurs
        steps.forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNum < currentStep) {
                step.classList.add('completed');
            } else if (stepNum === currentStep) {
                step.classList.add('active');
            }
        });

        // Mise à jour du contenu
        stepContents.forEach((content, index) => {
            content.classList.toggle('active', index + 1 === currentStep);
        });

        // Mise à jour des boutons
        btnPrev.style.display = currentStep > 1 ? 'inline-flex' : 'none';
        btnNext.style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
        btnSubmit.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }

    function updateSummary() {
        document.getElementById('summaryBusinessType').textContent = 
            formData.business_type === 'entreprise' ? 'Entreprise' : 'Auto-entrepreneur';
        document.getElementById('summaryName').textContent = formData.company_name;
        document.getElementById('summaryProfession').textContent = formData.subcategory;
        document.getElementById('summaryLocation').textContent = `${formData.city}, ${formData.country}`;
        
        // Afficher l'abonnement choisi
        let planEl = document.getElementById('summaryPlan');
        if (!planEl) {
            const summaryCard = document.querySelector('#oauthStep6 .summary-card');
            if (summaryCard) {
                const row = document.createElement('div');
                row.className = 'summary-row';
                row.innerHTML = '<span class="summary-label">Abonnement</span><span class="summary-value" id="summaryPlan">-</span>';
                summaryCard.appendChild(row);
                planEl = document.getElementById('summaryPlan');
            }
        }
        if (planEl) {
            if (formData.plan === 'annual') {
                planEl.textContent = 'Annuel (85€/an)';
                planEl.style.color = '#6366f1';
            } else if (formData.plan === 'monthly') {
                planEl.textContent = 'Mensuel (9,99€/mois)';
                planEl.style.color = '#10b981';
            } else {
                planEl.textContent = 'Sans abonnement';
                planEl.style.color = '#64748b';
            }
        }
    }
    
    // Sélection du plan dans le modal OAuth
    window.selectOAuthPlan = function(plan) {
        formData.plan = plan;
        modal.querySelectorAll('.oauth-plan-card').forEach(c => c.classList.remove('selected'));
        if (plan) {
            const card = modal.querySelector(`.oauth-plan-card[data-plan="${plan}"]`);
            if (card) card.classList.add('selected');
        }
    };

    // Continuer sans abonnement — avancer directement à l'étape suivante
    window.skipOAuthSubscription = function() {
        formData.plan = null;
        modal.querySelectorAll('.oauth-plan-card').forEach(c => c.classList.remove('selected'));
        hideError();
        currentStep++;
        if (currentStep > totalSteps) currentStep = totalSteps;
        updateStepUI();
        if (currentStep === totalSteps) updateSummary();
    };

    async function submitForm() {
        if (!validateCurrentStep()) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validation...';

        try {
            const response = await fetch('<?php echo e(route("become-provider.store")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                // Si paiement Stripe requis, rediriger vers la page de paiement
                if (result.requires_payment && result.checkout_url) {
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirection vers le paiement...';
                    window.location.href = result.checkout_url;
                    return;
                }
                // Afficher l'étape de succès avec proposition de vérification
                showSuccessStep(result);
            } else {
                if (result.errors) {
                    const firstError = Object.values(result.errors)[0];
                    showError(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    showError(result.message || 'Une erreur est survenue.');
                }
            }
        } catch (error) {
            console.error('Erreur soumission:', error);
            showError('Erreur de connexion. Veuillez réessayer.');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-check"></i> Valider et continuer';
        }
    }

    function showError(message) {
        errorText.textContent = message;
        errorMessage.style.display = 'flex';
    }

    function hideError() {
        errorMessage.style.display = 'none';
    }

    function showSuccessStep(result) {
        // Cacher toutes les étapes et les indicateurs
        stepContents.forEach(c => c.classList.remove('active'));
        document.getElementById('oauthProviderSteps').style.display = 'none';
        
        // Cacher les boutons de navigation
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
        btnSubmit.style.display = 'none';
        
        // Créer et afficher l'étape de succès
        let successDiv = document.getElementById('oauthSuccessStep');
        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.id = 'oauthSuccessStep';
            successDiv.className = 'oauth-step-content active';
            successDiv.innerHTML = `
                <div style="text-align: center; padding: 1rem 0;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; animation: scaleIn 0.5s ease;">
                        <i class="fas fa-check" style="color: white; font-size: 2rem;"></i>
                    </div>
                    <h5 style="color: #1f2937; font-weight: 700; margin-bottom: 0.5rem;">Félicitations ! 🎉</h5>
                    <p style="color: #6b7280; margin-bottom: 1.5rem;">Votre profil prestataire est maintenant actif. Vous pouvez désormais proposer vos services !</p>
                    
                    ${result.has_subscription ? `
                    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(5,150,105,0.08)); border: 1px solid rgba(16,185,129,0.2); border-radius: 12px; padding: 14px; margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-crown" style="color: #059669; margin-right: 6px;"></i>
                        <strong style="color: #166534;">Abonnement ${result.plan === 'annual' ? 'Annuel (85€/an)' : 'Mensuel (9,99€/mois)'} activé !</strong>
                    </div>
                    ` : ''}
                    
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
                            <li style="padding: 0.25rem 0; font-size: 0.85rem; color: #4b5563;"><i class="fas fa-check" style="color: #10b981; margin-right: 0.5rem;"></i> Annonces illimitées (entreprises)</li>
                        </ul>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: center;">
                        <button type="button" class="btn btn-outline-secondary" onclick="closeAndReload()" style="border-radius: 10px; padding: 0.625rem 1.25rem;">
                            <i class="fas fa-home"></i> Plus tard
                        </button>
                        <button type="button" class="btn btn-primary" onclick="goToVerification()" style="border-radius: 10px; padding: 0.625rem 1.25rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;">
                            <i class="fas fa-shield-alt"></i> Vérifier mon profil
                        </button>
                    </div>
                </div>
            `;
            modal.querySelector('.oauth-modal-body').appendChild(successDiv);
        } else {
            successDiv.classList.add('active');
        }
        
        // Cacher le bouton Annuler du footer
        const cancelBtn = modal.querySelector('.modal-footer .btn-outline-secondary');
        if (cancelBtn) cancelBtn.style.display = 'none';
    }

    // Fermer et recharger la page
    window.closeAndReload = function() {
        bootstrap.Modal.getInstance(modal).hide();
        window.location.reload();
    };

    // Aller à la page de vérification
    window.goToVerification = function() {
        bootstrap.Modal.getInstance(modal).hide();
        window.location.href = '<?php echo e(route("verification.index")); ?>';
    };

    // Reset au fermeture du modal
    modal.addEventListener('hidden.bs.modal', function() {
        currentStep = 1;
        formData = {
            business_type: '',
            company_name: '<?php echo e(Auth::user()->name); ?>',
            category: '',
            subcategory: '',
            country: '',
            city: '',
            plan: null
        };
        
        // Reset UI
        modal.querySelectorAll('.business-type-card').forEach(c => c.classList.remove('selected'));
        modal.querySelectorAll('.category-chip').forEach(c => c.classList.remove('selected'));
        modal.querySelectorAll('.oauth-plan-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('changeNameToggle').checked = false;
        document.getElementById('newNameInputWrapper').style.display = 'none';
        document.getElementById('oauth_company_name').value = '';
        document.getElementById('oauth_country').value = '';
        document.getElementById('oauth_city').value = '';
        document.getElementById('oauth_city').disabled = true;
        document.getElementById('oauthSubcategoryWrapper').style.display = 'none';
        
        // Reset success step
        const successDiv = document.getElementById('oauthSuccessStep');
        if (successDiv) successDiv.remove();
        
        // Rétablir les indicateurs d'étapes
        document.getElementById('oauthProviderSteps').style.display = 'flex';
        
        // Rétablir le bouton Annuler
        const cancelBtn = modal.querySelector('.modal-footer .btn-outline-secondary');
        if (cancelBtn) cancelBtn.style.display = '';
        
        hideError();
        updateStepUI();
    });
});
</script>
<?php /**PATH C:\Users\PC\Desktop\MASSIWANI V2\resources\views/partials/provider-oauth-modal.blade.php ENDPATH**/ ?>