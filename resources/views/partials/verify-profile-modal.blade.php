{{-- Modal Vérification de Profil --}}
<div class="modal fade" id="verifyProfileModal" tabindex="-1" aria-labelledby="verifyProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="verifyProfileModalLabel">Vérifier mon profil</h5>
                        <small class="opacity-75">Obtenez le badge "Profil vérifié" pour 10€</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Step indicators -->
                <div class="d-flex justify-content-center mb-4">
                    <div class="step-indicator active" id="verify-step-1-indicator">
                        <span class="step-number">1</span>
                        <span class="step-label">Documents</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-indicator" id="verify-step-2-indicator">
                        <span class="step-number">2</span>
                        <span class="step-label">Paiement</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-indicator" id="verify-step-3-indicator">
                        <span class="step-number">3</span>
                        <span class="step-label">Confirmation</span>
                    </div>
                </div>

                <!-- Step 1: Documents -->
                <div id="verify-step-1" class="verify-step">
                    <div class="text-center mb-4">
                        <h5>Téléchargez vos documents d'identité</h5>
                        <p class="text-muted">Nous avons besoin de vérifier votre identité pour sécuriser la plateforme</p>
                    </div>

                    <form id="verificationForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="profile_verification">
                        
                        <!-- Type de document -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Type de document</label>
                            <select name="document_type" class="form-select form-select-lg" required>
                                <option value="">Choisissez un document...</option>
                                <option value="cni">Carte Nationale d'Identité</option>
                                <option value="passport">Passeport</option>
                                <option value="permis">Permis de conduire</option>
                                <option value="carte_sejour">Carte de séjour</option>
                            </select>
                        </div>

                        <!-- Upload documents -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Recto du document *</label>
                                <div class="upload-zone w-100" id="zone_front" onclick="document.getElementById('doc_front').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-primary" id="icon_front"></i>
                                    <p class="mb-0" id="text_front">Cliquez pour télécharger</p>
                                    <small class="text-muted" id="hint_front">JPG, PNG (max 8MB)</small>
                                    <img id="preview_front" class="upload-preview d-none" alt="Aperçu">
                                </div>
                                <input type="file" id="doc_front" name="document_front" style="display:none;" accept="image/*" onchange="previewAndStore('doc_front', 'preview_front', 'zone_front')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Verso du document <small class="text-muted">(optionnel)</small></label>
                                <div class="upload-zone w-100" id="zone_back" onclick="document.getElementById('doc_back').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-primary" id="icon_back"></i>
                                    <p class="mb-0" id="text_back">Cliquez pour télécharger</p>
                                    <small class="text-muted" id="hint_back">JPG, PNG (max 8MB)</small>
                                    <img id="preview_back" class="upload-preview d-none" alt="Aperçu">
                                </div>
                                <input type="file" id="doc_back" name="document_back" style="display:none;" accept="image/*" onchange="previewAndStore('doc_back', 'preview_back', 'zone_back')">
                            </div>
                        </div>

                        <!-- Selfie -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Selfie avec votre document *</label>
                            <p class="text-muted small mb-2">Prenez une photo de vous tenant votre document à côté de votre visage</p>
                            <div class="upload-zone w-100" id="zone_selfie" onclick="document.getElementById('selfie').click()">
                                <i class="fas fa-camera fa-2x mb-2 text-primary" id="icon_selfie"></i>
                                <p class="mb-0" id="text_selfie">Cliquez pour télécharger votre selfie</p>
                                <small class="text-muted" id="hint_selfie">JPG, PNG (max 8MB)</small>
                                <img id="preview_selfie" class="upload-preview d-none" alt="Aperçu">
                            </div>
                            <input type="file" id="selfie" name="selfie" style="display:none;" accept="image/*" onchange="previewAndStore('selfie', 'preview_selfie', 'zone_selfie')">
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Frais de vérification : 10€</strong> - Ces frais couvrent la vérification manuelle de vos documents par notre équipe.
                        </div>
                    </form>
                </div>

                <!-- Step 2: Payment -->
                <div id="verify-step-2" class="verify-step d-none">
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <div class="p-4 rounded-circle d-inline-block" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                                <i class="fas fa-credit-card fa-3x text-warning"></i>
                            </div>
                        </div>
                        <h4>Procédez au paiement</h4>
                        <p class="text-muted mb-4">Vos documents ont été téléchargés. Finalisez votre demande en effectuant le paiement sécurisé.</p>
                        
                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 350px;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Vérification de profil</span>
                                    <span class="fw-bold text-primary">10,00 €</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total</span>
                                    <span class="fw-bold fs-4 text-success">10,00 €</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" id="payVerificationBtn" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-lock me-2"></i>Payer 10€ maintenant
                            </button>
                            <p class="text-muted small mt-2">
                                <i class="fab fa-stripe me-1"></i>Paiement sécurisé par Stripe
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Confirmation -->
                <div id="verify-step-3" class="verify-step d-none">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <div class="p-4 rounded-circle d-inline-block" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
                                <i class="fas fa-check-circle fa-4x text-success"></i>
                            </div>
                        </div>
                        <h4 class="text-success">Demande envoyée !</h4>
                        <p class="text-muted">Votre demande de vérification a été envoyée à notre équipe.</p>
                        <p>Nous examinerons vos documents dans les <strong>24-48 heures</strong>.</p>
                        <p class="text-muted small">Vous recevrez une notification une fois la vérification effectuée.</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                <button type="button" id="verifyNextBtn" class="btn btn-success" onclick="handleVerifySubmit()">
                    Continuer <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        min-height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }
    
    .upload-zone:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }
    
    .upload-zone.has-file {
        border-color: #10b981;
        background: #f0fdf4;
    }
    
    .upload-preview {
        max-width: 100%;
        max-height: 120px;
        border-radius: 8px;
        margin-top: 10px;
    }
    
    .step-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        opacity: 0.5;
    }
    
    .step-indicator.active {
        opacity: 1;
    }
    
    .step-indicator.completed {
        opacity: 1;
    }
    
    .step-indicator.completed .step-number {
        background: #10b981;
    }
    
    .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #9ca3af;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .step-indicator.active .step-number {
        background: #10b981;
    }
    
    .step-label {
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    .step-connector {
        width: 60px;
        height: 2px;
        background: #d1d5db;
        margin: 18px 10px;
    }
</style>

<script>
    // Variables globales pour stocker les fichiers sélectionnés
    var selectedFiles = {
        doc_front: null,
        doc_back: null,
        selfie: null
    };
    var currentVerificationId = null;
    
    // Fonction pour prévisualiser ET stocker le fichier
    function previewAndStore(inputId, previewId, zoneId) {
        var input = document.getElementById(inputId);
        var preview = document.getElementById(previewId);
        var zone = document.getElementById(zoneId);
        
        console.log('previewAndStore called for:', inputId);
        console.log('Files in input:', input.files);
        
        if (input.files && input.files[0]) {
            var file = input.files[0];
            console.log('File selected:', file.name, 'Size:', file.size);
            
            // Stocker le fichier dans notre variable globale
            selectedFiles[inputId] = file;
            
            // Afficher l'aperçu
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                preview.style.display = 'block';
                preview.style.maxWidth = '100%';
                preview.style.maxHeight = '120px';
                
                // Cacher les icônes et textes
                zone.classList.add('has-file');
                var icons = zone.querySelectorAll('i, p, small');
                icons.forEach(function(el) {
                    el.style.display = 'none';
                });
                
                console.log('Preview displayed for:', inputId);
            };
            reader.readAsDataURL(file);
        }
    }
    
    // Fonction pour soumettre les documents
    function handleVerifySubmit() {
        console.log('handleVerifySubmit called');
        console.log('Selected files:', selectedFiles);
        
        // Récupérer le type de document
        var docTypeSelect = document.querySelector('select[name="document_type"]');
        var docType = docTypeSelect ? docTypeSelect.value : '';
        
        // Vérifier aussi directement les inputs (fallback)
        var docFrontInput = document.getElementById('doc_front');
        var selfieInput = document.getElementById('selfie');
        var docBackInput = document.getElementById('doc_back');
        
        var docFront = selectedFiles.doc_front || (docFrontInput && docFrontInput.files[0]);
        var selfie = selectedFiles.selfie || (selfieInput && selfieInput.files[0]);
        var docBack = selectedFiles.doc_back || (docBackInput && docBackInput.files[0]);
        
        console.log('docType:', docType);
        console.log('docFront:', docFront);
        console.log('selfie:', selfie);
        
        // Limite de 8MB en octets
        var maxSize = 8 * 1024 * 1024;
        
        // Validation
        if (!docType) {
            alert('Veuillez sélectionner un type de document.');
            return;
        }
        if (!docFront) {
            alert('Veuillez télécharger le recto de votre document.');
            return;
        }
        if (!selfie) {
            alert('Veuillez télécharger votre selfie avec le document.');
            return;
        }
        
        // Vérifier la taille des fichiers
        if (docFront.size > maxSize) {
            alert('Le recto du document est trop volumineux (' + (docFront.size / 1024 / 1024).toFixed(2) + ' Mo). Maximum: 8 Mo.');
            return;
        }
        if (selfie.size > maxSize) {
            alert('Le selfie est trop volumineux (' + (selfie.size / 1024 / 1024).toFixed(2) + ' Mo). Maximum: 8 Mo.');
            return;
        }
        if (docBack && docBack.size > maxSize) {
            alert('Le verso du document est trop volumineux (' + (docBack.size / 1024 / 1024).toFixed(2) + ' Mo). Maximum: 8 Mo.');
            return;
        }
        
        // Créer le FormData avec les fichiers
        var formData = new FormData();
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        formData.append('_token', csrfToken ? csrfToken.getAttribute('content') : '{{ csrf_token() }}');
        formData.append('type', 'profile_verification');
        formData.append('document_type', docType);
        formData.append('document_front', docFront, docFront.name);
        if (docBack) {
            formData.append('document_back', docBack, docBack.name);
        }
        formData.append('selfie', selfie, selfie.name);
        
        console.log('FormData created, sending request...');
        
        var btn = document.getElementById('verifyNextBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi...';
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("verification.submit.ajax") }}', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log('Response status:', xhr.status);
                console.log('Response:', xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            currentVerificationId = data.verification_id;
                            document.getElementById('verify-step-1').classList.add('d-none');
                            document.getElementById('verify-step-2').classList.remove('d-none');
                            document.getElementById('verify-step-1-indicator').classList.remove('active');
                            document.getElementById('verify-step-1-indicator').classList.add('completed');
                            document.getElementById('verify-step-2-indicator').classList.add('active');
                            btn.classList.add('d-none');
                        } else {
                            alert(data.message || 'Une erreur est survenue.');
                            btn.disabled = false;
                            btn.innerHTML = 'Continuer <i class="fas fa-arrow-right ms-2"></i>';
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                        alert('Erreur lors du traitement de la réponse.');
                        btn.disabled = false;
                        btn.innerHTML = 'Continuer <i class="fas fa-arrow-right ms-2"></i>';
                    }
                } else {
                    try {
                        var errorData = JSON.parse(xhr.responseText);
                        if (errorData.errors) {
                            var errorMessages = [];
                            for (var field in errorData.errors) {
                                if (errorData.errors.hasOwnProperty(field)) {
                                    errorMessages.push(errorData.errors[field][0]);
                                }
                            }
                            alert('Erreurs de validation:\n' + errorMessages.join('\n'));
                        } else {
                            alert('Erreur: ' + (errorData.message || 'Erreur serveur'));
                        }
                    } catch (e) {
                        if (xhr.status === 419) {
                            alert('Votre session a expiré. Veuillez rafraîchir la page et réessayer.');
                        } else if (xhr.status === 413) {
                            alert('Les fichiers sont trop volumineux. Veuillez réduire leur taille (max 8 Mo par fichier).');
                        } else if (xhr.status === 500) {
                            alert('Erreur serveur. Veuillez réessayer dans quelques instants.');
                        } else {
                            alert('Erreur serveur (code ' + xhr.status + '). Veuillez réessayer.');
                        }
                    }
                    btn.disabled = false;
                    btn.innerHTML = 'Continuer <i class="fas fa-arrow-right ms-2"></i>';
                }
            }
        };
        
        xhr.send(formData);
    }
    
    // Fonction pour le paiement
    function handlePayment() {
        console.log('handlePayment called');
        
        var btn = document.getElementById('payVerificationBtn');
        
        if (!currentVerificationId) {
            alert('Erreur: ID de vérification manquant.');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Redirection...';
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("verification.create.payment") }}', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success && data.checkout_url) {
                            window.location.href = data.checkout_url;
                        } else {
                            alert(data.message || 'Erreur lors de la création du paiement.');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-lock me-2"></i>Payer 10€ maintenant';
                        }
                    } catch (e) {
                        alert('Erreur lors du traitement de la réponse.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-lock me-2"></i>Payer 10€ maintenant';
                    }
                } else {
                    alert('Erreur serveur: ' + xhr.status);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock me-2"></i>Payer 10€ maintenant';
                }
            }
        };
        
        xhr.send(JSON.stringify({
            verification_id: currentVerificationId
        }));
    }
    
    // Initialisation du bouton payer au chargement
    document.addEventListener('DOMContentLoaded', function() {
        var payBtn = document.getElementById('payVerificationBtn');
        if(payBtn) {
            payBtn.onclick = handlePayment;
        }
    });
</script>
