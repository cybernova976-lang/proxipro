// Requires Playwright in NODE_PATH. Uses guest pages only; no account or message is created.
// node tests/Browser/public-home-publications.cjs http://127.0.0.1:8000/ local
const assert = require('node:assert/strict');
const {mkdir, writeFile, readFile} = require('node:fs/promises');
const {createHash} = require('node:crypto');
const {chromium} = require('playwright');

(async () => {
    const target = process.argv[2] || 'http://127.0.0.1:8000/';
    const prefix = process.argv[3] || 'local';
    const browser = await chromium.launch({channel: 'chrome', headless: true});
    await mkdir('tmp', {recursive: true});
    try {
        const context = await browser.newContext({locale: 'fr-FR', serviceWorkers: 'block'});
        context.setDefaultTimeout(20000);
        await context.route('**/usage/events', route => route.fulfill({status: 204, body: ''}));
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const checks = [];
        for (const width of [1906, 1440, 1024, 768, 390, 375, 360]) {
            await page.setViewportSize({width, height: 900});
            const response = await page.goto(target, {waitUntil: 'domcontentloaded'});
            await page.evaluate(() => document.fonts.ready);
            await page.locator('[data-publication-image]').evaluateAll(images => images.forEach(image => { image.loading = 'eager'; }));
            await page.waitForFunction(() => [...document.querySelectorAll('[data-publication-image]')].every(image => image.complete));
            const result = await page.evaluate(() => {
                const section = document.querySelector('#publications');
                const box = section?.getBoundingClientRect();
                const title = document.querySelector('.hero-title');
                const titleRange = document.createRange(); titleRange.selectNodeContents(title);
                return {
                    width: innerWidth, scrollWidth: document.documentElement.scrollWidth, height: document.documentElement.scrollHeight,
                    titleLines: new Set([...titleRange.getClientRects()].filter(r => r.width > 0).map(r => Math.round(r.top))).size,
                    sectionTop: box?.top, sectionBottom: box?.bottom, categoriesTop: document.querySelector('#services').getBoundingClientRect().top,
                    cards: [...document.querySelectorAll('#publications article')].map(card => ({
                        id: card.dataset.publicationId, type: card.dataset.publicationType,
                        title: card.querySelector('h3').textContent.trim(), description: card.querySelector('.home-publication-excerpt').textContent.trim(),
                        details: card.querySelector('.home-publication-details').textContent.trim(), author: card.querySelector('.home-publication-author').textContent.trim(),
                        contact: card.querySelector('.home-publication-contact').href,
                        photo: card.querySelector('[data-publication-image]')?.src || null,
                        photoLoaded: Boolean(card.querySelector('[data-publication-image]')?.naturalWidth),
                        placeholderVisible: [...card.querySelectorAll('.home-publication-photo-placeholder')].some(el => !el.hidden),
                        left: card.getBoundingClientRect().left, right: card.getBoundingClientRect().right,
                    })),
                };
            });
            assert.equal(response.status(), 200);
            assert.equal(result.scrollWidth, width);
            assert.ok(result.cards.length >= 1 && result.cards.length <= 4);
            assert.ok(result.sectionBottom <= result.categoriesTop);
            assert.ok(result.sectionTop < 900, 'Member publications must follow the hero immediately');
            if (width >= 992) assert.equal(result.titleLines, 1);
            for (const type of ['offre', 'demande']) assert.ok(result.cards.filter(card => card.type === type).length <= 2);
            for (const card of result.cards) {
                assert.ok(card.title && card.description && card.details && card.author);
                assert.ok(card.photoLoaded || card.placeholderVisible, 'An original photo or a visible neutral placeholder is required');
                assert.ok(card.left >= -1 && card.right <= width + 1);
                const contact = new URL(card.contact);
                assert.equal(contact.origin, new URL(target).origin);
                assert.equal(contact.pathname, '/login');
                assert.equal(contact.searchParams.get('continue'), 'ad');
                assert.equal(contact.searchParams.get('ad'), card.id);
            }
            await page.locator('#publications').screenshot({path: `tmp/publications-${prefix}-${width}.png`});
            if (width === 375 || width === 1440) await page.screenshot({path: `tmp/publications-${prefix}-${width}-full.png`, fullPage: true});
            checks.push(result);
            console.log(`Passed ${width}px: ${result.cards.length} member publications, page height ${result.height}`);
        }

        const withPhoto = checks.at(-1).cards.find(card => card.photoLoaded);
        assert.ok(withPhoto, 'The photo-enabled fixture or production selection must contain an original photo');
        await page.route(withPhoto.photo, route => route.abort());
        await page.reload({waitUntil: 'domcontentloaded'});
        const brokenPhoto = page.locator(`[data-publication-id="${withPhoto.id}"] [data-publication-image]`);
        await brokenPhoto.evaluate(image => { image.loading = 'eager'; });
        await page.waitForFunction(id => document.querySelector(`[data-publication-id="${id}"] [data-publication-image]`)?.hidden, withPhoto.id);
        assert.equal(await page.locator(`[data-publication-id="${withPhoto.id}"] [data-publication-image-fallback]`).isVisible(), true);
        await page.unroute(withPhoto.photo);
        await page.goto(target, {waitUntil: 'domcontentloaded'});

        const selected = checks.at(-1).cards[0];
        await page.locator(`[data-publication-id="${selected.id}"] .home-publication-contact`).click();
        await page.waitForURL(url => url.pathname === '/login' && url.searchParams.get('ad') === selected.id);
        assert.ok((await page.locator('body').innerText()).includes(selected.title));
        await page.getByRole('link', {name: 'Créer un compte', exact: true}).click();
        await page.waitForURL(url => url.pathname === '/register');
        assert.ok((await page.locator('body').innerText()).includes(selected.title), 'The selected publication must survive switching to registration');
        await page.screenshot({path: `tmp/publications-${prefix}-register.png`, fullPage: true});

        const http = [];
        if (target.startsWith('https://')) {
            for (const path of ['/', '/up', '/css/public-home.css?v=20261027-publication-photos1', '/js/public-home.js?v=20261027-publication-photos1']) {
                const response = await context.request.get(new URL(path, target).href);
                assert.equal(response.status(), 200);
                if (path.startsWith('/css/') || path.startsWith('/js/')) assert.equal(createHash('sha256').update(await response.body()).digest('hex'), createHash('sha256').update(await readFile('public' + path.split('?')[0])).digest('hex'));
                http.push({path, status: response.status()});
            }
        }
        assert.deepEqual(errors, []);
        await writeFile(`tmp/publications-${prefix}-checks.json`, JSON.stringify({checks, selectedPublication: selected.id, registrationPreservesPublication: true, http, errors}, null, 2));
        console.log(`Passed contact -> login -> registration for publication ${selected.id}`);
    } finally {
        await Promise.race([browser.close(), new Promise(resolve => setTimeout(resolve, 5000))]);
    }
})().then(() => process.exit(0)).catch(error => { console.error(error); process.exit(1); });
