import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// Unit-level DOM doubles: exercise the actual editor script, not a browser or a file picker.
class Element {
    constructor(tag = 'div') {
        this.tag = tag;
        this.children = [];
        this.dataset = {};
        this.listeners = {};
        this.classList = { toggle() {} };
    }
    addEventListener(type, listener) { this.listeners[type] = listener; }
    fire(type) { this.listeners[type]?.call(this); }
    setAttribute(name, value) { this[name] = value; }
    appendChild(child) { child.parent = this; this.children.push(child); }
    insertBefore(child, before) {
        child.parent = this;
        this.children.splice(this.children.indexOf(before), 0, child);
    }
    remove() { this.parent.children.splice(this.parent.children.indexOf(this), 1); }
    querySelectorAll() { return this.children.filter(child => child.dataset.newRealization); }
}

function editor(remainingSlots = 4) {
    const ids = {};
    for (const id of ['professionalRealizationPhotos', 'professionalGalleryGrid', 'professionalGalleryAdd',
        'professionalGalleryCount', 'professionalGalleryAvailable', 'professionalGallerySelection']) ids[id] = new Element();
    ids.professionalRealizationPhotos.dataset.remainingSlots = String(remainingSlots);
    ids.professionalGalleryGrid.appendChild(ids.professionalGalleryAdd);
    const template = readFileSync(new URL('../../resources/views/profile/edit.blade.php', import.meta.url), 'utf8');
    const start = template.indexOf('const professionalGalleryInput =');
    const end = template.indexOf('const cropModalEl =', start);
    assert.ok(start >= 0 && end > start, 'Locate the real gallery editor script');
    vm.runInNewContext(template.slice(start, end), {
        document: { getElementById: id => ids[id], createElement: tag => new Element(tag) },
        URL: { createObjectURL: file => `blob:${file.name}`, revokeObjectURL() {} },
        DataTransfer: class {
            files = [];
            items = { add: file => this.files.push(file) };
        },
    });
    return {
        select(files) { ids.professionalRealizationPhotos.files = files; ids.professionalRealizationPhotos.fire('change'); },
        files: () => ids.professionalRealizationPhotos.files,
        cards: () => ids.professionalGalleryGrid.querySelectorAll(),
        count: () => ids.professionalGalleryCount.textContent,
        feedback: () => ids.professionalGallerySelection.textContent,
        addHidden: () => ids.professionalGalleryAdd.hidden,
    };
}

const photo = (name, extra = {}) => ({ name, size: 1000, lastModified: 42, type: 'image/png', ...extra });
const caption = card => card.children.find(child => child.tag === 'label').children[0];

test('successive selections accumulate files and retain each caption', () => {
    const gallery = editor();
    const first = photo('one.png');
    gallery.select([first, photo('two.png')]);
    caption(gallery.cards()[0]).value = 'Salle de bain';
    caption(gallery.cards()[0]).fire('input');
    gallery.select([photo('three.png')]);
    assert.deepEqual(Array.from(gallery.files(), file => file.name), ['one.png', 'two.png', 'three.png']);
    assert.equal(caption(gallery.cards()[0]).value, 'Salle de bain');
    assert.equal(gallery.count(), '5/6');
});

test('removing a pending photo reindexes fields without moving another photo caption', () => {
    const gallery = editor();
    gallery.select([photo('one.png'), photo('two.png')]);
    caption(gallery.cards()[1]).value = 'Carrelage';
    caption(gallery.cards()[1]).fire('input');
    gallery.cards()[0].children.find(child => child.tag === 'button').fire('click');
    assert.equal(gallery.files()[0].name, 'two.png');
    assert.equal(caption(gallery.cards()[0]).value, 'Carrelage');
    assert.equal(caption(gallery.cards()[0]).name, 'professional_realization_captions[0]');
});

test('existing photos count toward six and duplicates or invalid files are ignored', () => {
    const gallery = editor(2);
    const first = photo('one.png');
    gallery.select([first, first, photo('document.pdf', { type: 'application/pdf' }),
        photo('large.png', { size: 6 * 1024 * 1024 }), photo('two.png'), photo('extra.png')]);
    assert.deepEqual(Array.from(gallery.files(), file => file.name), ['one.png', 'two.png']);
    assert.equal(gallery.count(), '6/6');
    assert.equal(gallery.addHidden(), true);
    assert.match(gallery.feedback(), /3 fichier\(s\) ignoré\(s\)/);
    gallery.cards()[0].children.find(child => child.tag === 'button').fire('click');
    assert.equal(gallery.count(), '5/6');
    assert.equal(gallery.addHidden(), false);
});
