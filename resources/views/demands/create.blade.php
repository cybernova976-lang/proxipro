@extends('layouts.app')

@section('title', 'Trouver un professionnel - Prokejem')

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
    gap: 0; margin-bottom: 28px; scroll-margin-top: 110px;
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

.demand-draft-bar {
    display: flex; align-items: center; justify-content: space-between; gap: 14px;
    margin: -10px 0 18px; padding: 11px 14px; border: 1px solid #dbeafe;
    border-radius: 12px; background: #f8fbff; color: #475569; font-size: .84rem;
}
.demand-draft-status { display: inline-flex; align-items: center; gap: 8px; }
.demand-draft-status i { color: #3b82f6; }
.demand-draft-reset { border: 0; background: transparent; color: #2563eb; font-weight: 700; cursor: pointer; }

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

.demand-step-heading { margin-bottom: 22px; }
.demand-step-heading small { display: block; color: #3b82f6; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 5px; }
.demand-step-heading h2 { margin: 0 0 6px; color: #111827; font-size: 1.35rem; font-weight: 800; }
.demand-step-heading p { margin: 0; color: #64748b; font-size: .92rem; }
.demand-choice-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
.demand-choice {
    display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 2px solid #e5e7eb;
    border-radius: 14px; background: #fff; color: #374151; cursor: pointer; text-align: left;
}
.demand-choice:hover { border-color: #93c5fd; background: #f8fbff; }
.demand-choice.selected { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
.demand-choice i { width: 22px; color: #3b82f6; margin-top: 2px; text-align: center; }
.demand-choice strong, .demand-choice span { display: block; }
.demand-choice span { margin-top: 3px; color: #64748b; font-size: .82rem; line-height: 1.4; }
.demand-intake-card {
    margin-bottom: 24px; padding: 18px; border: 1px solid #bfdbfe;
    border-radius: 16px; background: linear-gradient(145deg, #f8fbff, #eef4ff);
}
.demand-intake-card[hidden] { display: none; }
.demand-intake-head { display: flex; align-items: flex-start; gap: 11px; margin-bottom: 16px; }
.demand-intake-icon {
    width: 38px; height: 38px; flex: 0 0 38px; display: grid; place-items: center;
    border-radius: 11px; color: #2563eb; background: #dbeafe;
}
.demand-intake-head strong { display: block; color: #172554; font-size: .94rem; }
.demand-intake-head p { margin: .25rem 0 0; color: #52627a; font-size: .8rem; line-height: 1.45; }
.demand-intake-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.demand-intake-grid .demand-field { margin-bottom: 0; }
.demand-intake-grid .demand-field label { font-size: .84rem; }
.demand-confirm {
    display: flex; align-items: flex-start; gap: 11px; padding: 14px 16px; margin-top: 16px;
    border: 1px solid #cbd5e1; border-radius: 12px; background: #fff;
}
.demand-confirm input { width: 18px; height: 18px; margin-top: 2px; flex: 0 0 auto; }
.demand-confirm label { margin: 0; color: #374151; font-size: .88rem; line-height: 1.45; cursor: pointer; }

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
.demand-step-invalid {
    padding: 10px;
    border-radius: 16px;
    background: #fff7f7;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, .18);
}
.demand-confirm.pk-required-missing {
    border-color: #dc2626;
}

/* Back to category link */
.demand-back-link {
    display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem;
    color: #6b7280; cursor: pointer; margin-bottom: 14px; background: none; border: none; padding: 0;
}
.demand-back-link:hover { color: #3b82f6; }

@media (max-width: 768px) {
    .demand-container { padding: 14px 12px 56px; }
    .demand-hero { padding: 24px 12px 20px; margin-bottom: 16px; }
    .demand-hero-icon { width: 64px; height: 64px; margin-bottom: 14px; font-size: 1.6rem; }
    .demand-hero h1 { font-size: 1.55rem; }
    .demand-hero p { font-size: .94rem; }
    .demand-card-body { padding: 24px 18px; }
    .demand-steps { width: 100%; max-width: 340px; margin-left: auto; margin-right: auto; justify-content: stretch; }
    .demand-step { flex: 0 0 auto; }
    .demand-step-label { display: none; }
    .demand-step-num { width: 32px; height: 32px; font-size: .82rem; }
    .demand-step-line { flex: 1 1 auto; width: auto; margin: 0 5px; }
    .demand-choice-grid { grid-template-columns: 1fr; }
    .demand-intake-grid { grid-template-columns: 1fr; }
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

    @guest
    <div style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:12px;padding:14px 18px;margin-bottom:18px;color:#3730a3;display:flex;gap:12px;align-items:flex-start;">
        <i class="fas fa-info-circle" style="margin-top:3px;"></i>
        <div>
            <strong>Un compte gratuit est nécessaire pour publier.</strong>
            <div style="font-size:.88rem;margin-top:3px;">Vous pouvez préparer votre demande ci-dessous. Connectez-vous avant l'envoi pour suivre les propositions reçues.</div>
            <a href="{{ route('login') }}" style="display:inline-block;margin-top:7px;font-weight:700;color:#3730a3;">Se connecter</a>
            <span aria-hidden="true"> · </span>
            <a href="{{ route('register') }}" style="font-weight:700;color:#3730a3;">Créer un compte</a>
        </div>
    </div>
    @endguest

    <div class="demand-draft-bar" id="demandDraftBar" hidden>
        <span class="demand-draft-status"><i class="fas fa-cloud-check-alt"></i><span id="demandDraftStatus">Brouillon enregistré sur cet appareil</span></span>
        <button type="button" class="demand-draft-reset" id="demandDraftReset">Recommencer</button>
    </div>

    <!-- Steps indicator -->
    <div class="demand-steps" id="demandSteps" aria-label="Progression de la publication">
        @foreach(['Service', 'Lieu & date', 'Détails', 'Budget', 'Vérifier'] as $stepLabel)
            <div class="demand-step">
                <div class="demand-step-num {{ $loop->first ? 'active' : 'pending' }}" id="stepNum{{ $loop->iteration }}">{{ $loop->iteration }}</div>
                <span class="demand-step-label {{ $loop->first ? 'active' : '' }}" id="stepLabel{{ $loop->iteration }}">{{ $stepLabel }}</span>
            </div>
            @unless($loop->last)
                <div class="demand-step-line" id="stepLine{{ $loop->iteration }}"></div>
            @endunless
        @endforeach
    </div>

    <form method="POST" action="{{ route('demand.store') }}" enctype="multipart/form-data" id="demandForm" novalidate>
        @csrf
        <input type="hidden" name="main_category" id="h_main_category">
        <input type="hidden" name="category" id="h_category">
        <input type="hidden" name="urgency" id="h_urgency" value="normal">
        <input type="hidden" name="price_type" id="h_price_type" value="{{ old('price_type', 'negotiable') }}">

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
                    <div class="demand-step-heading">
                        <small>Étape 1 sur 5</small>
                        <h2>Quel service recherchez-vous ?</h2>
                        <p>Choisissez l’activité la plus proche de votre besoin.</p>
                    </div>
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

                <!-- ═══ STEP 2 : Lieu et date ═══ -->
                <div class="demand-section" id="demandStep2">
                    <div class="demand-step-heading">
                        <small>Étape 2 sur 5</small>
                        <h2>Où et quand intervenir ?</h2>
                        <p>Ces informations servent à présenter votre demande aux prestataires disponibles.</p>
                    </div>
                    <div class="demand-selection-badge" id="demandSelectionBadge">
                        <i class="fas fa-tag"></i>
                        <span id="demandSelectionText"></span>
                        <button type="button" onclick="goToDemandStep(1)"><i class="fas fa-pen"></i> Modifier</button>
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-map-marker-alt me-1"></i> Localisation <span class="required">*</span></label>
                        <div class="demand-field-row">
                            <div>
                                <select name="country" id="demandCountry" onchange="updateDemandCities()" required>
                                    <option value="">-- Pays --</option>
                                    @foreach(config('locations.countries', []) as $countryName => $flag)
                                        <option value="{{ $countryName }}" {{ (old('country') ?? Auth::user()->country ?? '') == $countryName ? 'selected' : '' }}>{{ $flag }} {{ $countryName }}</option>
                                    @endforeach
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

                    <div class="demand-field-row">
                        <div class="demand-field">
                            <label for="demandDesiredDate"><i class="fas fa-calendar-day me-1"></i> Date souhaitée <span class="required">*</span></label>
                            <input type="date" name="desired_date" id="demandDesiredDate" min="{{ now()->toDateString() }}" value="{{ old('desired_date') }}" required>
                        </div>
                        <div class="demand-field">
                            <label for="demandTimeWindow"><i class="fas fa-clock me-1"></i> Moment de la journée <span class="required">*</span></label>
                            <select name="time_window" id="demandTimeWindow" required>
                                <option value="">-- Choisir --</option>
                                <option value="flexible" @selected(old('time_window') === 'flexible')>Je suis flexible</option>
                                <option value="morning" @selected(old('time_window') === 'morning')>Matin</option>
                                <option value="afternoon" @selected(old('time_window') === 'afternoon')>Après-midi</option>
                                <option value="evening" @selected(old('time_window') === 'evening')>Soir</option>
                            </select>
                        </div>
                    </div>

                    <div class="demand-error" id="step2Error">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <!-- ═══ STEP 3 : Détails et photos ═══ -->
                <div class="demand-section" id="demandStep3">
                    <div class="demand-step-heading">
                        <small>Étape 3 sur 5</small>
                        <h2>Expliquez simplement votre besoin</h2>
                        <p>Un titre clair et quelques détails suffisent. Les photos restent facultatives.</p>
                    </div>

                    <div class="demand-intake-card" id="demandIntakeCard" hidden>
                        <div class="demand-intake-head">
                            <span class="demand-intake-icon"><i class="fas fa-wand-magic-sparkles"></i></span>
                            <div>
                                <strong>Deux précisions pour mieux vous orienter</strong>
                                <p id="demandIntakeIntroduction"></p>
                            </div>
                        </div>
                        <div class="demand-intake-grid" id="demandIntakeFields"></div>
                    </div>

                    <div class="demand-field">
                        <label for="demandTitle"><i class="fas fa-heading me-1"></i> Titre court <span class="required">*</span></label>
                        <input type="text" name="title" id="demandTitle" placeholder="Ex : Réparer une fuite sous l’évier" maxlength="255" value="{{ old('title') }}" required>
                        <p class="hint">Résumez votre besoin en une phrase.</p>
                    </div>

                    <div class="demand-field">
                        <label for="demandDesc"><i class="fas fa-align-left me-1"></i> Description <span class="required">*</span></label>
                        <textarea name="description" id="demandDesc" maxlength="2000" placeholder="Précisez le problème, les dimensions utiles et le résultat attendu." required>{{ old('description') }}</textarea>
                        <p class="hint"><span id="demandDescCount">0</span>/2000 caractères</p>
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-camera me-1"></i> Photos <span style="color:#9ca3af; font-weight:400;">(facultatif)</span></label>
                        <div class="demand-photo-area" role="button" tabindex="0" id="demandPhotoArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Ajoutez jusqu’à 2 photos pour aider le prestataire à comprendre.</p>
                        </div>
                        <input type="file" id="demandPhotoInput" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleDemandPhotos(this)">
                        <div class="demand-photo-previews" id="demandPhotoPreviews"></div>
                        <p class="hint">Les photos ne sont jamais enregistrées dans le brouillon local.</p>
                    </div>

                    <div class="demand-error" id="step3Error">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <!-- ═══ STEP 4 : Budget et urgence ═══ -->
                <div class="demand-section" id="demandStep4">
                    <div class="demand-step-heading">
                        <small>Étape 4 sur 5</small>
                        <h2>Quel budget avez-vous prévu ?</h2>
                        <p>Vous pouvez indiquer un budget global ou laisser les prestataires proposer leur prix.</p>
                    </div>

                    <div class="demand-choice-grid" aria-label="Type de budget">
                        <button type="button" class="demand-choice" data-price-type="fixed" onclick="selectPriceType('fixed')">
                            <i class="fas fa-wallet"></i>
                            <span><strong>J’ai un budget</strong><span>Indiquer un montant global estimé.</span></span>
                        </button>
                        <button type="button" class="demand-choice selected" data-price-type="negotiable" onclick="selectPriceType('negotiable')">
                            <i class="fas fa-comments-dollar"></i>
                            <span><strong>À discuter</strong><span>Recevoir des propositions chiffrées.</span></span>
                        </button>
                    </div>

                    <div class="demand-field" id="demandPriceField" hidden>
                        <label for="demandPrice"><i class="fas fa-euro-sign me-1"></i> Budget global <span class="required">*</span></label>
                        <input type="number" name="price" id="demandPrice" placeholder="Ex : 150" min="1" step="0.01" value="{{ old('price') }}">
                    </div>

                    <div class="demand-field">
                        <label><i class="fas fa-bolt me-1"></i> Niveau de priorité</label>
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

                    <div class="demand-error" id="step4Error">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <!-- ═══ STEP 5 : Récapitulatif ═══ -->
                <div class="demand-section" id="demandStep5">
                    <div style="text-align:center; margin-bottom:20px;">
                        <small style="display:block;color:#3b82f6;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Étape 5 sur 5</small>
                        <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#22c55e,#16a34a); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:#fff; font-size:1.4rem;">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h3 style="font-size:1.15rem; font-weight:700; color:#111827; margin-bottom:4px;">Vérifiez avant de publier</h3>
                        <p style="font-size:0.85rem; color:#6b7280;">Vous pourrez encore modifier votre demande après sa publication.</p>
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
                                    <li>Prokejem recherche les prestataires compatibles</li>
                                    <li>Votre demande reste visible même si aucun profil n’est trouvé immédiatement</li>
                                    <li>Vous suivez ensuite les propositions depuis votre feed</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="demand-confirm">
                        <input type="checkbox" name="publication_confirmed" id="publicationConfirmed" value="1" @checked(old('publication_confirmed')) required>
                        <label for="publicationConfirmed">Je confirme que les informations de cette demande sont exactes et j’accepte sa publication publique pendant 30 jours.</label>
                    </div>

                    <div class="demand-error" id="step5Error">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
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
                        <i class="fas fa-{{ auth()->check() ? 'paper-plane' : 'sign-in-alt' }}"></i>
                        <span class="demand-btn-submit-text">
                            @guest
                            <span>Continuer</span>
                            <span>Connexion ou inscription</span>
                            @else
                            <span>Publier</span>
                            <span>et trouver des pros</span>
                            @endguest
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
const intakeSchemas = @json($intakeSchemas);
const oldServiceDetails = @json(old('service_details', []));
const preCategory = @json($preCategory);
const preSubcategory = @json($preSubcategory);
const initialCategory = @json(old('main_category', $preCategory));
const initialSubcategory = @json(old('category', $preSubcategory));
const validationErrorKeys = @json($errors->keys());
const validationErrors = @json($errors->toArray());
const hasServerInput = @json(session()->hasOldInput());
const isGuestDemand = @json(Auth::guest());
const guestDemandLoginUrl = @json(route('login'));
const demandDraftIdentity = @json(Auth::id() ?: 'guest');
const demandDraftKey = 'prokejem-demand-draft-v2-' + demandDraftIdentity;
const guestDemandDraftKey = 'prokejem-demand-draft-v2-guest';
const totalDemandSteps = 5;

let currentStep = 1;
let selectedCat = null;
let selectedSub = null;
let demandPhotos = [];
let draftSaveTimer = null;

const citiesByCountry = @json(config('locations.cities', []));

// ─── Step navigation ───
function updateStepUI() {
    for (let i = 1; i <= totalDemandSteps; i++) {
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
    for (let i = 1; i < totalDemandSteps; i++) {
        document.getElementById('stepLine' + i).classList.toggle('done', i < currentStep);
    }

    document.querySelectorAll('.demand-section').forEach(s => s.classList.remove('active'));
    document.getElementById('demandStep' + currentStep).classList.add('active');

    document.getElementById('demandBtnBack').style.visibility = currentStep > 1 ? 'visible' : 'hidden';
    document.getElementById('demandBtnNext').style.display = currentStep < totalDemandSteps ? 'inline-flex' : 'none';
    document.getElementById('demandBtnSubmit').style.display = currentStep === totalDemandSteps ? 'inline-flex' : 'none';
}

function goToDemandStep(step) {
    currentStep = Math.max(1, Math.min(totalDemandSteps, step));
    updateStepUI();
    updateNextBtn();
    scrollDemandFormIntoView();
}

function nextDemandStep() {
    hideErrors();
    const validators = {
        1: validateStep1,
        2: validateStep2,
        3: validateStep3,
        4: validateStep4,
    };

    if (validators[currentStep] && !validators[currentStep]()) return;

    if (currentStep === 1) {
        document.getElementById('demandSelectionText').textContent = selectedCat + ' → ' + selectedSub;
    }

    currentStep++;
    if (currentStep === totalDemandSteps) buildRecap();
    updateStepUI();
    updateNextBtn();
    scheduleDraftSave();
    scrollDemandFormIntoView();
}

function prevDemandStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepUI();
        updateNextBtn();
        scrollDemandFormIntoView();
    }
}

function scrollDemandFormIntoView() {
    document.getElementById('demandSteps').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function updateNextBtn() {
    const btn = document.getElementById('demandBtnNext');
    btn.disabled = false;
}

function markDemandFields(fields, errorId, summary) {
    fields.forEach(item => {
        if (window.ProkejemFormValidation?.mark) {
            window.ProkejemFormValidation.mark(item.field, item.message);
        } else {
            item.field.classList.add('pk-field-invalid');
            item.field.setAttribute('aria-invalid', 'true');
        }
    });
    showError(errorId, summary);
    const first = fields[0]?.field;
    if (first) {
        (first.closest('.demand-field, .demand-confirm') || first).scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => first.focus({ preventScroll: true }), 220);
    }
    return false;
}

function validateStep1() {
    if (selectedSub) return true;
    const selectionArea = selectedCat
        ? document.getElementById('demandSubList')
        : document.getElementById('demandCatGrid');
    selectionArea.classList.add('demand-step-invalid');
    showError('step1Error', 'Veuillez sélectionner un service.');
    selectionArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return false;
}

// ─── Step 1: Categories ───
function selectDemandCategory(catName, subcategoryToSelect = null) {
    if (!categoriesData[catName]) return;
    document.getElementById('demandCatGrid').classList.remove('demand-step-invalid');
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
            document.getElementById('demandSubList').classList.remove('demand-step-invalid');
            selectedSub = sub;
            document.getElementById('h_main_category').value = catName;
            document.getElementById('h_category').value = sub;
            renderDemandIntake();
            updateNextBtn();
            scheduleDraftSave();
        };
        subList.appendChild(chip);
    });

    if (subcategoryToSelect && subs.includes(subcategoryToSelect)) {
        selectedSub = subcategoryToSelect;
        document.getElementById('h_main_category').value = catName;
        document.getElementById('h_category').value = subcategoryToSelect;
        Array.from(subList.children).find(chip => chip.textContent === subcategoryToSelect)?.classList.add('selected');
        renderDemandIntake();
    }

    updateNextBtn();
}

function resetDemandCategory() {
    selectedCat = null;
    selectedSub = null;
    document.getElementById('demandCatGrid').style.display = 'grid';
    document.getElementById('demandCatSearch').parentElement.style.display = 'block';
    document.getElementById('demandSubSection').style.display = 'none';
    document.getElementById('h_main_category').value = '';
    document.getElementById('h_category').value = '';
    document.getElementById('demandIntakeCard').hidden = true;
    document.getElementById('demandIntakeFields').innerHTML = '';
    updateNextBtn();
    scheduleDraftSave();
}

function filterDemandCategories(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.demand-cat-btn').forEach(btn => {
        const catName = btn.getAttribute('data-cat').toLowerCase();
        const subs = (categoriesData[btn.getAttribute('data-cat')]?.subcategories || []).join(' ').toLowerCase();
        btn.style.display = (!q || catName.includes(q) || subs.includes(q)) ? '' : 'none';
    });
}

function renderDemandIntake(values = {}) {
    const card = document.getElementById('demandIntakeCard');
    const container = document.getElementById('demandIntakeFields');
    const schema = intakeSchemas[selectedCat];
    const fields = schema?.fields || {};
    container.innerHTML = '';

    if (!selectedSub || !Object.keys(fields).length) {
        card.hidden = true;
        return;
    }

    document.getElementById('demandIntakeIntroduction').textContent = schema.introduction || '';

    Object.entries(fields).forEach(([key, field]) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'demand-field';

        const label = document.createElement('label');
        const inputId = 'demandServiceDetail_' + key;
        label.htmlFor = inputId;
        label.textContent = field.label || key;
        if (field.required) {
            const required = document.createElement('span');
            required.className = 'required';
            required.textContent = ' *';
            label.appendChild(required);
        }

        let input;
        if (field.type === 'select') {
            input = document.createElement('select');
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = field.placeholder || '-- Choisir --';
            input.appendChild(placeholder);
            Object.entries(field.options || {}).forEach(([value, text]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                input.appendChild(option);
            });
        } else {
            input = document.createElement('input');
            input.type = field.type === 'number' ? 'number' : 'text';
            if (field.min !== undefined) input.min = field.min;
            if (field.max !== undefined) input.max = field.max;
            if (field.step !== undefined) input.step = field.step;
            if (field.maxlength !== undefined) input.maxLength = field.maxlength;
            input.placeholder = field.placeholder || '';
        }

        input.id = inputId;
        input.name = `service_details[${key}]`;
        input.dataset.serviceDetail = key;
        input.required = Boolean(field.required);
        input.value = values[key] ?? '';
        input.addEventListener('change', scheduleDraftSave);
        wrapper.appendChild(label);
        wrapper.appendChild(input);
        container.appendChild(wrapper);
    });

    card.hidden = false;
}

function currentServiceDetails() {
    const details = {};
    document.querySelectorAll('[data-service-detail]').forEach(input => {
        if (input.value !== '') details[input.dataset.serviceDetail] = input.value;
    });
    return details;
}

function setServiceDetails(values = {}) {
    document.querySelectorAll('[data-service-detail]').forEach(input => {
        input.value = values[input.dataset.serviceDetail] ?? '';
    });
}

// ─── Step 2: Location and date ───
function updateDemandCities(cityToSelect = null) {
    const country = document.getElementById('demandCountry').value;
    const cityEl = document.getElementById('demandCity');
    const manualEl = document.getElementById('demandLocation');
    cityEl.innerHTML = '<option value="">-- Ville --</option>';
    manualEl.style.display = 'none';
    manualEl.required = false;
    cityEl.required = Boolean(country && citiesByCountry[country]);

    if (country && citiesByCountry[country]) {
        cityEl.disabled = false;
        citiesByCountry[country].forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            cityEl.appendChild(option);
        });
        const otherOption = document.createElement('option');
        otherOption.value = '__other__';
        otherOption.textContent = '🔤 Autre ville';
        cityEl.appendChild(otherOption);
        if (cityToSelect && Array.from(cityEl.options).some(option => option.value === cityToSelect)) {
            cityEl.value = cityToSelect;
        } else if (cityToSelect) {
            cityEl.value = '__other__';
            manualEl.style.display = 'block';
            manualEl.required = true;
        }
    } else {
        cityEl.disabled = true;
    }

    cityEl.onchange = function() {
        if (this.value === '__other__') {
            manualEl.style.display = 'block';
            manualEl.required = true;
            manualEl.focus();
        } else {
            manualEl.style.display = 'none';
            manualEl.required = false;
            manualEl.value = '';
        }
        scheduleDraftSave();
    };
}

function selectUrgency(level) {
    document.querySelectorAll('.demand-urgency-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector('[data-urgency="' + level + '"]').classList.add('selected');
    document.getElementById('h_urgency').value = level;
    scheduleDraftSave();
}

function selectPriceType(type) {
    document.querySelectorAll('[data-price-type]').forEach(button => {
        button.classList.toggle('selected', button.dataset.priceType === type);
    });
    document.getElementById('h_price_type').value = type;
    document.getElementById('demandPriceField').hidden = type !== 'fixed';
    document.getElementById('demandPrice').required = type === 'fixed';
    scheduleDraftSave();
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
    const country = document.getElementById('demandCountry').value;
    const city = document.getElementById('demandCity').value;
    const manual = document.getElementById('demandLocation').value.trim();
    const desiredDate = document.getElementById('demandDesiredDate').value;
    const timeWindow = document.getElementById('demandTimeWindow').value;
    const missing = [];

    if (!country) missing.push({ field: document.getElementById('demandCountry'), message: 'Sélectionnez un pays.' });
    if (!city && !manual) missing.push({ field: document.getElementById('demandCity'), message: 'Sélectionnez une ville.' });
    if (city === '__other__' && !manual) missing.push({ field: document.getElementById('demandLocation'), message: 'Saisissez votre ville.' });
    if (!desiredDate) missing.push({ field: document.getElementById('demandDesiredDate'), message: 'Indiquez la date souhaitée.' });
    if (!timeWindow) missing.push({ field: document.getElementById('demandTimeWindow'), message: 'Choisissez un moment de la journée.' });
    if (missing.length) return markDemandFields(missing, 'step2Error', 'Complétez les informations obligatoires indiquées en rouge.');
    if (desiredDate < document.getElementById('demandDesiredDate').min) {
        return markDemandFields([
            { field: document.getElementById('demandDesiredDate'), message: 'La date souhaitée ne peut pas être dans le passé.' },
        ], 'step2Error', 'Corrigez la date souhaitée.');
    }

    // Copy city to location hidden if needed
    if (city && city !== '__other__' && !manual) {
        document.getElementById('demandLocation').value = city;
    }
    return true;
}

function validateStep3() {
    const title = document.getElementById('demandTitle').value.trim();
    const desc = document.getElementById('demandDesc').value.trim();
    const missing = [];
    if (!title) missing.push({ field: document.getElementById('demandTitle'), message: 'Le titre est obligatoire.' });
    if (!desc) missing.push({ field: document.getElementById('demandDesc'), message: 'La description est obligatoire.' });
    Array.from(document.querySelectorAll('[data-service-detail][required]'))
        .filter(input => !input.value)
        .forEach(input => missing.push({ field: input, message: 'Cette précision est obligatoire.' }));
    if (missing.length) return markDemandFields(missing, 'step3Error', 'Complétez les informations obligatoires indiquées en rouge.');
    return true;
}

function validateStep4() {
    const priceType = document.getElementById('h_price_type').value;
    const price = Number(document.getElementById('demandPrice').value);
    if (priceType === 'fixed' && (!Number.isFinite(price) || price < 1)) {
        return markDemandFields([
            { field: document.getElementById('demandPrice'), message: 'Indiquez un budget supérieur à 0 €.' },
        ], 'step4Error', 'Indiquez un budget valide, ou choisissez « À discuter ».');
    }
    return true;
}

function validateStep5() {
    const confirmation = document.getElementById('publicationConfirmed');
    if (confirmation.checked) return true;

    return markDemandFields([
        { field: confirmation, message: 'Cette confirmation est obligatoire avant de continuer.' },
    ], 'step5Error', 'Confirmez les informations avant de continuer.');
}

// ─── Step 5: Recap ───
function buildRecap() {
    const title = document.getElementById('demandTitle').value.trim();
    const desc = document.getElementById('demandDesc').value.trim();
    const country = document.getElementById('demandCountry').value;
    const city = document.getElementById('demandCity').value;
    const manual = document.getElementById('demandLocation').value.trim();
    const price = document.getElementById('demandPrice').value;
    const priceType = document.getElementById('h_price_type').value;
    const urgency = document.getElementById('h_urgency').value;
    const desiredDate = document.getElementById('demandDesiredDate').value;
    const timeWindow = document.getElementById('demandTimeWindow').value;

    const urgencyLabels = { normal: '🕐 Normal', urgent: '⚡ Urgent', tres_urgent: '🚨 Très urgent' };
    const timeLabels = { flexible: 'Flexible', morning: 'Matin', afternoon: 'Après-midi', evening: 'Soir' };
    const locationText = manual || city || '';
    const formattedDate = desiredDate ? new Intl.DateTimeFormat('fr-FR').format(new Date(desiredDate + 'T12:00:00')) : '';
    const safe = escapeHtml;
    const serviceDetailRows = Object.entries(serviceDetails).map(([key, value]) => {
        const field = intakeFields[key] || {};
        const displayValue = field.options?.[value] || value;
        return `<div style="display:flex;align-items:flex-start;gap:10px;"><i class="fas fa-circle-check" style="color:#2563eb;width:20px;text-align:center;margin-top:4px;"></i><div><small style="color:#9ca3af;">${safe(field.label || key)}</small><br><strong style="color:#111827;">${safe(displayValue)}</strong></div></div>`;
    }).join('');

    let html = `
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-tag" style="color:#6366f1; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Service</small><br><strong style="color:#111827;">${safe(selectedCat)} → ${safe(selectedSub)}</strong></div>
            </div>
            ${serviceDetailRows}
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-heading" style="color:#3b82f6; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Titre</small><br><strong style="color:#111827;">${safe(title)}</strong></div>
            </div>
            <div style="display:flex; align-items:flex-start; gap:10px;">
                <i class="fas fa-align-left" style="color:#10b981; width:20px; text-align:center; margin-top:4px;"></i>
                <div><small style="color:#9ca3af;">Description</small><br><span style="color:#374151; font-size:0.88rem;">${safe(desc.length > 150 ? desc.substring(0, 150) + '…' : desc)}</span></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-map-marker-alt" style="color:#ef4444; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Localisation</small><br><strong style="color:#111827;">${safe(locationText)}, ${safe(country)}</strong></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-calendar-day" style="color:#0ea5e9; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Intervention souhaitée</small><br><strong style="color:#111827;">${safe(formattedDate)} · ${safe(timeLabels[timeWindow] || '')}</strong></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-clock" style="color:#f59e0b; width:20px; text-align:center;"></i>
                <div><small style="color:#9ca3af;">Urgence</small><br><strong style="color:#111827;">${urgencyLabels[urgency] || '🕐 Normal'}</strong></div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-euro-sign" style="color:#22c55e; width:20px; text-align:center;"></i><div><small style="color:#9ca3af;">Budget</small><br><strong style="color:#111827;">${priceType === 'fixed' ? safe(price) + ' €' : 'À discuter'}</strong></div></div>
            ${demandPhotos.length ? `<div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-camera" style="color:#8b5cf6; width:20px; text-align:center;"></i><div><small style="color:#9ca3af;">Photos</small><br><strong style="color:#111827;">${demandPhotos.length} photo(s)</strong></div></div>` : ''}
        </div>
    `;
    document.getElementById('demandRecap').innerHTML = html;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

// ─── Local draft (text fields only; never photos) ───
function draftPayload() {
    return {
        version: 2,
        savedAt: new Date().toISOString(),
        main_category: selectedCat,
        category: selectedSub,
        country: document.getElementById('demandCountry').value,
        city: document.getElementById('demandCity').value,
        location: document.getElementById('demandLocation').value,
        desired_date: document.getElementById('demandDesiredDate').value,
        time_window: document.getElementById('demandTimeWindow').value,
        title: document.getElementById('demandTitle').value,
        description: document.getElementById('demandDesc').value,
        price_type: document.getElementById('h_price_type').value,
        price: document.getElementById('demandPrice').value,
        urgency: document.getElementById('h_urgency').value,
        service_details: currentServiceDetails(),
    };
}

function scheduleDraftSave() {
    window.clearTimeout(draftSaveTimer);
    draftSaveTimer = window.setTimeout(saveDemandDraft, 350);
}

function saveDemandDraft() {
    try {
        const payload = draftPayload();
        const hasContent = payload.main_category || payload.title.trim() || payload.description.trim() || payload.desired_date;
        if (!hasContent) return;
        localStorage.setItem(demandDraftKey, JSON.stringify(payload));
        showDraftStatus('Brouillon enregistré sur cet appareil');
    } catch (error) {
        // Le formulaire reste pleinement utilisable si le stockage local est indisponible.
    }
}

function loadDemandDraft() {
    try {
        let raw = localStorage.getItem(demandDraftKey);
        if (!raw && demandDraftIdentity !== 'guest') {
            raw = localStorage.getItem(guestDemandDraftKey);
            if (raw) {
                localStorage.setItem(demandDraftKey, raw);
                localStorage.removeItem(guestDemandDraftKey);
            }
        }
        return raw ? JSON.parse(raw) : null;
    } catch (error) {
        return null;
    }
}

function restoreDemandDraft(draft) {
    if (!draft || draft.version !== 2) return false;
    if (draft.main_category && categoriesData[draft.main_category]) {
        selectDemandCategory(draft.main_category, draft.category);
        setServiceDetails(draft.service_details || {});
    }
    document.getElementById('demandCountry').value = draft.country || '';
    updateDemandCities(draft.city || null);
    document.getElementById('demandLocation').value = draft.location || '';
    if (draft.city === '__other__' || (draft.location && !draft.city)) {
        document.getElementById('demandLocation').style.display = 'block';
        document.getElementById('demandLocation').required = true;
    }
    document.getElementById('demandDesiredDate').value = draft.desired_date || '';
    document.getElementById('demandTimeWindow').value = draft.time_window || '';
    document.getElementById('demandTitle').value = draft.title || '';
    document.getElementById('demandDesc').value = draft.description || '';
    document.getElementById('demandPrice').value = draft.price || '';
    selectPriceType(draft.price_type === 'fixed' ? 'fixed' : 'negotiable');
    selectUrgency(['normal', 'urgent', 'tres_urgent'].includes(draft.urgency) ? draft.urgency : 'normal');
    updateDescriptionCount();
    showDraftStatus('Brouillon restauré · vérifiez les informations avant de publier');
    return true;
}

function showDraftStatus(message) {
    document.getElementById('demandDraftStatus').textContent = message;
    document.getElementById('demandDraftBar').hidden = false;
}

function clearDemandDraft() {
    try {
        localStorage.removeItem(demandDraftKey);
        localStorage.removeItem(guestDemandDraftKey);
    } catch (error) {}
}

function updateDescriptionCount() {
    document.getElementById('demandDescCount').textContent = document.getElementById('demandDesc').value.length;
}

function firstStepForValidationErrors() {
    if (validationErrorKeys.some(key => ['main_category', 'category'].includes(key))) return 1;
    if (validationErrorKeys.some(key => ['country', 'city', 'location', 'desired_date', 'time_window'].includes(key))) return 2;
    if (validationErrorKeys.some(key => ['title', 'description', 'photos', 'photos.0', 'photos.1'].includes(key) || key.startsWith('service_details'))) return 3;
    if (validationErrorKeys.some(key => ['price_type', 'price', 'urgency'].includes(key))) return 4;
    if (validationErrorKeys.includes('publication_confirmed')) return 5;
    return 1;
}

function fieldForValidationError(key) {
    const fields = {
        country: 'demandCountry',
        city: 'demandCity',
        location: 'demandLocation',
        desired_date: 'demandDesiredDate',
        time_window: 'demandTimeWindow',
        title: 'demandTitle',
        description: 'demandDesc',
        price: 'demandPrice',
        publication_confirmed: 'publicationConfirmed',
    };
    if (fields[key]) return document.getElementById(fields[key]);
    if (key.startsWith('service_details.')) {
        return document.querySelector('[data-service-detail="' + key.split('.').slice(1).join('.') + '"]');
    }
    return null;
}

function applyServerValidationHighlights() {
    validationErrorKeys.forEach(key => {
        if (['main_category', 'category'].includes(key)) {
            (selectedCat ? document.getElementById('demandSubList') : document.getElementById('demandCatGrid'))
                .classList.add('demand-step-invalid');
            return;
        }
        if (key === 'photos' || key.startsWith('photos.')) {
            document.getElementById('demandPhotoArea').classList.add('demand-step-invalid');
            return;
        }
        const field = fieldForValidationError(key);
        if (field) window.ProkejemFormValidation?.mark(field, validationErrors[key]?.[0]);
    });
}

// ─── Helpers ───
function showError(id, msg) {
    const el = document.getElementById(id);
    el.querySelector('span').textContent = msg;
    el.style.display = 'flex';
}
function hideErrors() {
    document.querySelectorAll('.demand-error').forEach(e => e.style.display = 'none');
    document.querySelectorAll('.demand-step-invalid').forEach(e => e.classList.remove('demand-step-invalid'));
    document.querySelectorAll('.pk-field-invalid').forEach(field => window.ProkejemFormValidation?.clear(field));
}

// ─── Init ───
document.addEventListener('DOMContentLoaded', function() {
    const countryEl = document.getElementById('demandCountry');
    const oldCity = @json(old('city'));
    const storedDraft = !hasServerInput ? loadDemandDraft() : null;
    const draft = storedDraft && (!preCategory || storedDraft.main_category === preCategory)
        ? storedDraft
        : null;

    if (draft) {
        restoreDemandDraft(draft);
    } else {
        if (countryEl.value) updateDemandCities(oldCity);
        if (initialCategory && categoriesData[initialCategory]) {
            selectDemandCategory(initialCategory, initialSubcategory);
            setServiceDetails(oldServiceDetails);
        }
        selectPriceType(@json(old('price_type', 'negotiable')));
        selectUrgency(@json(old('urgency', 'normal')));
    }

    currentStep = validationErrorKeys.length ? firstStepForValidationErrors() : 1;
    updateStepUI();
    updateNextBtn();
    updateDescriptionCount();
    if (validationErrorKeys.length) applyServerValidationHighlights();

    const form = document.getElementById('demandForm');
    form.addEventListener('input', event => {
        if (event.target.id === 'demandDesc') updateDescriptionCount();
        scheduleDraftSave();
    });
    form.addEventListener('change', scheduleDraftSave);
    form.addEventListener('submit', event => {
        if (isGuestDemand) {
            event.preventDefault();
            saveDemandDraft();
            window.location.assign(guestDemandLoginUrl);
            return;
        }

        if (!validateStep5()) {
            event.preventDefault();
            currentStep = totalDemandSteps;
            updateStepUI();
            return;
        }
    });

    document.getElementById('demandDraftReset').addEventListener('click', () => {
        clearDemandDraft();
        window.location.reload();
    });

    const photoArea = document.getElementById('demandPhotoArea');
    photoArea.addEventListener('click', () => document.getElementById('demandPhotoInput').click());
    photoArea.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            document.getElementById('demandPhotoInput').click();
        }
    });
});
</script>
@endsection
