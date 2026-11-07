(function (root, factory) {
    const helper = factory();
    if (typeof module === 'object' && module.exports) module.exports = helper;
    else root.ProkejemDemandWriting = helper;
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';

    function examplesFor(service, catalogue) {
        if (!service) return [];
        const entries = catalogue[service] || [
            ['Demander une intervention', `Besoin d’aide : ${service}`, `Je recherche un prestataire pour le service « ${service} ». J’aimerais échanger pour préciser mon besoin.`],
            ['Demander un conseil ou un devis', `Conseil ou devis : ${service}`, `Je recherche un prestataire pour le service « ${service} ». J’aimerais être conseillé et connaître le coût de la prestation.`],
        ];
        return entries.map(([label, title, description]) => ({label, title, description}));
    }

    function applyExample(title, description, example, maxLength = 2000) {
        // Keep every character entered by the user, including an existing title.
        if (description.includes(example.description)) return {title, description, status: 'duplicate'};
        const next = description.trim() ? `${description}\n\n${example.description}` : example.description;
        if (next.length > maxLength) return {title, description, status: 'too_long'};
        return {title: title.trim() ? title : example.title, description: next, status: 'added'};
    }

    return {examplesFor, applyExample};
});
