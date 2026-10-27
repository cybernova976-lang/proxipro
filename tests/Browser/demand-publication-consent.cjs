// Run against a guest-accessible app with Playwright available in NODE_PATH:
// node tests/Browser/demand-publication-consent.cjs http://127.0.0.1:8000 local
// Uses a separate browser context, saves only a local draft, never publishes.
const assert = require('node:assert/strict');
const {mkdir, writeFile} = require('node:fs/promises');
const {chromium} = require('playwright');

(async () => {
    const target = process.argv[2] || 'http://127.0.0.1:8000/';
    const prefix = process.argv[3] || 'local';
    const browser = await chromium.launch({channel: 'chrome', headless: true});
    const results = [];
    await mkdir('tmp', {recursive: true});

    try {
        for (const width of [1440, 375]) {
            const context = await browser.newContext({viewport: {width, height: 900}, locale: 'fr-FR', serviceWorkers: 'block'});
            context.setDefaultTimeout(15000);
            await context.route('**/usage/events', route => route.fulfill({status: 204, body: ''}));
            const page = await context.newPage();
            const errors = [];
            const authRequests = [];
            const publications = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('request', request => {
                const url = new URL(request.url());
                if (request.isNavigationRequest() && url.pathname === '/login') authRequests.push(url.href);
                if (request.method() === 'POST' && url.pathname === '/demande') publications.push(url.href);
            });

            await page.goto(target, {waitUntil: 'domcontentloaded'});
            console.log(`Checking guest publication at ${width}px`);
            await page.getByRole('link', {name: 'Publier mon besoin', exact: true}).first().click();
            await page.locator('.demand-cat-btn[data-cat="Bricolage & Travaux"]').click();
            await page.getByRole('button', {name: 'Plombier', exact: true}).click();
            const next = page.locator('#demandBtnNext');
            await next.click();
            await page.locator('#demandCountry').selectOption('Mayotte');
            await page.locator('#demandCity').selectOption('Mamoudzou');
            const date = await page.locator('#demandDesiredDate').getAttribute('min');
            await page.locator('#demandDesiredDate').fill(date);
            await page.locator('#demandTimeWindow').selectOption('flexible');
            await next.click();
            await page.locator('#demandTitle').fill('Réparer une fuite sous mon évier');
            await page.locator('#demandDesc').fill('Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.');
            await page.locator('[data-service-detail="work_scope"]').selectOption('repair');
            await page.locator('[data-service-detail="site_type"]').selectOption('house');
            await next.click();
            await next.click();

            const confirmation = page.locator('#publicationConfirmed');
            const submit = page.getByRole('button', {name: 'Continuer', exact: true});
            assert.equal(await confirmation.isChecked(), false);
            assert.equal(await submit.getAttribute('type'), 'submit');
            const demandURL = page.url();

            for (const action of ['click', 'keyboard', 'requestSubmit']) {
                if (action === 'click') await submit.click();
                if (action === 'keyboard') await submit.press('Enter');
                if (action === 'requestSubmit') await page.locator('#demandForm').evaluate(form => form.requestSubmit());
                assert.equal(page.url(), demandURL, `${action} must stay on the demand`);
                assert.equal(authRequests.length, 0, `${action} must not navigate to login`);
                assert.equal(await confirmation.getAttribute('aria-invalid'), 'true');
                assert.equal(await page.locator('#step5Error').isVisible(), true);
                assert.match(await page.locator('#step5Error').innerText(), /Confirmez les informations/);
            }
            await confirmation.scrollIntoViewIfNeeded();
            await page.screenshot({path: `tmp/consent-${prefix}-${width}-unchecked.png`});
            await confirmation.check();
            await Promise.all([
                page.waitForURL(url => url.pathname === '/login' && url.searchParams.get('continue') === 'demand'),
                submit.click(),
            ]);
            assert.equal(authRequests.length, 1);
            assert.equal(publications.length, 0);
            const draft = await page.evaluate(() => JSON.parse(localStorage.getItem('prokejem-demand-draft-v2-guest')));
            assert.equal(draft.title, 'Réparer une fuite sous mon évier');
            assert.equal(draft.current_step, 5);
            assert.equal(Object.hasOwn(draft, 'publication_confirmed'), false);

            await page.goto(new URL('/demande', target).href, {waitUntil: 'domcontentloaded'});
            assert.equal(await confirmation.isChecked(), false, 'Restoring a draft must not grant publication consent');
            assert.equal(await page.locator('#demandTitle').inputValue(), draft.title);
            assert.deepEqual(errors, []);
            results.push({width, blocked: ['click', 'keyboard', 'requestSubmit'], acceptedRedirect: authRequests[0], draftPreserved: true, restoredConsent: false, publications: publications.length, errors});
            console.log(`Passed guest consent at ${width}px: unconfirmed stays, confirmed continues, draft preserved`);
            await context.close();
        }
        await writeFile(`tmp/consent-${prefix}-checks.json`, JSON.stringify(results, null, 2));
    } finally {
        await Promise.race([browser.close(), new Promise(resolve => setTimeout(resolve, 5000))]);
    }
})().then(() => process.exit(0)).catch(error => { console.error(error); process.exit(1); });
