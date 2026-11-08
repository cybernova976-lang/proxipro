import assert from 'node:assert/strict';
import test from 'node:test';
import '../../public/js/pk-search.js';

const {normalize, match, suggest} = globalThis.ProkejemSearch;

const index = {
    situations: [
        {key: 'fuite-eau', label: 'Fuite d’eau', url: '/demande?situation=fuite-eau', search: ['fuite d eau', 'fuite', 'robinet', 'plomberie']},
        {key: 'wc-bouche', label: 'WC bouché', url: '/demande?situation=wc-bouche', search: ['wc bouche', 'toilettes', 'wc']},
        {key: 'menage', label: 'Ménage', url: '/demande?situation=menage', search: ['menage', 'nettoyage']},
        {key: 'clim', label: 'Clim', url: '/demande?situation=clim', search: ['clim', 'climatisation', 'climatiseur']},
        {key: 'telephone-casse', label: 'Téléphone cassé', url: '/demande?situation=telephone-casse', search: ['telephone casse', 'portable', 'ecran']},
    ],
    trades: [
        {label: 'Électricien', parent: 'Bricolage & Travaux', url: '/demande?category=x&subcategory=Electricien'},
        {label: 'Plombier', parent: 'Bricolage & Travaux', url: '/demande?category=x&subcategory=Plombier'},
    ],
    providersUrl: '/feed/professionals',
    providersLabel: 'Voir les prestataires à Mayotte',
    fallbackUrl: '/demande',
    fallbackLabel: 'Je ne trouve pas : décrire mon besoin',
};

const keys = (rows) => rows.filter((r) => r.kind === 'situation').map((r) => r.key);

test('normalize removes accents, ligatures and punctuation', () => {
    assert.equal(normalize('  Œuvre d’ÉLÉCTRICITÉ ! '), 'oeuvre d electricite');
});

test('accents are optional', () => {
    assert.deepEqual(keys(suggest('menage', index)), ['menage']);
});

test('one typo, plurals and doubled letters are tolerated', () => {
    assert.ok(keys(suggest('fuitte', index)).includes('fuite-eau'));
    assert.ok(keys(suggest('fuites', index)).includes('fuite-eau'));
    assert.ok(keys(suggest('menaage', index)).includes('menage'));
    assert.ok(keys(suggest('climatisaton', index)).includes('clim'));
    assert.ok(suggest('elektricien', index).some((r) => r.label === 'Électricien'));
});

test('synonyms lead to the right situation', () => {
    assert.ok(keys(suggest('toilettes', index)).includes('wc-bouche'));
    assert.ok(keys(suggest('portable', index)).includes('telephone-casse'));
});

test('order is situations, trades, providers, then the fallback last', () => {
    const rows = suggest('plomb', index);
    const order = rows.map((r) => r.kind);
    assert.deepEqual(order, ['situation', 'trade', 'providers', 'fallback']);
    assert.ok(rows.length <= 6);
});

test('short or unknown terms', () => {
    assert.deepEqual(suggest('f', index), []);
    const rows = suggest('xyzw', index);
    assert.equal(rows.length, 1);
    assert.equal(rows[0].kind, 'fallback');
});

test('no providers line without a providers url', () => {
    const rows = suggest('fuite', {...index, providersUrl: null});
    assert.ok(!rows.some((r) => r.kind === 'providers'));
});

test('typed text never ends up in a produced url', () => {
    for (const row of suggest('fuite chez moi', index)) {
        assert.ok(!row.url.includes('chez'));
    }
    assert.equal(match('', ['fuite']), false);
});
