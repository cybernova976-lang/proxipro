// Uses server-rendered fixtures from render-account-review.php, never a real account.
// NODE_PATH must point to the installed Playwright package directory.
const {chromium} = require('playwright');
const fs = require('node:fs/promises');
const path = require('node:path');
const http = require('node:http');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const fixtureDir = path.join(root, 'tmp/account-review');
(async () => {
    const requestedPages = process.env.REVIEW_PAGES?.split(',');
    const fixtures = JSON.parse(await fs.readFile(path.join(fixtureDir, 'manifest.json')));
    const manifest = requestedPages ? fixtures.filter(page => requestedPages.includes(page.name)) : fixtures;
    assert.ok(manifest.length && (!requestedPages || manifest.length === requestedPages.length), 'Unknown review page');
    const reportName = requestedPages ? 'browser-focused.json' : 'browser-results.json';
    const server = http.createServer(async (req, res) => {
        try {
            const pathname = new URL(req.url, 'http://localhost').pathname;
            const name = pathname.match(/^\/review\/([a-z-]+)$/)?.[1];
            const target = name ? path.join(fixtureDir, name + '.html') : path.resolve(root, 'public', '.' + decodeURIComponent(pathname));
            if (!name && !target.startsWith(path.join(root, 'public') + path.sep)) { res.writeHead(404).end(); return; }
            const content = await fs.readFile(target);
            const types = {'.css':'text/css', '.js':'text/javascript', '.svg':'image/svg+xml', '.png':'image/png', '.html':'text/html', '.json':'application/json', '.woff2':'font/woff2'};
            res.writeHead(200, {'Content-Type': types[path.extname(target)] || 'application/octet-stream'}).end(content);
        } catch { res.writeHead(404).end(); }
    });
    await new Promise(resolve => server.listen(8096, '127.0.0.1', resolve));
    const browser = await chromium.launch({channel:'chrome', headless:true});
    const checks = [];
    try {
        const context = await browser.newContext({locale:'fr-FR', serviceWorkers:'block'});
        const assetCache = new Map();
        const unavailableExternalAssets = new Set();
        await context.route('**/*', async route => {
            if (route.request().method() !== 'GET') return route.fulfill({status:204, body:''});
            const url = route.request().url();
            if (process.env.REVIEW_BLOCK_CHART === '1' && url.includes('/chart.js@')) {
                return route.abort('blockedbyclient');
            }
            if (!url.startsWith('http://127.0.0.1:8096/')) {
                if (!assetCache.has(url)) {
                    try {
                        const response = await route.fetch({timeout:8000});
                        assetCache.set(url, {status:response.status(), headers:response.headers(), body:await response.body()});
                    } catch {
                        unavailableExternalAssets.add(url);
                        assetCache.set(url, null);
                    }
                }
                const cached = assetCache.get(url);
                return cached ? route.fulfill(cached) : route.abort('timedout');
            }
            return route.continue();
        });
        const page = await context.newPage();
        for (const width of (process.env.REVIEW_WIDTHS || '1440,768,390,360').split(',').map(Number)) {
            await page.setViewportSize({width, height:1000});
            for (const fixture of manifest) {
                const errors = [];
                const onError = error => errors.push(error.message);
                page.on('pageerror', onError);
                await page.goto('http://127.0.0.1:8096/review/' + fixture.name, {waitUntil:'domcontentloaded'});
                await page.waitForLoadState('load', {timeout:10000}).catch(() => {});
                await page.evaluate(() => Promise.race([document.fonts.ready, new Promise(resolve=>setTimeout(resolve,5000))]));
                const result = await page.evaluate(() => ({viewport:innerWidth, width:document.documentElement.scrollWidth,
                    headings:[...document.querySelectorAll('h1')].map(el=>el.textContent.trim()),
                    overflow:[...document.querySelectorAll('main *, .account-center *, .pro-content *')].filter(el=>{
                        const r=el.getBoundingClientRect();return r.width && r.right>innerWidth+2 && getComputedStyle(el).position!=='fixed';
                    }).slice(0,5).map(el=>({tag:el.tagName,cls:el.className}))}));
                checks.push({page:fixture.name,width,...result,errors});
                if (fixture.name === 'analytics' && process.env.REVIEW_BLOCK_CHART === '1') {
                    assert.ok(await page.locator('[data-chart-values="revenueChart"]').isVisible());
                    assert.equal(await page.locator('[data-chart-values="revenueChart"] tbody tr').count(), 12);
                    assert.ok(await page.locator('[data-chart-values="clientsChart"]').isVisible());
                    assert.equal(await page.locator('[data-chart-values="clientsChart"] tbody tr').count(), 6);
                    assert.match(await page.locator('[data-chart-summary="conversionChart"]').innerText(), /\d+ acceptés/);
                    assert.equal(await page.locator('#revenueChart:visible, #clientsChart:visible, #conversionChart:visible').count(), 0);
                }
                if (fixture.name === 'admin-reminders') {
                    assert.ok(await page.getByRole('button', {name:'Préparer les rappels dus'}).isVisible());
                }
                if (fixture.name === 'offers') {
                    const comparison = page.locator('.premium-comparison');
                    assert.equal(await comparison.locator('.plan-name').first().isVisible(), width <= 575);
                    if (width <= 575) {
                        const row = comparison.locator('tbody tr').first();
                        const heading = await row.locator('th').boundingBox();
                        const value = await row.locator('td').first().boundingBox();
                        assert.ok(heading.y + heading.height <= value.y + 1, 'The feature should span the mobile row');
                    }
                    await comparison.screenshot({path:path.join(fixtureDir, `comparison-${width}.png`)});
                }
                if (['settings','publications','offers','analytics','admin-reminders','subscription-free','subscription-active','subscription-past-due','subscription-cancelled'].includes(fixture.name)) {
                    await page.screenshot({path:path.join(fixtureDir, `${fixture.name}-${width}.png`),fullPage:true});
                }
                page.off('pageerror',onError);
                await fs.writeFile(path.join(fixtureDir,reportName), JSON.stringify(checks,null,2));
            }
            console.log(`Checked ${width}px: ${manifest.length} pages`);
        }
        await fs.writeFile(path.join(fixtureDir,reportName), JSON.stringify(checks,null,2));
        const failures = checks.filter(c=>c.width!==c.viewport||c.errors.length);
        const assetReport = requestedPages ? 'external-assets-focused.json' : 'external-assets.json';
        await fs.writeFile(path.join(fixtureDir,assetReport), JSON.stringify([...unavailableExternalAssets],null,2));
        console.log(JSON.stringify({checks:checks.length,failures,unavailableExternalAssets:[...unavailableExternalAssets]},null,2));
        assert.equal(failures.length, 0, 'A reviewed page has overflow or JavaScript errors');
    } finally { await browser.close(); await new Promise(resolve=>server.close(resolve)); }
})().catch(error=>{console.error(error);process.exitCode=1;});
