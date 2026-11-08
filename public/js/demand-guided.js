/*
 * Parcours guidé « Publier sans écrire » (/demande?situation=…) et écran « Demande publiée ».
 *
 * Sans JavaScript, toutes les étapes restent visibles sur une longue page et le
 * serveur compose le texte. Avec JavaScript :
 *  - une étape visible à la fois, « Étape N sur M », historique du navigateur ;
 *  - un toucher sur une réponse passe à l'écran suivant (le bouton « Suivant » reste pour le clavier) ;
 *  - « Écouter » lit la question et les réponses (speechSynthesis), sans dictée ;
 *  - photos réduites à 1 600 px et réencodées en JPEG avant l'envoi ;
 *  - aperçu du texte composé (ProkejemDemandText, mêmes règles qu'en PHP), inséré en textContent seulement ;
 *  - invité : réponses gardées dans sessionStorage (sans photos), puis connexion.
 * Aucun texte saisi n'est envoyé à la mesure d'usage : seulement des noms d'événements et un numéro d'étape.
 */
(function () {
    'use strict';

    var doc = document;
    var STORAGE_KEY = 'prokejem.guided.v1';
    var MAX_PHOTOS = 2;
    var MAX_PHOTO_BYTES = 5 * 1024 * 1024;
    var MAX_PHOTO_SIDE = 1600;
    var ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    var MESSAGES = {
        heic: 'Cette photo est au format HEIC, que nous ne savons pas lire. Choisissez une autre photo, ou prenez-la avec le bouton « Prendre une photo ».',
        unreadable: 'Cette photo ne peut pas être lue. Essayez une autre photo.',
        tooHeavy: 'Cette photo est trop lourde. Essayez une autre photo.',
        tooMany: 'Deux photos au plus.',
        added: 'Photo ajoutée.',
        city: 'Choisissez votre commune.',
        country: 'Choisissez votre pays ou territoire.',
        when: 'Choisissez quand vous avez besoin d’aide.',
        date: 'Choisissez une date à partir d’aujourd’hui.',
        price: 'Indiquez un prix en euros, ou choisissez « À discuter ».',
        confirm: 'Cochez la case « Ma demande sera visible pendant 30 jours » avant de publier.',
        resumed: 'Vos photos sont à ajouter de nouveau.',
        lost: 'Vos réponses n’ont pas pu être gardées. Recommencez, c’est rapide.'
    };

    function track(name, details) {
        try {
            if (window.prokejemUsage && typeof window.prokejemUsage.track === 'function') {
                window.prokejemUsage.track(name, details || {});
            }
        } catch (error) { /* la mesure ne bloque jamais le parcours */ }
    }

    function storageGet() {
        try {
            var raw = window.sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    }

    function storageSet(value) {
        try {
            window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(value));
            return true;
        } catch (error) {
            return false;
        }
    }

    function storageClear() {
        try { window.sessionStorage.removeItem(STORAGE_KEY); } catch (error) { /* rien */ }
    }

    function isHeic(file) {
        var type = String(file.type || '').toLowerCase();
        var name = String(file.name || '').toLowerCase();
        return type === 'image/heic' || type === 'image/heif' || /\.(heic|heif)$/.test(name);
    }

    /* ------------------------------------------------------------------ */
    /* Écran « Demande publiée »                                           */
    /* ------------------------------------------------------------------ */

    function initPublished(root) {
        storageClear();

        var share = root.querySelector('[data-pk-share]');
        if (share) {
            share.addEventListener('click', function () {
                var url = share.getAttribute('data-url') || '';
                var text = share.getAttribute('data-text') || '';
                track('demand_share_click');
                if (navigator.share) {
                    navigator.share({ title: text, text: text, url: url }).catch(function () { /* partage annulé */ });
                    return;
                }
                window.open('https://wa.me/?text=' + encodeURIComponent(text + ' ' + url), '_blank', 'noopener');
            });
        }

        var push = root.querySelector('[data-pk-push-offer]');
        if (push && 'PushManager' in window && 'Notification' in window && window.Notification.permission === 'default') {
            push.hidden = false;
        }
    }

    /* ------------------------------------------------------------------ */
    /* Parcours guidé                                                      */
    /* ------------------------------------------------------------------ */

    function initGuided(form) {
        var config = {};
        try {
            config = JSON.parse((doc.getElementById('pkGuidedConfig') || {}).textContent || '{}');
        } catch (error) {
            config = {};
        }

        var steps = Array.prototype.slice.call(form.querySelectorAll('[data-pk-step]'));
        if (!steps.length) return;

        var total = steps.length;
        var current = 0;
        var depth = 0;
        var viewed = {};
        var lastPointer = 0;
        var advanceTimer = null;
        var overrideTouched = false;
        var composed = { title: '', description: '' };

        var currentEl = doc.querySelector('[data-pk-step-current]');
        var progressEl = doc.querySelector('.pk-guided__progress');
        var backEl = doc.querySelector('[data-pk-back]');
        var navEl = form.querySelector('[data-pk-nav]');
        var nextEl = form.querySelector('[data-pk-next]');
        var alertEl = doc.querySelector('.pk-guided__alert');
        var resumeEl = doc.querySelector('[data-pk-resume-msg]');
        var serverErrors = !!(alertEl && !alertEl.hidden);

        function field(name) {
            return form.querySelector('[name="' + name + '"]');
        }

        function checkedValue(name) {
            var input = form.querySelector('input[name="' + name + '"]:checked');
            return input ? input.value : '';
        }

        function showAlert(message) {
            if (!alertEl) return;
            alertEl.textContent = '';
            if (!message) {
                alertEl.hidden = true;
                return;
            }
            var p = doc.createElement('p');
            p.textContent = message;
            alertEl.appendChild(p);
            alertEl.hidden = false;
        }

        /* --- Valeurs du formulaire (aperçu, sauvegarde invitée) --- */

        function chosenCity() {
            var city = checkedValue('city');
            if (city === '' || city === '__other__') {
                var typed = field('city_typed');
                city = typed ? typed.value : '';
            }
            return city.trim();
        }

        function values() {
            var answers = {};
            form.querySelectorAll('[data-pk-question]').forEach(function (step) {
                var key = step.getAttribute('data-pk-question');
                var value = checkedValue('answers[' + key + ']');
                if (value) answers[key] = value;
            });
            var when = checkedValue('when');
            var dateInput = field('desired_date_choice');
            var priceInput = field('price');
            var detailInput = field('detail');
            var countryInput = form.querySelector('input[name="country"]:checked') || form.querySelector('input[type="hidden"][name="country"]');
            return {
                situation_key: config.situationKey || '',
                answers: answers,
                city: chosenCity(),
                city_choice: checkedValue('city'),
                city_typed: field('city_typed') ? field('city_typed').value : '',
                country: countryInput ? countryInput.value : '',
                when: when,
                desired_date: when === 'date' && dateInput ? dateInput.value : null,
                desired_date_choice: dateInput ? dateInput.value : '',
                time_window: checkedValue('time_window') || 'flexible',
                price_type: checkedValue('price_type'),
                price: priceInput ? priceInput.value : '',
                detail: detailInput ? detailInput.value : ''
            };
        }

        /* --- Aperçu du texte composé --- */

        var previewEmpty = form.querySelector('[data-pk-preview-empty]');
        var previewBody = form.querySelector('[data-pk-preview-body]');
        var previewTitle = form.querySelector('[data-pk-preview-title]');
        var previewText = form.querySelector('[data-pk-preview-text]');
        var editButton = form.querySelector('[data-pk-edit-text]');
        var overrideWrap = form.querySelector('[data-pk-override]');
        var overrideInput = field('description_override');

        function updatePreview() {
            if (!window.ProkejemDemandText || !config.catalog) return;
            composed = window.ProkejemDemandText.compose(config.catalog, values());
            if (!composed.title) return;
            previewTitle.textContent = composed.title;
            previewText.textContent = composed.description;
            previewEmpty.hidden = true;
            previewBody.hidden = false;
            if (editButton) editButton.hidden = false;
            if (overrideInput && overrideWrap && !overrideWrap.hidden && !overrideTouched) {
                overrideInput.value = composed.description;
            }
        }

        if (editButton && overrideWrap && overrideInput) {
            editButton.addEventListener('click', function () {
                updatePreview();
                if (!overrideTouched && overrideInput.value.trim() === '') overrideInput.value = composed.description;
                overrideWrap.hidden = false;
                editButton.hidden = true;
                overrideInput.focus();
            });
            overrideInput.addEventListener('input', function () { overrideTouched = true; });
            if (overrideInput.value.trim() !== '') {
                overrideTouched = true;
                overrideWrap.hidden = false;
            }
        }

        /* --- Champs qui dépendent d'un choix --- */

        function syncDependents() {
            var dateWrap = form.querySelector('[data-pk-date]');
            if (dateWrap) dateWrap.hidden = checkedValue('when') !== 'date';

            var priceWrap = form.querySelector('[data-pk-price]');
            if (priceWrap) priceWrap.hidden = checkedValue('price_type') !== 'fixed';

            var typedWrap = form.querySelector('[data-pk-city-typed]');
            if (typedWrap && form.querySelector('[data-pk-city-other]')) {
                typedWrap.hidden = checkedValue('city') !== '__other__';
            }

            form.querySelectorAll('.pk-answer').forEach(function (label) {
                var input = label.querySelector('input[type="radio"]');
                if (input) label.classList.toggle('is-checked', input.checked);
            });
        }

        /* --- Navigation entre les étapes --- */

        function validateStep(step) {
            if (step.hasAttribute('data-pk-place')) {
                var country = form.querySelector('input[name="country"]:checked') || form.querySelector('input[type="hidden"][name="country"]');
                if (!country || !country.value) return MESSAGES.country;
                if (chosenCity() === '') return MESSAGES.city;
            }
            if (step.hasAttribute('data-pk-when')) {
                var when = checkedValue('when');
                if (!when) return MESSAGES.when;
                if (when === 'date') {
                    var date = field('desired_date_choice');
                    var min = date ? date.getAttribute('min') || '' : '';
                    if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date.value) || (min && date.value < min)) return MESSAGES.date;
                }
            }
            if (step.hasAttribute('data-pk-budget') && checkedValue('price_type') === 'fixed') {
                var price = field('price');
                if (!price || !/^\d+$/.test(price.value.trim()) || parseInt(price.value, 10) < 1) return MESSAGES.price;
            }
            return '';
        }

        function show(index, options) {
            options = options || {};
            index = Math.max(0, Math.min(total - 1, index));
            current = index;

            steps.forEach(function (step, i) { step.hidden = i !== index; });
            if (currentEl) currentEl.textContent = String(index + 1);
            if (progressEl) progressEl.value = index + 1;
            if (navEl) navEl.hidden = index === total - 1;
            if (!options.keepAlert) showAlert('');

            if (options.push) {
                depth += 1;
                try { history.pushState({ pkStep: index, pkDepth: depth }, '', window.location.href); } catch (error) { /* rien */ }
            }

            if (steps[index].hasAttribute('data-pk-review')) updatePreview();

            var legend = steps[index].querySelector('.pk-guided__legend');
            if (options.focus !== false && legend) {
                try { legend.focus({ preventScroll: true }); } catch (error) { legend.focus(); }
                var top = form.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo(0, Math.max(0, top));
            }

            if (!viewed[index]) {
                viewed[index] = true;
                track('demand_guided_step_view', { step: index + 1 });
            }
        }

        function next() {
            window.clearTimeout(advanceTimer);
            var message = validateStep(steps[current]);
            if (message) {
                showAlert(message);
                return;
            }
            if (current < total - 1) show(current + 1, { push: true });
        }

        if (nextEl) nextEl.addEventListener('click', next);

        if (backEl) {
            backEl.addEventListener('click', function (event) {
                if (current === 0) return;
                event.preventDefault();
                if (depth > 0) {
                    history.back();
                } else {
                    show(current - 1);
                }
            });
        }

        window.addEventListener('popstate', function (event) {
            var state = event.state;
            if (state && typeof state.pkStep === 'number') {
                depth = state.pkDepth || 0;
                show(state.pkStep);
            }
        });

        /* --- Réponses : un toucher coche et avance --- */

        form.addEventListener('pointerup', function (event) {
            if (event.target.closest && event.target.closest('.pk-answer')) lastPointer = Date.now();
        });

        form.addEventListener('change', function (event) {
            var input = event.target;
            syncDependents();
            updatePreview();

            if (input.type !== 'radio') return;
            var step = input.closest('[data-pk-question]');
            if (!step) return;
            if (input.value === 'unknown') track('demand_answer_unknown');
            // Les flèches du clavier changent aussi la réponse : on n'avance qu'après un toucher ou un clic.
            if (Date.now() - lastPointer < 1000) {
                window.clearTimeout(advanceTimer);
                advanceTimer = window.setTimeout(next, 150);
            }
        });

        form.addEventListener('input', function (event) {
            if (event.target === overrideInput) return;
            updatePreview();
        });

        var skip = form.querySelector('[data-pk-skip]');
        if (skip) skip.addEventListener('click', next);

        /* --- Écouter (lecture à voix haute, sans dictée) --- */

        var canSpeak = 'speechSynthesis' in window && typeof window.SpeechSynthesisUtterance === 'function';
        if (canSpeak) {
            form.querySelectorAll('[data-pk-listen]').forEach(function (button) {
                button.hidden = false;
                button.addEventListener('click', function () {
                    var step = button.closest('[data-pk-step]');
                    var parts = [];
                    if (step.hasAttribute('data-pk-review')) {
                        updatePreview();
                        parts = [composed.title, composed.description];
                    } else {
                        var legend = step.querySelector('.pk-guided__legend');
                        if (legend) parts.push(legend.textContent);
                        step.querySelectorAll('.pk-guided__intro p, .pk-guided__subtitle, .pk-answer__label').forEach(function (node) {
                            if (!node.closest('[hidden]')) parts.push(node.textContent);
                        });
                    }
                    var text = parts.map(function (part) { return String(part || '').trim(); }).filter(Boolean).join('. ');
                    if (!text) return;
                    window.speechSynthesis.cancel();
                    var utterance = new window.SpeechSynthesisUtterance(text);
                    utterance.lang = 'fr-FR';
                    utterance.rate = 0.9;
                    var voices = window.speechSynthesis.getVoices ? window.speechSynthesis.getVoices() : [];
                    for (var i = 0; i < voices.length; i += 1) {
                        if (/^fr/i.test(voices[i].lang)) { utterance.voice = voices[i]; break; }
                    }
                    window.speechSynthesis.speak(utterance);
                    track('demand_tts_used');
                });
            });
        }

        /* --- Photos --- */

        initPhotos(form);

        /* --- Envoi --- */

        form.addEventListener('submit', function (event) {
            var confirm = field('publication_confirmed');
            for (var i = 0; i < steps.length - 1; i += 1) {
                var message = validateStep(steps[i]);
                if (message) {
                    event.preventDefault();
                    show(i, { push: true, keepAlert: true });
                    showAlert(message);
                    return;
                }
            }

            var data = values();

            if (config.isGuest) {
                event.preventDefault();
                storageSet({
                    situation_key: data.situation_key,
                    answers: data.answers,
                    city: data.city_choice,
                    city_typed: data.city_typed,
                    country: data.country,
                    when: data.when,
                    time_window: data.time_window,
                    price_type: data.price_type,
                    price: data.price,
                    detail: data.detail,
                    desired_date_choice: data.desired_date_choice,
                    publication_confirmed: !!(confirm && confirm.checked)
                });
                window.location.assign(config.loginUrl);
                return;
            }

            if (confirm && !confirm.checked) {
                event.preventDefault();
                showAlert(MESSAGES.confirm);
                confirm.focus();
                return;
            }

            if (String(data.detail || '').trim() !== '') track('demand_detail_added');
        });

        /* --- Reprise après la connexion --- */

        function setRadio(name, value) {
            if (value === undefined || value === null || value === '') return;
            form.querySelectorAll('input[type="radio"][name="' + name + '"]').forEach(function (input) {
                input.checked = input.value === String(value);
            });
        }

        function setValue(name, value) {
            var input = field(name);
            if (input && typeof value === 'string') input.value = value;
        }

        function restore() {
            var saved = storageGet();
            if (!saved || saved.situation_key !== config.situationKey) return false;

            Object.keys(saved.answers || {}).forEach(function (key) {
                setRadio('answers[' + key + ']', saved.answers[key]);
            });
            setRadio('country', saved.country);
            setRadio('city', saved.city);
            setValue('city_typed', saved.city_typed);
            setRadio('when', saved.when);
            setValue('desired_date_choice', saved.desired_date_choice);
            setRadio('time_window', saved.time_window);
            setRadio('price_type', saved.price_type);
            setValue('price', saved.price);
            setValue('detail', saved.detail);
            var confirm = field('publication_confirmed');
            if (confirm && saved.publication_confirmed) confirm.checked = true;
            return true;
        }

        /* --- Démarrage --- */

        var start = 0;
        if (config.resume) {
            if (restore()) {
                start = total - 1;
                if (resumeEl) { resumeEl.textContent = MESSAGES.resumed; resumeEl.hidden = false; }
            } else if (resumeEl && !serverErrors) {
                resumeEl.textContent = MESSAGES.lost;
                resumeEl.hidden = false;
            }
        }
        if (serverErrors) start = total - 1;

        syncDependents();
        try { history.replaceState({ pkStep: start, pkDepth: 0 }, '', window.location.href); } catch (error) { /* rien */ }
        show(start, { focus: start !== 0 || serverErrors, keepAlert: serverErrors });
    }

    function initPhotos(form) {
        var step = form.querySelector('[data-pk-photo]');
        if (!step) return;

        var submitInput = step.querySelector('[data-pk-photo-submit]');
        var actions = step.querySelector('[data-pk-photo-actions]');
        var fallback = step.querySelector('[data-pk-photo-fallback]');
        var list = step.querySelector('[data-pk-photo-list]');
        var message = step.querySelector('[data-pk-photo-msg]');
        var photos = [];
        var canTransfer = false;

        try {
            canTransfer = typeof window.DataTransfer === 'function' && !!new window.DataTransfer().items;
        } catch (error) {
            canTransfer = false;
        }

        function say(text) { if (message) message.textContent = text; }

        function reject(text) {
            say(text);
            track('demand_photo_rejected');
        }

        if (!canTransfer) {
            // Navigateur ancien : l'original est envoyé tel quel, s'il est lisible et assez léger.
            if (submitInput) {
                submitInput.addEventListener('change', function () {
                    var files = Array.prototype.slice.call(submitInput.files || []);
                    var problem = '';
                    if (files.length > MAX_PHOTOS) problem = MESSAGES.tooMany;
                    files.forEach(function (file) {
                        if (problem) return;
                        if (isHeic(file)) problem = MESSAGES.heic;
                        else if (ACCEPTED_TYPES.indexOf(file.type) === -1) problem = MESSAGES.unreadable;
                        else if (file.size > MAX_PHOTO_BYTES) problem = MESSAGES.tooHeavy;
                    });
                    if (problem) {
                        submitInput.value = '';
                        reject(problem);
                    } else if (files.length) {
                        say(MESSAGES.added);
                        track('demand_photo_added');
                    }
                });
            }
            return;
        }

        if (actions) actions.hidden = false;
        if (fallback) fallback.hidden = true;

        function sync() {
            var transfer = new window.DataTransfer();
            photos.forEach(function (photo) { transfer.items.add(photo.file); });
            submitInput.files = transfer.files;

            list.textContent = '';
            photos.forEach(function (photo, index) {
                var item = doc.createElement('li');
                var img = doc.createElement('img');
                img.src = photo.url;
                img.alt = 'Photo ' + (index + 1);
                var remove = doc.createElement('button');
                remove.type = 'button';
                remove.textContent = 'Retirer';
                remove.addEventListener('click', function () {
                    URL.revokeObjectURL(photo.url);
                    photos.splice(index, 1);
                    sync();
                    say('');
                });
                item.appendChild(img);
                item.appendChild(remove);
                list.appendChild(item);
            });
        }

        function decode(file) {
            if (typeof window.createImageBitmap === 'function') {
                return window.createImageBitmap(file, { imageOrientation: 'from-image' })
                    .catch(function () { return window.createImageBitmap(file); })
                    .catch(function () { return decodeWithImage(file); });
            }
            return decodeWithImage(file);
        }

        function decodeWithImage(file) {
            return new Promise(function (resolve, reject) {
                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
                img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('illisible')); };
                img.src = url;
            });
        }

        function toJpeg(source) {
            var width = source.width || source.naturalWidth;
            var height = source.height || source.naturalHeight;
            if (!width || !height) return Promise.reject(new Error('illisible'));
            var scale = Math.min(1, MAX_PHOTO_SIDE / Math.max(width, height));
            var canvas = doc.createElement('canvas');
            canvas.width = Math.round(width * scale);
            canvas.height = Math.round(height * scale);
            var context = canvas.getContext('2d');
            if (!context) return Promise.reject(new Error('illisible'));
            context.drawImage(source, 0, 0, canvas.width, canvas.height);
            if (typeof source.close === 'function') source.close();
            return new Promise(function (resolve, reject) {
                canvas.toBlob(function (blob) {
                    if (blob) resolve(blob); else reject(new Error('illisible'));
                }, 'image/jpeg', 0.82);
            });
        }

        function add(file) {
            if (photos.length >= MAX_PHOTOS) {
                reject(MESSAGES.tooMany);
                return Promise.resolve();
            }
            if (isHeic(file)) {
                reject(MESSAGES.heic);
                return Promise.resolve();
            }
            return decode(file).then(toJpeg).then(function (blob) {
                if (blob.size > MAX_PHOTO_BYTES) {
                    reject(MESSAGES.tooHeavy);
                    return;
                }
                if (photos.length >= MAX_PHOTOS) {
                    reject(MESSAGES.tooMany);
                    return;
                }
                var name = String(file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
                var output = new File([blob], name, { type: 'image/jpeg' });
                photos.push({ file: output, url: URL.createObjectURL(output) });
                sync();
                say(MESSAGES.added);
                track('demand_photo_added');
            }).catch(function () {
                reject(MESSAGES.unreadable);
            });
        }

        step.querySelectorAll('[data-pk-photo-camera], [data-pk-photo-pick]').forEach(function (input) {
            input.addEventListener('change', function () {
                var files = Array.prototype.slice.call(input.files || []);
                input.value = '';
                files.reduce(function (chain, file) {
                    return chain.then(function () { return add(file); });
                }, Promise.resolve());
            });
        });
    }

    function boot() {
        var published = doc.querySelector('[data-pk-published]');
        if (published) initPublished(published);

        var form = doc.getElementById('pkGuidedForm');
        if (form) initGuided(form);
    }

    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
