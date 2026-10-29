import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { resolve } from 'node:path';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });

test('real Blade controls initialize, submit multiselects and bounds, and follow tabs', async ({ page }) => {
    const component = execFileSync('php', ['tests/browser/render-entity-attributes.php'], { encoding: 'utf8' });
    const catalog = {
        'attributes.channeledCampaign.objective': { relation: 'channeledCampaign', field: 'objective', type: 'string', options: { OUTCOME_SALES: 'OUTCOME_SALES', OUTCOME_LEADS: 'OUTCOME_LEADS' } },
        'attributes.channeledCampaign.budget': { relation: 'channeledCampaign', field: 'budget', type: 'float', options: {} },
        'attributes.channeledAd.status': { relation: 'channeledAd', field: 'status', type: 'string', options: { ACTIVE: 'ACTIVE', PAUSED: 'PAUSED' } },
    };
    const html = `<html><body><main id="root" x-data="demoDashboard()">${component}</main>
        <script type="module">
        import Alpine from '/node_modules/alpinejs/dist/module.esm.js';
        import { withEntityAttributes } from '/resources/js/dashboards/entity-attributes.js';
        import { uiAssetSelector } from '/resources/js/components/ui-asset-selector.js';
        window.Alpine = Alpine;
        Alpine.data('uiAssetSelector', uiAssetSelector);
        window.demoDashboard = () => withEntityAttributes({ tenantId: 1, accounts: ['42'], activeTab: 'campaigns', csrfToken: 'test',
            fetchAll() {}, getFetchOptions() { return {body: '{}'}; }, getCacheKey() { return 'test'; }
        }, 'facebook_marketing');
        Alpine.start();
        </script></body></html>`;
    const server = createServer((request, response) => {
        if (request.url === '/') { response.setHeader('Content-Type', 'text/html'); response.end(html); return; }
        if (request.url === '/api/entity-attributes') { response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({status: 'success', data: catalog})); return; }
        const path = request.url.split('?')[0];
        if (!path.startsWith('/resources/js/') && !path.startsWith('/node_modules/alpinejs/')) { response.writeHead(404).end(); return; }
        try {
            response.setHeader('Content-Type', 'text/javascript');
            response.end(readFileSync(resolve('.' + path + (path.endsWith('.js') ? '' : '.js'))));
        } catch { response.writeHead(404).end(); }
    });
    await new Promise(done => server.listen(0, '127.0.0.1', done));
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.goto(`http://127.0.0.1:${server.address().port}`);
        await expect(page.getByText('objective', {exact: true})).toBeVisible();
        await page.getByRole('button', {name: 'All values', exact: true}).click();
        await page.getByText('OUTCOME_SALES', {exact: true}).click();
        await page.getByLabel('Minimum', {exact: true}).fill('10');
        await page.getByLabel('Minimum', {exact: true}).dispatchEvent('change');
        const filters = await page.evaluate(() => JSON.parse(Alpine.$data(document.getElementById('root')).getFetchOptions().body).filters);
        expect(filters['attributes.channeledCampaign.objective'].value).toEqual(['OUTCOME_SALES']);
        expect(filters['attributes.channeledCampaign.budget__1000'].value).toBe('10');
        await page.evaluate(() => { Alpine.$data(document.getElementById('root')).activeTab = 'ads'; });
        await expect(page.getByText('status', {exact: true})).toBeVisible();
        expect(errors).toEqual([]);
    } finally {
        await new Promise(done => server.close(done));
    }
});
