    const publicationSchemas = @json($publicationSchemas);
    let activePublicationDomain = document.getElementById('publication_domain')?.value || null;

    function publicationDomainForMainCategory(mainCategory) {
        if (!mainCategory) return null;

        for (const [domain, schema] of Object.entries(publicationSchemas)) {
            if ((schema.main_categories || []).includes(mainCategory)) return domain;
        }

        return Object.entries(publicationSchemas).find(([, schema]) => schema.is_default)?.[0] || 'service';
    }

    function getActivePublicationSchema() {
        return activePublicationDomain ? publicationSchemas[activePublicationDomain] : null;
    }

    function conditionalValue(panel, key) {
        const control = panel.querySelector(`[data-detail-key="${key}"]`);
        if (!control) return null;
        if (control.type === 'checkbox') return control.checked ? '1' : '0';
        return control.value;
    }

    function refreshConditionalDomainFields(panel) {
        if (!panel) return;

        panel.querySelectorAll('[data-domain-field-wrapper]').forEach(wrapper => {
            const showWhenField = wrapper.dataset.showWhenField;
            const showWhenValue = wrapper.dataset.showWhenValue;
            const requiredWhenField = wrapper.dataset.requiredWhenField;
            const requiredWhenValue = wrapper.dataset.requiredWhenValue;
            const visible = !showWhenField || conditionalValue(panel, showWhenField) === showWhenValue;
            const conditionallyRequired = requiredWhenField
                && conditionalValue(panel, requiredWhenField) === requiredWhenValue;

            wrapper.classList.toggle('is-conditional-hidden', !visible);
            wrapper.querySelectorAll('input, select, textarea').forEach(control => {
                control.disabled = !visible;
                control.required = visible && (control.dataset.baseRequired === '1' || conditionallyRequired);
            });
        });
    }

    function applyPublicationDomain(mainCategory) {
        activePublicationDomain = publicationDomainForMainCategory(mainCategory);
        const section = document.getElementById('publication-domain-section');
        const hiddenDomain = document.getElementById('publication_domain');
        const schema = getActivePublicationSchema();

        if (hiddenDomain) hiddenDomain.value = activePublicationDomain || '';
        if (section) section.style.display = schema ? 'block' : 'none';

        document.querySelectorAll('.publication-domain-panel').forEach(panel => {
            const isActive = panel.dataset.publicationDomain === activePublicationDomain;
            panel.style.display = isActive ? 'block' : 'none';
            panel.querySelectorAll('input, select, textarea').forEach(control => {
                control.disabled = !isActive;
                if (!isActive) control.required = false;
            });
            if (isActive) refreshConditionalDomainFields(panel);
        });

        if (!schema) return;

        const domainTitle = document.getElementById('publication-domain-title');
        if (domainTitle) domainTitle.textContent = `Informations — ${schema.label}`;

        const serviceType = document.getElementById('service_type')?.value || 'demande';
        const titleField = document.getElementById('title');
        const descriptionField = document.getElementById('description');
        if (titleField) titleField.placeholder = schema.title_placeholders?.[serviceType] || titleField.placeholder;
        if (descriptionField) descriptionField.placeholder = schema.description_placeholders?.[serviceType] || descriptionField.placeholder;

        const locationTitle = document.getElementById('location-section-title');
        if (locationTitle) locationTitle.textContent = schema.location?.title || 'Localisation';
        const radiusColumn = document.getElementById('radius-column');
        if (radiusColumn) radiusColumn.style.display = schema.location?.show_radius === false ? 'none' : '';
        ['location-country-column', 'location-city-column'].forEach(columnId => {
            const column = document.getElementById(columnId);
            if (!column) return;
            column.classList.toggle('col-md-4', schema.location?.show_radius !== false);
            column.classList.toggle('col-md-6', schema.location?.show_radius === false);
        });

        const priceTitle = document.getElementById('price-section-title');
        const priceModeLabel = document.getElementById('price-mode-label');
        if (priceTitle) priceTitle.textContent = schema.price?.title || 'Tarification';
        if (priceModeLabel) priceModeLabel.textContent = schema.price?.mode_label || 'Mode de tarification';

        const allowedTypes = schema.price?.allowed_types || ['fixed', 'hourly', 'negotiable'];
        document.querySelectorAll('.price-mode-option').forEach(option => {
            const type = option.dataset.priceType;
            const allowed = allowedTypes.includes(type);
            option.style.display = allowed ? '' : 'none';
            const radio = option.querySelector('input[type="radio"]');
            if (radio) radio.disabled = !allowed;
            const label = option.querySelector('span');
            if (label && schema.price?.type_labels?.[type]) label.textContent = schema.price.type_labels[type];
        });

        const currentPriceType = document.querySelector('input[name="price_type"]:checked')?.value;
        const nextPriceType = allowedTypes.includes(currentPriceType)
            ? currentPriceType
            : (schema.price?.default_type || allowedTypes[0] || 'negotiable');
        setPriceType(nextPriceType);
    }

    document.querySelectorAll('.publication-domain-panel').forEach(panel => {
        panel.addEventListener('change', () => refreshConditionalDomainFields(panel));
    });
