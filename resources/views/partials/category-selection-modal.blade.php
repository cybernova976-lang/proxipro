{{-- Modal d'onboarding professionnel --}}
{{-- Affiché après connexion si le professionnel n'a pas encore configuré ses catégories --}}
{{-- Utilise une vérification intelligente qui prend en compte TOUTES les sources de catégories --}}
@if(auth()->check() && auth()->user()->shouldShowCategorySelectionModal())

<style>
/* ======= ONBOARDING MODAL STYLES ======= */
.onb-overlay {
    position: fixed; inset: 0; z-index: 99999;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
}
.onb-modal {
    background: #fff; border-radius: 18px; box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    width: 100%; max-width: 680px; margin: 16px; max-height: 90vh;
    display: flex; flex-direction: column; overflow: hidden;
    animation: onbFadeIn 0.35s ease-out;
}
@keyframes onbFadeIn {
    from { opacity: 0; transform: scale(0.94) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.onb-header { padding: 24px 28px 16px; border-bottom: 1px solid #f0f0f0; }
.onb-header-top { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
.onb-header-icon {
    width: 44px; height: 44px; border-radius: 12px; background: #eef2ff;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.onb-header-icon i { font-size: 1.2rem; color: #3a86ff; }
.onb-header h2 { font-size: 1.15rem; font-weight: 700; color: #1a1a1a; margin: 0; }
.onb-header p { font-size: 0.82rem; color: #888; margin: 4px 0 0; }

/* Stepper */
.onb-stepper { display: flex; align-items: center; gap: 6px; }
.onb-step { display: flex; align-items: center; gap: 6px; }
.onb-step-num {
    width: 28px; height: 28px; border-radius: 50%; font-size: 0.75rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.onb-step-num.active { background: #3a86ff; color: #fff; }
.onb-step-num.done { background: #22c55e; color: #fff; }
.onb-step-num.pending { background: #e5e7eb; color: #9ca3af; }
.onb-step span { font-size: 0.82rem; font-weight: 600; }
.onb-step span.active-label { color: #3a86ff; }
.onb-step span.done-label { color: #22c55e; }
.onb-step span.pending-label { color: #9ca3af; }
.onb-stepper-line { flex: 1; height: 1px; background: #e5e7eb; }

/* Body */
.onb-body { flex: 1; overflow-y: auto; padding: 20px 28px; }

/* Category grid */
.onb-cat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
@media (max-width: 576px) { .onb-cat-grid { grid-template-columns: repeat(2, 1fr); } }
.onb-cat-btn {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 14px 8px; border-radius: 12px; border: 2px solid #e5e7eb;
    background: #fff; cursor: pointer; transition: all 0.2s; text-align: center;
}
.onb-cat-btn:hover { border-color: #c7d2fe; background: #fafbff; }
.onb-cat-btn.selected { border-color: #3a86ff; background: #eef2ff; }
.onb-cat-btn .onb-cat-icon { font-size: 1.6rem; }
.onb-cat-btn .onb-cat-name { font-size: 0.72rem; font-weight: 600; color: #374151; line-height: 1.2; }
.onb-cat-btn.selected .onb-cat-name { color: #3a86ff; }

/* Subcategory chips */
.onb-sub-list { display: flex; flex-wrap: wrap; gap: 8px; }
.onb-sub-chip {
    padding: 7px 14px; border-radius: 20px; border: 1.5px solid #e5e7eb;
    font-size: 0.82rem; font-weight: 500; color: #6b7280; background: #fff;
    cursor: pointer; transition: all 0.2s;
}
.onb-sub-chip:hover { border-color: #93c5fd; background: #f0f7ff; }
.onb-sub-chip.selected { border-color: #3a86ff; background: #eef2ff; color: #3a86ff; }

/* Back button */
.onb-back-btn {
    display: inline-flex; align-items: center; gap: 4px; background: none; border: none;
    color: #9ca3af; font-size: 0.85rem; cursor: pointer; padding: 0; margin-bottom: 12px;
}
.onb-back-btn:hover { color: #6b7280; }

/* Location inputs */
.onb-input {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
    font-size: 0.88rem; color: #374151; outline: none; transition: border-color 0.2s;
}
.onb-input:focus { border-color: #3a86ff; box-shadow: 0 0 0 3px rgba(58,134,255,0.1); }
.onb-input-label { display: block; font-size: 0.82rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
.onb-input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
@media (max-width: 576px) { .onb-input-row { grid-template-columns: 1fr; } }
.onb-geo-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px;
    border: 1.5px solid #3a86ff; border-radius: 10px; background: #eef2ff;
    color: #3a86ff; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.onb-geo-btn:hover { background: #3a86ff; color: #fff; }

/* Count badge */
.onb-count { font-size: 0.78rem; font-weight: 600; color: #22c55e; margin-top: 10px; }

/* Footer */
.onb-footer {
    padding: 16px 28px; border-top: 1px solid #f0f0f0; background: #fafafa;
    display: flex; align-items: center; justify-content: space-between;
}
.onb-skip { font-size: 0.82rem; color: #9ca3af; text-decoration: underline; cursor: pointer; background: none; border: none; }
.onb-skip:hover { color: #6b7280; }
.onb-save-btn {
    padding: 10px 28px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #3a86ff, #2667cc); color: #fff;
    font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.onb-save-btn:hover { background: linear-gradient(135deg, #2667cc, #1a4a99); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(58,134,255,0.35); }
.onb-save-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }

/* Toast */
.onb-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 100000;
    background: #22c55e; color: #fff; padding: 12px 22px; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2); font-size: 0.88rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    animation: onbFadeIn 0.3s ease-out;
}
</style>

<div class="onb-overlay" id="onb-overlay">
    <div class="onb-modal">
        {{-- Header --}}
        <div class="onb-header">
            <div class="onb-header-top">
                <div class="onb-header-icon"><i class="fas fa-rocket"></i></div>
                <div>
                    <h2>Bienvenue ! Configurez votre profil</h2>
                    <p>3 étapes rapides pour être visible par les clients</p>
                </div>
            </div>
            <div class="onb-stepper">
                <div class="onb-step" id="onb-si-1">
                    <div class="onb-step-num active">1</div>
                    <span class="active-label">Catégorie</span>
                </div>
                <div class="onb-stepper-line"></div>
                <div class="onb-step" id="onb-si-2">
                    <div class="onb-step-num pending">2</div>
                    <span class="pending-label">Spécialités</span>
                </div>
                <div class="onb-stepper-line"></div>
                <div class="onb-step" id="onb-si-3">
                    <div class="onb-step-num pending">3</div>
                    <span class="pending-label">Localisation</span>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="onb-body">
            {{-- Step 1: Catégorie --}}
            <div id="onb-step-1">
                <p style="font-size:0.85rem; color:#888; margin-bottom:14px;">Sélectionnez votre domaine d'activité principal :</p>
                <div class="onb-cat-grid" id="onb-cat-grid"></div>
            </div>

            {{-- Step 2: Sous-catégories --}}
            <div id="onb-step-2" style="display:none;">
                <button type="button" class="onb-back-btn" onclick="onbGoBack(1)">
                    <i class="fas fa-chevron-left"></i> Retour
                </button>
                <p style="font-size:0.85rem; color:#888; margin-bottom:14px;">
                    Sélectionnez vos spécialités dans <strong id="onb-cat-name" style="color:#3a86ff;"></strong> :
                </p>
                <div style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd; border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.8rem; color: #1e40af; display: flex; align-items: flex-start; gap: 8px;">
                    <i class="fas fa-info-circle" style="margin-top: 1px; flex-shrink: 0;"></i>
                    <span>Ces informations s'ajouteront à votre profil existant. Vos données précédentes seront conservées.</span>
                </div>
                <div class="onb-sub-list" id="onb-sub-list"></div>
                <div class="onb-count" id="onb-sub-count" style="display:none;"></div>
            </div>

            {{-- Step 3: Localisation --}}
            <div id="onb-step-3" style="display:none;">
                <button type="button" class="onb-back-btn" onclick="onbGoBack(2)">
                    <i class="fas fa-chevron-left"></i> Retour
                </button>
                <p style="font-size:0.85rem; color:#888; margin-bottom:16px;">
                    Indiquez votre zone d'intervention pour apparaître dans les recherches locales :
                </p>

                <div style="margin-bottom:16px;">
                    <button type="button" class="onb-geo-btn" id="onb-geo-btn" onclick="onbGeolocate()">
                        <i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement
                    </button>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="onb-input-label">Adresse / Ville *</label>
                    <input type="text" class="onb-input" id="onb-address" placeholder="Ex: 12 rue de la Paix, 75001 Paris" value="{{ auth()->user()->address ?? '' }}">
                </div>

                <div class="onb-input-row">
                    <div>
                        <label class="onb-input-label">Ville</label>
                        <input type="text" class="onb-input" id="onb-city" placeholder="Paris" value="{{ auth()->user()->city ?? '' }}">
                    </div>
                    <div>
                        <label class="onb-input-label">Rayon d'intervention (km)</label>
                        <input type="number" class="onb-input" id="onb-radius" placeholder="30" min="1" max="200" value="{{ auth()->user()->pro_intervention_radius ?? 30 }}">
                    </div>
                </div>

                <div id="onb-geo-status" style="font-size:0.8rem; margin-top:10px; display:none;"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="onb-footer">
            <button type="button" class="onb-skip" onclick="onbSkip()">Passer pour le moment</button>
            <button type="button" class="onb-save-btn" id="onb-next-btn" onclick="onbNext()" disabled>
                Continuer <i class="fas fa-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const catsData = {
        'Bricolage & Travaux': {
            icon: '🔧',
            subs: ['Plombier','Électricien','Peintre en bâtiment','Menuisier','Carreleur','Maçon','Serrurier','Climaticien','Chauffagiste','Couvreur / Zingueur','Plaquiste','Installateur panneaux solaires','Spécialiste rénovation','Poseur de parquet','Façadier','Vitrier','Charpentier','Isolation thermique','Domoticien','Installateur alarmes','Plâtrier','Ferronnier','Terrassier']
        },
        'Jardinage': {
            icon: '🌿',
            subs: ['Jardinier','Paysagiste','Élagueur','Pisciniste','Spécialiste arrosage','Entretien espaces verts','Arboriste','Tonte de pelouse','Taille de haies','Création de jardins','Engazonnement','Clôturier','Maçon paysagiste','Installateur terrasse bois','Spécialiste permaculture']
        },
        'Nettoyage & Foyer': {
            icon: '🧹',
            subs: ['Agent de nettoyage','Femme / Homme de ménage','Nettoyeur fin de chantier','Repasseur / Repasseuse','Couturier / Couturière','Vitrier','Laveur de vitres','Nettoyage de toiture','Nettoyage de moquette','Détachage spécialisé','Nettoyage après sinistre','Désinfection','Nettoyage de copropriété','Gardien / Concierge','Blanchisserie à domicile']
        },
        'Aide à domicile': {
            icon: '🤝',
            subs: ['Baby-sitter','Aide soignant(e)','Nounou','Accompagnateur scolaire','Livreur courses','Cuisinier à domicile','Assistant administratif','Aide aux personnes âgées','Auxiliaire de vie','Pet-sitter','Promeneur de chiens','Garde de maison','Homme / Femme toutes mains','Assistant personnel','Aide au déplacement','Soutien scolaire à domicile']
        },
        'Cours & Formation': {
            icon: '📚',
            subs: ['Professeur particulier','Coach sportif','Professeur de musique','Professeur de langues','Formateur informatique','Coach de vie','Préparateur physique','Professeur de danse','Professeur de yoga','Moniteur auto-école','Formateur professionnel','Tuteur universitaire','Coach en développement personnel','Professeur de dessin / peinture','Instructeur arts martiaux','Coach nutrition']
        },
        'Beauté & Bien-être': {
            icon: '💆',
            subs: ['Coiffeur / Coiffeuse','Esthéticien(ne)','Masseur / Masseuse','Maquilleur / Maquilleuse','Prothésiste ongulaire','Coach bien-être','Barbier','Tatoueur','Perceur','Sophrologue','Naturopathe','Réflexologue','Diététicien(ne)','Praticien spa','Extension de cils','Coloriste','Styliste capillaire']
        },
        'Événements': {
            icon: '🎉',
            subs: ['DJ','Photographe','Vidéaste','Traiteur','Décorateur','Animateur','Wedding planner','Fleuriste','Maître de cérémonie','Organisateur événementiel','Sonorisation / Éclairage','Location de matériel','Calligraphe','Artiste de spectacle','Magicien','Caricaturiste','Food truck','Pâtissier événementiel','Barman / Mixologue']
        },
        'Transport & Déménagement': {
            icon: '🚚',
            subs: ['Déménageur','Livreur','Chauffeur privé','Coursier','Transporteur d\'animaux','Chauffeur VTC','Transport de meubles','Monte-meubles','Chauffeur accompagnateur','Transporteur frigorifique','Convoyeur de véhicules','Taxi colis','Stockage / Garde-meuble','Logisticien freelance']
        },
        'Informatique & Tech': {
            icon: '💻',
            subs: ['Développeur web','Technicien informatique','Réparateur smartphone','Installateur réseau','Graphiste','Community manager','Développeur mobile','Administrateur système','Expert cybersécurité','Consultant SEO / SEA','Rédacteur web','Monteur vidéo','UX / UI Designer','Data analyst','Formateur bureautique','Spécialiste e-commerce','Photographe produit','Motion designer','Photocopie, préparation de documents, et autres activités spécialisées de soutien de bureau']
        },
        'Artisanat & Création': {
            icon: '🎨',
            subs: ['Couturier sur mesure','Retoucheur','Bijoutier','Potier','Encadreur','Restaurateur de meubles','Tapissier','Ébéniste','Sculpteur','Graveur','Sérigraphe','Relieur','Doreur','Luthier','Vitrailliste','Céramiste','Tourneur sur bois','Créateur de bougies','Maroquinier']
        },
        'Automobile & Mécanique': {
            icon: '🚗',
            subs: ['Mécanicien auto','Carrossier','Électricien auto','Détailing auto','Réparateur deux-roues','Mécanicien moto','Contrôle technique','Dépanneur / Remorqueur','Débosseleur sans peinture','Vitrage auto','Climatisation auto','Lavage auto à domicile','Mécanicien nautique']
        },
        'Immobilier & Habitat': {
            icon: '🏠',
            subs: ['Agent immobilier indépendant','Diagnostiqueur immobilier','Home stager','Décorateur d\'intérieur','Architecte d\'intérieur','Géomètre','Expert en bâtiment','Courtier en travaux','Feng Shui consultant','Désinsectiseur / Dératiseur','Cuisiniste','Installateur dressing']
        }
    };

    let currentStep = 1;
    let selCat = '';
    let selSubs = [];

    function updateStepper() {
        for (let i = 1; i <= 3; i++) {
            const si = document.getElementById('onb-si-' + i);
            const numEl = si.querySelector('.onb-step-num');
            const spanEl = si.querySelector('span');
            numEl.classList.remove('active', 'done', 'pending');
            spanEl.className = '';
            if (i < currentStep) {
                numEl.classList.add('done');
                numEl.innerHTML = '<i class="fas fa-check" style="font-size:0.65rem;"></i>';
                spanEl.classList.add('done-label');
            } else if (i === currentStep) {
                numEl.classList.add('active');
                numEl.textContent = i;
                spanEl.classList.add('active-label');
            } else {
                numEl.classList.add('pending');
                numEl.textContent = i;
                spanEl.classList.add('pending-label');
            }
        }
    }

    function showStep(n) {
        currentStep = n;
        document.getElementById('onb-step-1').style.display = (n === 1) ? '' : 'none';
        document.getElementById('onb-step-2').style.display = (n === 2) ? '' : 'none';
        document.getElementById('onb-step-3').style.display = (n === 3) ? '' : 'none';
        updateStepper();
        updateNextBtn();
    }

    function updateNextBtn() {
        const btn = document.getElementById('onb-next-btn');
        if (currentStep === 1) {
            btn.disabled = !selCat;
            btn.innerHTML = 'Continuer <i class="fas fa-arrow-right ms-1"></i>';
        } else if (currentStep === 2) {
            btn.disabled = selSubs.length === 0;
            btn.innerHTML = 'Continuer <i class="fas fa-arrow-right ms-1"></i>';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Valider et commencer';
        }
    }

    function renderCats() {
        const grid = document.getElementById('onb-cat-grid');
        if (!grid) return;
        grid.innerHTML = '';
        Object.entries(catsData).forEach(function(entry) {
            const name = entry[0], data = entry[1];
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'onb-cat-btn';
            btn.innerHTML = '<span class="onb-cat-icon">' + data.icon + '</span><span class="onb-cat-name">' + name + '</span>';
            btn.onclick = function() {
                selCat = name;
                document.querySelectorAll('.onb-cat-btn').forEach(function(b) { b.classList.remove('selected'); });
                btn.classList.add('selected');
                updateNextBtn();
            };
            grid.appendChild(btn);
        });
    }

    function renderSubs() {
        const list = document.getElementById('onb-sub-list');
        list.innerHTML = '';
        document.getElementById('onb-cat-name').textContent = selCat;
        selSubs = [];
        var data = catsData[selCat];
        if (!data) return;
        data.subs.forEach(function(sub) {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'onb-sub-chip';
            chip.textContent = sub;
            chip.onclick = function() {
                const idx = selSubs.indexOf(sub);
                if (idx > -1) { selSubs.splice(idx, 1); chip.classList.remove('selected'); }
                else { selSubs.push(sub); chip.classList.add('selected'); }
                const countEl = document.getElementById('onb-sub-count');
                if (selSubs.length > 0) {
                    countEl.style.display = '';
                    countEl.innerHTML = '✓ ' + selSubs.length + ' spécialité(s) sélectionnée(s)';
                } else { countEl.style.display = 'none'; }
                updateNextBtn();
            };
            list.appendChild(chip);
        });
        document.getElementById('onb-sub-count').style.display = 'none';
    }

    window.onbGoBack = function(toStep) {
        showStep(toStep);
    };

    window.onbNext = function() {
        if (currentStep === 1 && selCat) {
            renderSubs();
            showStep(2);
        } else if (currentStep === 2 && selSubs.length > 0) {
            showStep(3);
        } else if (currentStep === 3) {
            onbSave();
        }
    };

    window.onbSkip = function() {
        document.getElementById('onb-overlay').style.display = 'none';
    };

    window.onbGeolocate = function() {
        var statusEl = document.getElementById('onb-geo-status');
        var geoBtn = document.getElementById('onb-geo-btn');
        geoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation en cours...';
        statusEl.style.display = '';
        statusEl.style.color = '#3a86ff';
        statusEl.textContent = 'Recherche de votre position...';

        if (!navigator.geolocation) {
            statusEl.style.color = '#ef4444';
            statusEl.textContent = 'La géolocalisation n\'est pas supportée par votre navigateur.';
            geoBtn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
            return;
        }

        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;
            fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json&addressdetails=1')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.address) {
                        var addr = data.address;
                        var city = addr.city || addr.town || addr.village || addr.municipality || '';
                        var road = addr.road || '';
                        var houseNumber = addr.house_number || '';
                        var postcode = addr.postcode || '';
                        var fullAddr = ((houseNumber ? houseNumber + ' ' : '') + road + ', ' + postcode + ' ' + city).trim().replace(/^,\s*/, '');
                        document.getElementById('onb-address').value = fullAddr || data.display_name || '';
                        document.getElementById('onb-city').value = city;
                        statusEl.style.color = '#22c55e';
                        statusEl.innerHTML = '<i class="fas fa-check-circle"></i> Position trouvée : ' + city;
                    }
                    geoBtn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
                })
                .catch(function() {
                    statusEl.style.color = '#ef4444';
                    statusEl.textContent = 'Impossible de déterminer l\'adresse.';
                    geoBtn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
                });
        }, function() {
            statusEl.style.color = '#ef4444';
            statusEl.textContent = 'Accès à la localisation refusé. Saisissez votre adresse manuellement.';
            geoBtn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
        }, { timeout: 10000 });
    };

    function onbSave() {
        var btn = document.getElementById('onb-next-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...';

        var payload = {
            service_category: selCat,
            service_subcategories: selSubs,
            address: document.getElementById('onb-address').value,
            city: document.getElementById('onb-city').value,
            pro_intervention_radius: parseInt(document.getElementById('onb-radius').value) || 30,
        };

        fetch('{{ route("profile.save-categories") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('onb-overlay').style.display = 'none';
                var toast = document.createElement('div');
                toast.className = 'onb-toast';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Vos informations ont été ajoutées à votre profil ! Les données précédentes ont été conservées. Vous pouvez les modifier depuis votre profil.';
                document.body.appendChild(toast);
                setTimeout(function() { toast.remove(); }, 5000);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Valider et commencer';
                alert(data.message || 'Erreur. Veuillez réessayer.');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Valider et commencer';
            alert('Erreur de connexion. Veuillez réessayer.');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderCats();
        showStep(1);
    });
})();
</script>
@endif
