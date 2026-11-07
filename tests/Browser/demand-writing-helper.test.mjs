import assert from 'node:assert/strict';
import test from 'node:test';
import '../../public/js/demand-writing-helper.js';

const {examplesFor, applyExample} = globalThis.ProkejemDemandWriting;
const example = {label: 'Un robinet fuit', title: 'Réparer un robinet', description: 'Mon robinet fuit.'};

test('a chosen example prepares both empty fields without inventing a budget', () => {
    assert.deepEqual(applyExample('', '', example), {
        title: example.title, description: example.description, status: 'added',
    });
});

test('a draft title and every existing description character are preserved', () => {
    const existing = '  La fuite est sous mon évier.\n';
    const result = applyExample('Mon titre à conserver', existing, example);
    assert.equal(result.title, 'Mon titre à conserver');
    assert.equal(result.description, existing + '\n\nMon robinet fuit.');
});

test('choosing an example twice does not duplicate the text or change a title', () => {
    const result = applyExample('Titre', 'Mon robinet fuit.\nPrécision personnelle.', example);
    assert.equal(result.status, 'duplicate');
    assert.equal(result.description, 'Mon robinet fuit.\nPrécision personnelle.');
    assert.equal(result.title, 'Titre');
});

test('an example exceeding the field limit never truncates the user draft', () => {
    const description = 'x'.repeat(1990);
    assert.deepEqual(applyExample('', description, example), {title: '', description, status: 'too_long'});
});

test('suggestions follow the selected service with a neutral fallback', () => {
    assert.deepEqual(examplesFor(null, {}), []);
    const catalogue = {Plombier: [[example.label, example.title, example.description]]};
    assert.deepEqual(examplesFor('Plombier', catalogue), [example]);
    const fallback = examplesFor('Professeur de français', catalogue);
    assert.equal(fallback.length, 2);
    assert.ok(fallback.every(item => item.description.includes('Professeur de français')));
    assert.ok(fallback.every(item => !item.description.includes('robinet')));
});
