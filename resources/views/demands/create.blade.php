@extends('layouts.app')

@section('title', 'Trouver un professionnel - ProxiPro')

@push('styles')
<style>
body { background: #f0f2f5; }

.demand-container {
    max-width: 820px;
    margin: 0 auto;
    padding: 28px 20px 70px;
}

/* Hero Header */
.demand-hero {
    text-align: center;
    padding: 40px 24px 30px;
    margin-bottom: 28px;
}
.demand-hero-icon {
    width: 84px; height: 84px; border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px; color: white; font-size: 2.1rem;
    box-shadow: 0 8px 30px rgba(59, 130, 246, 0.3);
}
.demand-hero h1 {
    font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: 10px;
}
.demand-hero p {
    font-size: 1.08rem; color: #6b7280; max-width: 500px; margin: 0 auto; line-height: 1.6;
}

/* Steps */
.demand-steps {
    display: flex; align-items: center; justify-content: center;
    gap: 0; margin-bottom: 28px;
}
.demand-step {
    display: flex; align-items: center; gap: 8px;
}
.demand-step-num {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; font-weight: 700; transition: all 0.3s;
}
.demand-step-num.pending { background: #e5e7eb; color: #9ca3af; }
.demand-step-num.active { background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; box-shadow: 0 3px 12px rgba(59,130,246,0.3); }
.demand-step-num.done { background: #22c55e; color: #fff; }
.demand-step-label { font-size: 0.92rem; font-weight: 600; color: #9ca3af; }
.demand-step-label.active { color: #3b82f6; }
.demand-step-label.done { color: #22c55e; }
.demand-step-line { width: 40px; height: 2px; background: #e5e7eb; margin: 0 8px; }
.demand-step-line.done { background: #22c55e; }

/* Card */
.demand-card {
    background: white; border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
}
.demand-card-body { padding: 36px 32px; }

/* Step sections */
.demand-section { display: none; }
.demand-section.active { display: block; animation: fadeUp 0.3s ease; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Category grid */
.demand-cat-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
}
@media (max-width: 480px) { .demand-cat-grid { grid-template-columns: repeat(2, 1fr); } }

.demand-cat-btn {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    padding: 22px 12px; border-radius: 16px; border: 2px solid #e5e7eb;
    background: #fff; cursor: pointer; transition: all 0.2s; text-align: center;
}
.demand-cat-btn:hover { border-color: #93c5fd; background: #f0f7ff; transform: translateY(-2px); }
.demand-cat-btn.selected { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
.demand-cat-btn .cat-emoji { font-size: 2.2rem; }
.demand-cat-btn .cat-label { font-size: 0.88rem; font-weight: 600; color: #374151; line-height: 1.3; }
.demand-cat-btn.selected .cat-label { color: #3b82f6; }

/* Search */
.demand-search {
    position: relative; margin-bottom: 14px;
}
.demand-search input {
    width: 100%; padding: 14px 16px 14px 42px; border: 2px solid #e5e7eb;
    border-radius: 14px; font-size: 1rem; outline: none; transition: border-color 0.2s;
}
.demand-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.demand-search i {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.85rem;
}

/* Subcategory chips */
.demand-sub-list { display: flex; flex-wrap: wrap; gap: 8px; }
.demand-sub-chip {
    padding: 11px 20px; border-radius: 24px; border: 2px solid #e5e7eb;
    font-size: 0.95rem; font-weight: 500; color: #6b7280; background: #fff;
    cursor: pointer; transition: all 0.2s;
}
.demand-sub-chip:hover { border-color: #93c5fd; background: #f0f7ff; }
.demand-sub-chip.selected { border-color: #3b82f6; background: #3b82f6; color: #fff; }

/* Selection badge */
.demand-selection-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, #eff6ff, #e0e7ff); border: 1px solid #93c5fd;
    border-radius: 12px; padding: 12px 20px; margin-bottom: 22px; font-size: 0.98rem; color: #1e40af;
}
.demand-selection-badge i { color: #3b82f6; }
.demand-selection-badge button {
    background: none; border: none; color: #6366f1; cursor: pointer; font-size: 0.8rem; font-weight: 600;
    margin-left: 8px;
}

/* Form fields */
.demand-field { margin-bottom: 22px; }
.demand-field label {
    display: block; font-size: 0.98rem; font-weight: 600; color: #374151; margin-bottom: 8px;
}
.demand-field label .required { color: #ef4444; }
.demand-field input, .demand-field textarea, .demand-field select {
    width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 14px;
    font-size: 1rem; color: #111827; outline: none; transition: all 0.2s;
    background: #fafbfc;
}
.demand-field input:focus, .demand-field textarea:focus, .demand-field select:focus {
    border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.demand-field textarea { min-height: 130px; resize: vertical; }
.demand-field .hint { font-size: 0.85rem; color: #9ca3af; margin-top: 6px; }
.demand-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 480px) { .demand-field-row { grid-template-columns: 1fr; } }

/* Urgency */
.demand-urgency { display: flex; gap: 10px; }
.demand-urgency-btn {
    flex: 1; padding: 16px 12px; border-radius: 14px; border: 2px solid #e5e7eb;
    background: #fff; cursor: pointer; text-align: center; transition: all 0.2s;
}
.demand-urgency-btn:hover { border-color: #93c5fd; }
.demand-urgency-btn.selected { border-color: var(--urgency-color); background: var(--urgency-bg); }
.demand-urgency-btn .urgency-icon { font-size: 1.5rem; margin-bottom: 6px; }
.demand-urgency-btn .urgency-label { font-size: 0.92rem; font-weight: 600; color: #374151; }

/* Photo upload */
.demand-photo-area {
    border: 2px dashed #d1d5db; border-radius: 16px; padding: 28px;
    text-align: center; cursor: pointer; transition: all 0.2s; background: #fafbfc;
}
.demand-photo-area:hover { border-color: #93c5fd; background: #f0f7ff; }
.demand-photo-area i { font-size: 1.8rem; color: #9ca3af; }
.demand-photo-area p { font-size: 0.9rem; color: #9ca3af; margin: 8px 0 0; }
.demand-photo-previews { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
.demand-photo-preview {
    width: 100px; height: 100px; border-radius: 12px; overflow: hidden; position: relative;
}
.demand-photo-preview img { width: 100%; height: 100%; object-fit: cover; }
.demand-photo-preview button {
    position: absolute; top: 4px; right: 4px; width: 22px; height: 22px;
    border-radius: 50%; background: rgba(0,0,0,0.6); color: #fff; border: none;
    font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
}

/* Navigation */
.demand-nav {
    display: flex; justify-content: space-between; padding: 22px 32px;
    border-top: 1px solid #f0f0f0; background: #fafafa;
}
.demand-btn {
    padding: 14px 30px; border-radius: 14px; font-size: 1rem; font-weight: 600;
    border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s;
}
.demand-btn-back { background: #f3f4f6; color: #6b7280; }
.demand-btn-back:hover { background: #e5e7eb; }
.demand-btn-next {
    background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
    box-shadow: 0 4px 14px rgba(59,130,246,0.3);
}
.demand-btn-next:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.4); }
.demand-btn-next:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
.demand-btn-submit {
    background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff;
    box-shadow: 0 4px 14px rgba(34,197,94,0.3);
}
.demand-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(34,197,94,0.4); }
.demand-btn-submit-content {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.demand-btn-submit-text {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

/* Error */
.demand-error {
    background: #fef2f2; color: #dc2626; padding: 10px 14px; border-radius: 10px;
    font-size: 0.85rem; margin-top: 10px; display: none; align-items: center; gap: 8px;
}

/* Back to category link */
.demand-back-link {
    display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem;
    color: #6b7280; cursor: pointer; margin-bottom: 14px; background: none; border: none; padding: 0;
}
.demand-back-link:hover { color: #3b82f6; }

@media (max-width: 768px) {
    .demand-nav {
        flex-direction: column;
        gap: 12px;
    }
    .demand-btn {
        width: 100%;
        justify-content: center;
    }
    .demand-btn-submit {
        padding: 16px 20px;
    }
    .demand-btn-submit-content {
        flex-direction: column;
        gap: 6px;
    }
    .demand-btn-submit-text {
        font-size: 1rem;
        line-height: 1.2;
    }
}

@media (max-width: 420px) {
    .demand-nav {
        padding: 18px 20px;
    }
    .demand-btn-submit-text {
        flex-wrap: wrap;
        row-gap: 4px;
        column-gap: 6px;
    }
}
</style>
@endpush

@section('content')
<div class="demand-container">
    <!-- Hero -->
    <div class="demand-hero">
        <div class="demand-hero-icon">
            <i class="fas fa-search"></i>
        </div>
        <h1>De quoi avez-vous besoin ?</h1>
        <p>Décrivez votre besoin en 2 minutes et trouvez les professionnels disponibles près de chez vous.</p>
    </div>

    <!-- Steps indicator -->
    <div class="demand-steps" id="demandSteps">
        <div class="demand-step">
            <div class="demand-step-num active" id="stepNum1">1</div>
            <span class="demand-step-label active" id="stepLabel1">Service</span>
        </div>
        <div class="demand-step-line" id="stepLine1"></div>
        <div class="demand-step">
            <div class="demand-step-num pending" id="stepNum2">2</div>
            <span class="demand-step-label" id="stepLabel2">Détails</span>
        </div>
        <div class="demand-step-line" id="stepLine2"></div>
        <div class="demand-step">
            <div class="demand-step-num pending" id="stepNum3">3</div>
            <span class="demand-step-label" id="stepLabel3">Publier</span>
        </div>
    </div>

    <form method="POST" action="{{ route('demand.store') }}" enctype="multipart/form-data" id="demandForm">
        @csrf
        <input type="hidden" name="main_category" id="h_main_category">
        <input type="hidden" name="category" id="h_category">
        <input type="hidden" name="urgency" id="h_urgency" value="normal">

        <div class="demand-card">
            <div class="demand-card-body">

                @if ($errors->any())
                <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:18px;">
                    <strong style="color:#dc2626; font-size:0.88rem;"><i class="fas fa-exclamation-triangle me-1"></i> Erreur</strong>
                    <ul style="margin:6px 0 0; padding-left:18px; color:#dc2626; font-size:0.85rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- ═══ STEP 1 : Choix du service ═══ -->
                <div class="demand-section active" id="demandStep1">
                    <div class="demand-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="demandCatSearch" placeholder="Rechercher un service (ex: plombier, électricien...)" oninput="filterDemandCategories(this.value)">
                    </div>

                    <div id="demandCatGrid" class="demand-cat-grid">
                        @foreach($categoriesData as $catName => $catData)
                        <button type="button" class="demand-cat-btn" data-cat="{{ $catName }}" onclick="selectDemandCategory('{{ addslashes($catName) }}')">
                            <span class="cat-emoji">{{ $catData['icon'] }}</span>
                            <span class="cat-label">{{ $catName }}</span>
                        </button>
                        @endforeach
                    </div>

                    <!-- Subcategories (hidden by default) -->
                    <div id="demandSubSection" style="display:none;">
                        <button type="button" class="demand-back-link" onclick="resetDemandCategory()">
                            <i class="fas fa-chevron-left"></i> Toutes les catégories
                        </button>
                        <p style="font-size:0.88rem; color:#6b7280; margin-bottom:12px;">
                            Choisissez le service dont vous avez besoin dans <strong id="demandSelectedCatName" style="color:#3b82f6;"></strong> :
                        </p>
                        <div id="demandSubList" class="demand-sub-list"></div>
                    </div>

                    <div class="demand-error" id="step1Error">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <!-- ═══ STEP 2 : Détails de la demande ═══ -->
                <div class="demand-section" id="demandStep2">
                    <div class="demand-selection-badge" id="demandSelectionBadge">
                        <i class="fas fa-tag"></i>
                        <span id="demandSelectionText"></span>
                        <button type="button" onclick="goToDemandStep(1)"><i class="fas fa-pen"></i> Modifier</button>
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-heading me-1"></i> Titre court <span class="required">*</span></label>
                        <input type="text" name="title" id="demandTitle" placeholder="Ex: Recherche plombier pour fuite urgente" maxlength="255" value="{{ old('title') }}">
                        <p class="hint">Résumez votre besoin en une phrase</p>
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-align-left me-1"></i> Décrivez votre besoin <span class="required">*</span></label>
                        <textarea name="description" id="demandDesc" placeholder="Décrivez précisément ce dont vous avez besoin : type de travaux, préférences, délais souhaités...">{{ old('description') }}</textarea>
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-map-marker-alt me-1"></i> Localisation <span class="required">*</span></label>
                        <div class="demand-field-row">
                            <div>
                                <select name="country" id="demandCountry" onchange="updateDemandCities()">
                                    <option value="">-- Pays --</option>
                                    <option value="France" {{ (old('country') ?? Auth::user()->country ?? '') == 'France' ? 'selected' : '' }}>🇫🇷 France</option>
                                    <option value="Mayotte" {{ (old('country') ?? Auth::user()->country ?? '') == 'Mayotte' ? 'selected' : '' }}>🇾🇹 Mayotte</option>
                                    <option value="Madagascar" {{ (old('country') ?? Auth::user()->country ?? '') == 'Madagascar' ? 'selected' : '' }}>🇲🇬 Madagascar</option>
                                    <option value="La Réunion" {{ (old('country') ?? Auth::user()->country ?? '') == 'La Réunion' ? 'selected' : '' }}>🇷🇪 La Réunion</option>
                                    <option value="Maurice" {{ (old('country') ?? Auth::user()->country ?? '') == 'Maurice' ? 'selected' : '' }}>🇲🇺 Maurice</option>
                                    <option value="Belgique" {{ (old('country') ?? Auth::user()->country ?? '') == 'Belgique' ? 'selected' : '' }}>🇧🇪 Belgique</option>
                                    <option value="Suisse" {{ (old('country') ?? Auth::user()->country ?? '') == 'Suisse' ? 'selected' : '' }}>🇨🇭 Suisse</option>
                                    <option value="Canada" {{ (old('country') ?? Auth::user()->country ?? '') == 'Canada' ? 'selected' : '' }}>🇨🇦 Canada</option>
                                    <option value="Sénégal" {{ (old('country') ?? Auth::user()->country ?? '') == 'Sénégal' ? 'selected' : '' }}>🇸🇳 Sénégal</option>
                                    <option value="Côte d'Ivoire" {{ (old('country') ?? Auth::user()->country ?? '') == "Côte d'Ivoire" ? 'selected' : '' }}>🇨🇮 Côte d'Ivoire</option>
                                    <option value="Maroc" {{ (old('country') ?? Auth::user()->country ?? '') == 'Maroc' ? 'selected' : '' }}>🇲🇦 Maroc</option>
                                    <option value="Tunisie" {{ (old('country') ?? Auth::user()->country ?? '') == 'Tunisie' ? 'selected' : '' }}>🇹🇳 Tunisie</option>
                                    <option value="Algérie" {{ (old('country') ?? Auth::user()->country ?? '') == 'Algérie' ? 'selected' : '' }}>🇩🇿 Algérie</option>
                                </select>
                            </div>
                            <div>
                                <select name="city" id="demandCity">
                                    <option value="">-- Ville --</option>
                                </select>
                            </div>
                        </div>
                        <input type="text" name="location" id="demandLocation" placeholder="Ou saisissez votre ville" style="display:none; margin-top:8px;" value="{{ old('location') }}">
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-clock me-1"></i> Urgence</label>
                        <div class="demand-urgency">
                            <button type="button" class="demand-urgency-btn selected" data-urgency="normal" style="--urgency-color:#22c55e; --urgency-bg:#f0fdf4;" onclick="selectUrgency('normal')">
                                <div class="urgency-icon">🕐</div>
                                <div class="urgency-label">Normal</div>
                            </button>
                            <button type="button" class="demand-urgency-btn" data-urgency="urgent" style="--urgency-color:#f59e0b; --urgency-bg:#fffbeb;" onclick="selectUrgency('urgent')">
                                <div class="urgency-icon">⚡</div>
                                <div class="urgency-label">Urgent</div>
                            </button>
                            <button type="button" class="demand-urgency-btn" data-urgency="tres_urgent" style="--urgency-color:#ef4444; --urgency-bg:#fef2f2;" onclick="selectUrgency('tres_urgent')">
                                <div class="urgency-icon">🚨</div>
                                <div class="urgency-label">Très urgent</div>
                            </button>
                        </div>
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-euro-sign me-1"></i> Budget estimé <span style="color:#9ca3af; font-weight:400;">(facultatif)</span></label>
                        <input type="number" name="price" id="demandPrice" placeholder="Laissez vide si à discuter" min="0" step="0.01" value="{{ old('price') }}">
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-camera me-1"></i> Photos <span style="color:#9ca3af; font-weight:400;">(facultatif)</span></label>
                        <div class="demand-photo-area" onclick="document.getElementById('demandPhotoInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Cliquez pour ajouter des photos (max 2)</p>
                        </div>
                        <input type="file" id="demandPhotoInput" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleDemandPhotos(this)">
                        <div class="demand-photo-previews" id="demandPhotoPreviews"></div>
                    </div>

                    <div class="demand-error" id="step2Error">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <!-- ═══ STEP 3 : Récapitulatif ═══ -->
                <div class="demand-section" id="demandStep3">
                    <div style="text-align:center; margin-bottom:20px;">
                        <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#22c55e,#16a34a); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:#fff; font-size:1.4rem;">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h3 style="font-size:1.15rem; font-weight:700; color:#111827; margin-bottom:4px;">Vérifiez et publiez</h3>
                        <p style="font-size:0.85rem; color:#6b7280;">Votre demande sera envoyée aux professionnels correspondants.</p>
                    </div>

                    <div id="demandRecap" style="background:#f8fafc; border-radius:14px; padding:18px; margin-bottom:18px; border:1px solid #e5e7eb;">
                        <!-- Rempli dynamiquement -->
                    </div>

                    <div style="background:linear-gradient(135deg,#eff6ff,#e0e7ff); border:1px solid #93c5fd; border-radius:12px; padding:14px 18px; margin-bottom:16px;">
                        <div style="display:flex; align-items:flex-start; gap:10px;">
                            <i class="fas fa-magic" style="color:#3b82f6; margin-top:2px;"></i>
                            <div>
                                <strong style="color:#1e40af; font-size:0.9rem;">Que se passe-t-il ensuite ?</strong>
                                <ul style="margin:6px 0 0; padding-left:18px; font-size:0.82rem; color:#1e40af;">
                                    <li>Les professionnels correspondants sont immédiatement notifiés</li>
                                    <li>Vous verrez les profils des pros disponibles dans votre zone</li>
                                    <li>Vous pouvez les contacter directement depuis les résultats</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Navigation -->
            <div class="demand-nav">
                <button type="button" class="demand-btn demand-btn-back" id="demandBtnBack" style="visibility:hidden;" onclick="prevDemandStep()">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>
                <button type="button" class="demand-btn demand-btn-next" id="demandBtnNext" onclick="nextDemandStep()" disabled>
                    Continuer <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" class="demand-btn demand-btn-submit" id="demandBtnSubmit" style="display:none;">
                    <span class="demand-btn-submit-content">
                        <i class="fas fa-paper-plane"></i>
                        <span class="demand-btn-submit-text">
                            <span>Publier</span>
                            <span>et trouver des pros</span>
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
const categoriesData = @json($categoriesData);
const preCategory = @json($preCategory);
const preSubcategory = @json($preSubcategory);

let currentStep = 1;
let selectedCat = null;
let selectedSub = null;
let demandPhotos = [];

const citiesByCountry = {
    "France": ["Paris","Marseille","Lyon","Toulouse","Nice","Nantes","Strasbourg","Montpellier","Bordeaux","Lille","Rennes","Reims","Le Havre","Grenoble","Dijon","Angers","Nîmes","Clermont-Ferrand"],
    "Mayotte": ["Mamoudzou","Koungou","Dzaoudzi","Dembeni","Bandraboua","Tsingoni","Sada","Ouangani","Chiconi","Pamandzi","Mtsamboro","Acoua","Chirongui","Bouéni","Kani-Kéli","Bandrélé","M'Tsangamouji"],
    "Madagascar": ["Antananarivo","Toamasina","Antsirabe","Fianarantsoa","Mahajanga","Toliara","Antsiranana","Nosy Be"],
    "La Réunion": ["Saint-Denis","Saint-Paul","Saint-Pierre","Le Tampon","Saint-André","Saint-Louis","Saint-Benoît","Le Port"],
    "Maurice": ["Port-Louis","Beau Bassin-Rose Hill","Vacoas-Phoenix","Curepipe","Quatre Bornes","Grand Baie"],
    "Belgique": ["Bruxelles","Anvers","Gand","Charleroi","Liège","Bruges","Namur","Louvain","Mons"],
    "Suisse": ["Zurich","Genève","Bâle","Lausanne","Berne","Lucerne","Fribourg","Neuchâtel"],
    "Canada": ["Toronto","Montréal","Vancouver","Calgary","Edmonton","Ottawa","Québec"],
    "Sénégal": ["Dakar","Thiès","Rufisque","Kaolack","Saint-Louis","Ziguinchor","Touba"],
    "Côte d'Ivoire": ["Abidjan","Bouaké","Yamoussoukro","Korhogo","San-Pédro","Daloa"],
    "Maroc": ["Casablanca","Rabat","Fès","Marrakech","Tanger","Agadir","Meknès"],
    "Tunisie": ["Tunis","Sfax","Sousse","Kairouan","Bizerte","Gabès","Monastir"],
    "Algérie": ["Alger","Oran","Constantine","Annaba","Blida","Batna","Sétif"]
};

// ─── Step navigation ───
function updateStepUI() {
    for (let i = 1; i <= 3; i++) {
        const num = document.getElementById('stepNum' + i);
        const label = document.getElementById('stepLabel' + i);
        num.classList.remove('active', 'done', 'pending');
        label.classList.remove('active', 'done');
        label.className = 'demand-step-label';

        if (i < currentStep) {
            num.classList.add('done');
            num.innerHTML = '<i class="fas fa-check" style="font-size:0.7rem;"></i>';
            label.classList.add('done');
        } else if (i === currentStep) {
            num.classList.add('active');
            num.textContent = i;
            label.classList.add('active');
        } else {
            num.classList.add('pending');
            num.textContent = i;
        }
    }
    for (let i = 1; i <= 2; i++) {
        document.getElementById('stepLine' + i).classList.toggle('done', i < currentStep);
    }

    document.querySelectorAll('.demand-section').forEach(s => s.classList.remove('active'));
    document.getElementById('demandStep' + currentStep).classList.add('active');

    document.getElementById('demandBtnBack').style.visibility = currentStep > 1 ? 'visible' : 'hidden';
    document.getElementById('demandBtnNext').style.display = currentStep < 3 ? 'inline-flex' : 'none';
    document.getElementById('demandBtnSubmit').style.display = currentStep === 3 ? 'inline-flex' : 'none';
}

function goToDemandStep(step) {
    currentStep = step;
    updateStepUI();
    updateNextBtn();
}

function nextDemandStep() {
    hideErrors();
    if (currentStep === 1) {
        if (!selectedSub) {
            showError('step1Error', 'Veuillez sélectionner un service.');
            return;
        }
        currentStep = 2;
        document.getElementById('demandSelectionText').textContent = selectedCat + ' → ' + selectedSub;
        updateStepUI();
        updateNextBtn();
    } else if (currentStep === 2) {
        if (!validateStep2()) return;
        currentStep = 3;
        buildRecap();
        updateStepUI();
    }
}

function prevDemandStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepUI();
        updateNextBtn();
    }
}

function updateNextBtn() {
    const btn = document.getElementById('demandBtnNext');
    if (currentStep === 1) {
        btn.disabled = !selectedSub;
    } else if (currentStep === 2) {
        btn.disabled = false;
    }
}

// ─── Step 1: Categories ───
function selectDemandCategory(catName) {
    selectedCat = catName;
    selectedSub = null;

    document.getElementById('demandCatGrid').style.display = 'none';
    document.getElementById('demandCatSearch').parentElement.style.display = 'none';
    document.getElementById('demandSubSection').style.display = 'block';
    document.getElementById('demandSelectedCatName').textContent = catName;

    const subList = document.getElementById('demandSubList');
    subList.innerHTML = '';
    const subs = categoriesData[catName]?.subcategories || [];
    subs.forEach(sub => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'demand-sub-chip';
        chip.textContent = sub;
        chip.onclick = function() {
            document.querySelectorAll('.demand-sub-chip').forEach(c => c.classList.remove('selected'));
            chip.classList.add('selected');
            selectedSub = sub;
            document.getElementById('h_main_category').value = catName;
            document.getElementById('h_category').value = sub;
            updateNextBtn();
        };
        subList.appendChild(chip);
    });
}

function resetDemandCategory() {
    selectedCat = null;
    selectedSub = null;
    document.getElementById('demandCatGrid').style.display = 'grid';
    document.getElementById('demandCatSearch').parentElement.style.display = 'block';
    document.getElementById('demandSubSection').style.display = 'none';
    document.getElementById('h_main_category').value = '';
    document.getElementById('h_category').value = '';
    updateNextBtn();
}

function filterDemandCategories(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.demand-cat-btn').forEach(btn => {
        const catName = btn.getAttribute('data-cat').toLowerCase();
        const subs = (categoriesData[btn.getAttribute('data-cat')]?.subcategories || []).join(' ').toLowerCase();
        btn.style.display = (!q || catName.includes(q) || subs.includes(q)) ? '' : 'none';
    });
}

// ─── Step 2: Details ───
function updateDemandCities() {
    const country = document.getElementById('demandCountry').value;
    const cityEl = document.getElementById('demandCity');
    const manualEl = document.getElementById('demandLocation');
    cityEl.innerHTML = '<option value="">-- Ville --</option>';
    manualEl.style.display = 'none';

    if (country && citiesByCountry[country]) {
        cityEl.disabled = false;
        citiesByCountry[country].forEach(city => {
            cityEl.innerHTML += '<option value="' + city + '">' + city + '</option>';
        });
        cityEl.innerHTML += '<option value="__other__">🔤 Autre ville</option>';
    } else {
        cityEl.disabled = true;
    }

    cityEl.onchange = function() {
        if (this.value === '__other__') {
            manualEl.style.display = 'block';
            manualEl.focus();
        } else {
            manualEl.style.display = 'none';
            manualEl.value = '';
        }
    };
}

function selectUrgency(level) {
    document.querySelectorAll('.demand-urgency-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector('[data-urgency="' + level + '"]').classList.add('selected');
    document.getElementById('h_urgency').value = level;
}

function handleDemandPhotos(input) {
    const files = Array.from(input.files);
    const remaining = 2 - demandPhotos.length;
    files.slice(0, remaining).forEach(file => {
        if (file.size <= 5 * 1024 * 1024 && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            demandPhotos.push(file);
        }
    });
    input.value = '';
    renderDemandPhotos();
}

function renderDemandPhotos() {
    const container = document.getElementById('demandPhotoPreviews');
    container.innerHTML = '';
    demandPhotos.forEach((file, idx) => {
        const div = document.createElement('div');
        div.className = 'demand-photo-preview';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = '&times;';
        btn.onclick = () => { demandPhotos.splice(idx, 1); renderDemandPhotos(); updatePhotoInput(); };
        div.appendChild(img);
        div.appendChild(btn);
        container.appendChild(div);
    });
    updatePhotoInput();
}

function updatePhotoInput() {
    const dt = new DataTransfer();
    demandPhotos.forEach(f => dt.items.add(f));
    document.getElementById('demandPhotoInput').files = dt.files;
}

function validateStep2() {
    const title = document.getElementById('demandTitle').value.trim();
    const desc = document.getElementById('demandDesc').value.trim();
    const country = document.getElementById('demandCountry').value;
    const city = document.getElementById('demandCity').value;
    const manual = document.getElementById('demandLocation').value.trim();

    if (!title) { showError('step2Error', 'Le titre est obligatoire.'); document.getElementById('demandTitle').focus(); return false; }
    if (!desc) { showError('step2Error', 'La description est obligatoire.'); document.getElementById('demandDesc').focus(); return false; }
    if (!country) { showError('step2Error', 'Veuillez sélectionner un pays.'); return false; }
    if (!city && !manual) { showError('step2Error', 'Veuillez sélectionner une ville.'); return false; }

    // Copy city to location hidden if needed
    if (city && city !== '__other__' && !manual) {
        document.getElementById('demandLocation').value = city;
    }
    return true;
}

// ─── Step 3: Recap ───
function buildRecap() {
    const title = document.getElementById('demandTitle').value.trim();
    const desc = document.getElementById('demandDesc').value.trim();
    const country = document.getElementById('demandCountry').value;
    const city = document.getElementById('demandCity').value;
    const manual = document.getElementById('demandLocation').value.trim();
    const price = document.getElementById('demandPrice').value;
    const urgency = document.getElementById('h_urgency').value;

    const urgencyLabels = { normal: '🕐 Normal', urgent: '⚡ Urgent', tres_urgent: '🚨 Très urgent' };
    const locationText = manual || city || '';

    let html = `
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-tag" style="color:#6366f1; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Service</small><br><strong style="color:#111827;">${selectedCat} → ${selectedSub}</strong></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-heading" style="color:#3b82f6; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Titre</small><br><strong style="color:#111827;">${title}</strong></div>
            </div>
            <div style="display:flex; align-items:flex-start; gap:10px;">
                <i class="fas fa-align-left" style="color:#10b981; width:20px; text-align:center; margin-top:4px;"></i>
                <div><small style="color:#9ca3af;">Description</small><br><span style="color:#374151; font-size:0.88rem;">${desc.length > 150 ? desc.substring(0, 150) + '...' : desc}</span></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-map-marker-alt" style="color:#ef4444; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Localisation</small><br><strong style="color:#111827;">${locationText}, ${country}</strong></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-clock" style="color:#f59e0b; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Urgence</small><br><strong style="color:#111827;">${urgencyLabels[urgency] || '🕐 Normal'}</strong></div>
            </div>
            ${price ? `<div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-euro-sign" style="color:#22c55e; width:20px; text-align:center;"></i><div><small style="color:#9ca3af;">Budget</small><br><strong style="color:#111827;">${price} €</strong></div></div>` : ''}
            ${demandPhotos.length ? `<div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-camera" style="color:#8b5cf6; width:20px; text-align:center;"></i><div><small style="color:#9ca3af;">Photos</small><br><strong style="color:#111827;">${demandPhotos.length} photo(s)</strong></div></div>` : ''}
        </div>
    `;
    document.getElementById('demandRecap').innerHTML = html;
}

// ─── Helpers ───
function showError(id, msg) {
    const el = document.getElementById(id);
    el.querySelector('span').textContent = msg;
    el.style.display = 'flex';
}
function hideErrors() {
    document.querySelectorAll('.demand-error').forEach(e => e.style.display = 'none');
}

// ─── Init ───
document.addEventListener('DOMContentLoaded', function() {
    updateStepUI();

    // Auto-select country from user profile
    const countryEl = document.getElementById('demandCountry');
    if (countryEl.value) updateDemandCities();

    // Pre-select from URL params
    if (preCategory && categoriesData[preCategory]) {
        selectDemandCategory(preCategory);
        if (preSubcategory) {
            const subs = categoriesData[preCategory]?.subcategories || [];
            if (subs.includes(preSubcategory)) {
                selectedSub = preSubcategory;
                document.getElementById('h_main_category').value = preCategory;
                document.getElementById('h_category').value = preSubcategory;
                setTimeout(() => {
                    document.querySelectorAll('.demand-sub-chip').forEach(c => {
                        if (c.textContent === preSubcategory) c.classList.add('selected');
                    });
                    updateNextBtn();
                }, 50);
            }
        }
    }
});
</script>
@endsection
