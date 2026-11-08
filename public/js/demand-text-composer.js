/*
 * Compositeur du texte d'une demande guidée (aperçu dans le navigateur).
 * Même signature et mêmes règles que App\Services\Demand\DemandTextComposer (PHP),
 * qui reste la référence. Les deux sont vérifiés sur la même fixture :
 * tests/fixtures/demand-text-cases.json. Toute modification se fait des deux côtés.
 * Aucune date n'est formatée par Intl : le tableau des mois garantit la même sortie qu'en PHP.
 */
(function (root, factory) {
    const composer = factory();
    if (typeof module === 'object' && module.exports) module.exports = composer;
    else root.ProkejemDemandText = composer;
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';

    const VERSION = 1;
    const DETAIL_MAX = 500;
    const TITLE_MAX = 255;
    const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    const WHEN = {
        today: 'J’ai besoin d’aide aujourd’hui',
        this_week: 'J’ai besoin d’aide cette semaine',
        this_month: 'J’ai besoin d’aide ce mois-ci',
        no_rush: 'Ce n’est pas pressé',
    };
    const TIME_WINDOW = {
        morning: ', le matin si possible',
        afternoon: ', l’après-midi si possible',
        evening: ', le soir si possible',
        flexible: '',
    };
    // Mêmes caractères que la constante SPACES du compositeur PHP.
    const SPACES = /[\t\n\u000b\f\r \u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]+/g;
    const has = (object, key) => object !== null && typeof object === 'object' && Object.prototype.hasOwnProperty.call(object, key);

    function normalize(value) {
        if (typeof value !== 'string' && typeof value !== 'number') return '';
        return String(value).replace(SPACES, ' ').replace(/^ +| +$/g, '');
    }

    // Coupe sur un caractère (point de code), comme mb_substr en PHP.
    function cut(text, max) {
        const characters = Array.from(text);
        return characters.length > max ? characters.slice(0, max).join('') : text;
    }

    function chosenOption(questions, questionKey, answers) {
        if (typeof questionKey !== 'string' || questionKey === '') return null;
        const answer = has(answers, questionKey) ? answers[questionKey] : null;
        if (typeof answer !== 'string' || answer === '' || answer === 'unknown') return null;
        const question = has(questions, questionKey) ? questions[questionKey] : null;
        const options = question && has(question, 'options') ? question.options : null;
        const option = has(options, answer) ? options[answer] : null;
        return option !== null && typeof option === 'object' ? option : null;
    }

    function isValidDate(year, month, day) {
        if (month < 1 || month > 12 || day < 1) return false;
        const leap = (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
        const lengths = [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        return day <= lengths[month - 1];
    }

    function whenSentence(input) {
        const when = typeof input.when === 'string' ? input.when : '';
        let base;

        if (when === 'date') {
            const date = typeof input.desired_date === 'string' ? input.desired_date : '';
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(date);
            if (!match || !isValidDate(Number(match[1]), Number(match[2]), Number(match[3]))) return null;
            base = 'J’ai besoin d’aide le ' + Number(match[3]) + ' ' + MONTHS[Number(match[2]) - 1];
        } else if (has(WHEN, when)) {
            base = WHEN[when];
        } else {
            return null;
        }

        const window = typeof input.time_window === 'string' && has(TIME_WINDOW, input.time_window) ? TIME_WINDOW[input.time_window] : '';
        return base + window + '.';
    }

    function priceSentence(input) {
        if (input.price_type === 'negotiable') return 'Le prix est à discuter.';
        if (input.price_type !== 'fixed') return null;

        const raw = input.price;
        if (typeof raw !== 'string' && typeof raw !== 'number') return null;
        // Lecture sur la forme écrite, comme en PHP : 2 décimales au plus, point ou virgule.
        const match = /^(\d+)(?:[.,](\d{1,2}))?$/.exec(normalize(raw));
        if (!match) return null;

        const units = match[1].replace(/^0+/, '') || '0';
        const cents = (match[2] || '').padEnd(2, '0');
        if (units === '0' && cents === '00') return null;

        return 'Je propose ' + (cents === '00' ? units : units + ',' + cents) + ' €.';
    }

    function compose(catalog, input) {
        input = input && typeof input === 'object' ? input : {};
        const situations = catalog && has(catalog, 'situations') ? catalog.situations : null;
        const situationKey = typeof input.situation_key === 'string' ? input.situation_key : '';
        const situation = has(situations, situationKey) ? situations[situationKey] : null;

        if (situation === null || typeof situation !== 'object') {
            return {title: '', description: '', sentences: [], version: VERSION};
        }

        const answers = input.answers && typeof input.answers === 'object' ? input.answers : {};
        const questions = catalog.questions && typeof catalog.questions === 'object' ? catalog.questions : {};
        const city = normalize(input.city);
        const detail = cut(normalize(input.detail), DETAIL_MAX).replace(/ +$/, '');
        const titleOption = chosenOption(questions, situation.title_question, answers);

        let title = String(situation.title || '') + String((titleOption && titleOption.title_part) || '');
        if (city !== '') title += ' à ' + city;
        title = cut(normalize(title), TITLE_MAX);

        const sentences = [];
        const opening = normalize(String(situation.opening || '').split('{fragment}').join(String((titleOption && titleOption.fragment) || '')));
        if (opening !== '') sentences.push(opening);

        (Array.isArray(situation.questions) ? situation.questions : []).forEach((questionKey) => {
            const option = chosenOption(questions, questionKey, answers);
            const sentence = normalize(option ? option.sentence : '');
            if (sentence !== '') sentences.push(sentence);
        });

        const moment = whenSentence(input);
        if (moment !== null) sentences.push(moment);

        const price = priceSentence(input);
        if (price !== null) sentences.push(price);

        const parts = ['Bonjour.'].concat(sentences);
        if (detail !== '') parts.push(detail);

        return {title, description: parts.join(' '), sentences, version: VERSION};
    }

    return {compose, normalize, VERSION};
});
