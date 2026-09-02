(function () {
    'use strict';

    var requiredSelector = 'input[required], select[required], textarea[required]';

    function fieldContainer(field) {
        return field.closest('[data-field-container], .demand-field, .form-group, .mb-3, .form-check, .input-group')
            || field.parentElement;
    }

    function messageFor(field) {
        if (field.validity.valueMissing) return 'Ce champ est obligatoire.';
        if (field.validity.typeMismatch) return 'Vérifiez le format de cette information.';
        if (field.validity.tooShort) return 'Cette information est trop courte.';
        if (field.validity.tooLong) return 'Cette information est trop longue.';
        if (field.validity.rangeUnderflow || field.validity.rangeOverflow) return 'Cette valeur est hors de la plage autorisée.';
        if (field.validity.patternMismatch) return 'Le format saisi n’est pas valide.';
        return field.validationMessage || 'Vérifiez cette information.';
    }

    function feedbackKey(field) {
        return field.id || field.name || 'required-field';
    }

    function mark(field, message) {
        if (!field) return;

        var container = fieldContainer(field);
        var key = feedbackKey(field);
        field.classList.add('pk-field-invalid');
        field.setAttribute('aria-invalid', 'true');
        if (container) container.classList.add('pk-required-missing');

        var feedback = container ? container.querySelector('[data-pk-validation-for="' + key.replace(/"/g, '') + '"]') : null;
        if (!feedback && container) {
            feedback = document.createElement('span');
            feedback.className = 'pk-field-feedback';
            feedback.dataset.pkValidationFor = key;
            feedback.setAttribute('role', 'alert');
            container.appendChild(feedback);
        }
        if (feedback) feedback.textContent = message || messageFor(field);
    }

    function clear(field) {
        if (!field) return;
        var container = fieldContainer(field);
        var key = feedbackKey(field);
        field.classList.remove('pk-field-invalid');
        field.removeAttribute('aria-invalid');
        if (container) {
            container.classList.remove('pk-required-missing');
            var feedback = container.querySelector('[data-pk-validation-for="' + key.replace(/"/g, '') + '"]');
            if (feedback) feedback.remove();
        }
    }

    function invalidFields(form) {
        return Array.prototype.slice.call(form.querySelectorAll(requiredSelector))
            .filter(function (field) { return !field.disabled && !field.validity.valid; });
    }

    function showSummary(form, count) {
        var summary = form.querySelector(':scope > .pk-validation-summary');
        if (!summary) {
            summary = document.createElement('div');
            summary.className = 'pk-validation-summary';
            summary.setAttribute('role', 'alert');
            summary.setAttribute('tabindex', '-1');
            form.insertBefore(summary, form.firstChild);
        }
        summary.textContent = count === 1
            ? 'Une information obligatoire doit être complétée. Le champ concerné est indiqué en rouge.'
            : count + ' informations obligatoires doivent être complétées. Les champs concernés sont indiqués en rouge.';
    }

    function clearSummary(form) {
        form.querySelector(':scope > .pk-validation-summary')?.remove();
    }

    function focusFirst(field) {
        var target = field.offsetParent !== null ? field : fieldContainer(field);
        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (field.offsetParent !== null && typeof field.focus === 'function') {
            window.setTimeout(function () { field.focus({ preventScroll: true }); }, 220);
        }
    }

    function highlightForm(form) {
        var fields = invalidFields(form);
        if (!fields.length) {
            clearSummary(form);
            return true;
        }
        fields.forEach(function (field) { mark(field); });
        showSummary(form, fields.length);
        focusFirst(fields[0]);
        return false;
    }

    document.addEventListener('invalid', function (event) {
        if (event.target.matches?.(requiredSelector)) mark(event.target);
    }, true);

    ['input', 'change'].forEach(function (eventName) {
        document.addEventListener(eventName, function (event) {
            var field = event.target;
            if (!field.matches?.(requiredSelector) || !field.validity.valid) return;
            clear(field);
            if (field.form && invalidFields(field.form).length === 0) clearSummary(field.form);
        });
    });

    document.addEventListener('submit', function (event) {
        if (!highlightForm(event.target)) event.preventDefault();
    }, true);

    window.ProkejemFormValidation = {
        mark: mark,
        clear: clear,
        highlightForm: highlightForm,
    };
})();
