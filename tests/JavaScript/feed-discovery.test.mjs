import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../public/js/feed.js', import.meta.url), 'utf8');

function page(role = 'client') {
    const events = {};
    const form = { addEventListener: (name, handler) => { events[name] = handler; }, contains: () => false };
    const field = { value: '', setAttribute() {}, addEventListener() {} };
    const panel = { hidden: true, addEventListener() {} };
    const quick = { dataset: { pkCategory: 'Bricolage & Travaux' }, addEventListener: (_, handler) => { events.quick = handler; } };
    const root = { querySelectorAll: () => [quick], addEventListener() {} };
    const config = { role, requestsUrl: '/ads?type=demandes', professionalsUrl: '/feed/professionals?city=Mamoudzou&country=Mayotte',
        categories: [{ parent: 'Bricolage & Travaux', sub: 'Plombier', label: 'Plombier', search: 'plombier plomberie' }] };
    const elements = { pkFeed: root, pkIntentForm: form, pkIntentField: field, pkSuggest: panel, pkFeedConfig: { textContent: JSON.stringify(config) } };
    const window = { location: { href: '' } };
    runInNewContext(source, { document: { getElementById: id => elements[id], querySelector: () => null, addEventListener() {} }, window });
    return { window, submit(value) { field.value = value; events.submit({ preventDefault() {} }); return new URL(window.location.href, 'https://example.test'); },
        quick() { events.quick.call(quick); return new URL(window.location.href, 'https://example.test'); } };
}

test('client search opens filtered profiles, not publication', () => {
    const url = page().submit('Plomberie');
    assert.equal(url.pathname, '/feed/professionals');
    assert.equal(url.searchParams.get('subcategory'), 'Plombier');
    assert.equal(url.searchParams.get('city'), 'Mamoudzou');
    assert.equal(url.searchParams.get('country'), 'Mayotte');
});

test('an unknown search term is preserved and safely encoded', () => {
    const url = page().submit('  Nadia & fils  ');
    assert.equal(url.searchParams.get('q'), 'Nadia & fils');
    assert.equal(url.searchParams.get('city'), 'Mamoudzou');
});

test('quick categories open the directory without publishing', () => {
    const url = page().quick();
    assert.equal(url.pathname, '/feed/professionals');
    assert.equal(url.searchParams.get('category'), 'Bricolage & Travaux');
});

test('provider search still opens compatible requests', () => {
    const url = page('provider').submit('Plombier');
    assert.equal(url.pathname, '/ads');
    assert.equal(url.searchParams.get('type'), 'demandes');
    assert.equal(url.searchParams.get('search'), 'Plombier');
});

test('an empty client search opens the directory with the local context', () => {
    const url = page().submit('');
    assert.equal(url.pathname, '/feed/professionals');
    assert.equal(url.searchParams.get('city'), 'Mamoudzou');
    assert.equal(url.searchParams.has('q'), false);
});
