import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
import '../../public/js/demand-text-composer.js';

// Même fixture que tests/Unit/Demand/DemandTextComposerTest.php : le texte doit être identique.
const fixture = JSON.parse(fs.readFileSync(new URL('../fixtures/demand-text-cases.json', import.meta.url), 'utf8'));
const {compose} = globalThis.ProkejemDemandText;

test('the shared fixture has at least fourteen cases', () => {
    assert.ok(fixture.cases.length >= 14);
});

for (const {name, input, expected} of fixture.cases) {
    test(`fixture case « ${name} » gives the same text as PHP`, () => {
        const result = compose(fixture.catalog, input);
        assert.deepEqual({title: result.title, description: result.description}, expected);
        assert.equal(result.version, 1);
    });
}

test('unknown or absent answers produce no sentence', () => {
    for (const [key, situation] of Object.entries(fixture.catalog.situations)) {
        const answers = Object.fromEntries(situation.questions.map((question) => [question, 'unknown']));
        const result = compose(fixture.catalog, {situation_key: key, answers});

        assert.deepEqual(result.sentences, [situation.opening.split('{fragment}').join('')], key);
        assert.deepEqual(compose(fixture.catalog, {situation_key: key, answers: {}}), result, key);
        assert.equal(result.title, situation.title, key);
    }
});

test('the composed text is plain text, never markup', () => {
    const result = compose(fixture.catalog, {situation_key: 'menage', answers: {}, detail: '<img src=x onerror=alert(1)>'});
    // Le compositeur rend la chaîne telle quelle : l'aperçu l'insère avec textContent.
    assert.equal(result.description, 'Bonjour. Je cherche une aide pour le ménage. <img src=x onerror=alert(1)>');
});

test('an unknown situation produces an empty text', () => {
    assert.deepEqual(compose(fixture.catalog, {situation_key: 'inconnue'}), {title: '', description: '', sentences: [], version: 1});
    assert.deepEqual(compose(fixture.catalog, null), {title: '', description: '', sentences: [], version: 1});
});
