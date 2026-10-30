import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { resolve } from 'node:path';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });

test('real search-type controls debounce tenant requests and retain widget multiselect values', async ({ page }) => {
    const fixture = JSON.parse(execFileSync('php', ['tests/Browser/render-gsc-search-types.php'], { encoding: 'utf8' }));
    const html = `<html><body>
        <main x-data="explorer()">${fixture.explorer}</main>
        <section x-data="widget()"><div x-data="{flt: {dimension:'dimensions.search_type', operator:'in', value:[]}}">${fixture.widget}<output id="selected" x-text="JSON.stringify(flt.value)"></output></div></section>
        <script type="module">
        import Alpine from '/node_modules/alpinejs/dist/module.esm.js';
        import {gscDashboard} from '/resources/js/dashboards/gsc-dashboard.js';
        import {dashboardBuilder} from '/resources/js/dashboards/dashboard-builder.js';
        import {uiAssetSelector} from '/resources/js/components/ui-asset-selector.js';
        Alpine.data('uiAssetSelector',uiAssetSelector);
        window.explorer = () => gscDashboard({tenantId:1,account:'42',dateStart:'2026-10-01',dateEnd:'2026-10-28',searchTypesEnabled:true,analyticsCatalog:${JSON.stringify(fixture.catalog)}});
        window.widget = () => { const value = dashboardBuilder({tenant:'test',gscSearchTypeOptions:${JSON.stringify(fixture.options)}}); value.init=()=>{}; value.filterModalChannel='google_search_console'; value.markWidgetControlsDirty=()=>{}; return value; };
        Alpine.start();
        </script></body></html>`;
    const payloads = [];
    let hold = true;
    const waiting = [];
    const reply = response => { response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({summary:{},chart:[],table:[]})); };
    const server = createServer((request, response) => {
        if (request.url === '/') return response.end(html);
        if (request.url.startsWith('/api/gsc/')) {
            let body = '';
            request.on('data', chunk => body += chunk);
            request.on('end', () => { payloads.push(JSON.parse(body)); if (hold) waiting.push(response); else reply(response); });
            return;
        }
        if (!request.url.startsWith('/resources/js/') && !request.url.startsWith('/node_modules/alpinejs/')) return response.writeHead(404).end();
        try { response.setHeader('Content-Type', 'text/javascript'); response.end(readFileSync(resolve('.'+request.url+(request.url.endsWith('.js') ? '' : '.js')))); }
        catch { response.writeHead(404).end(); }
    });
    await new Promise(done => server.listen(0, '127.0.0.1', done));
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.goto(`http://127.0.0.1:${server.address().port}`);
        const select = page.getByLabel('Search type', {exact:true});
        await select.selectOption('image');
        await select.selectOption('video');
        await expect.poll(() => payloads.length).toBe(2);
        await expect(select).toBeDisabled();
        hold = false;
        waiting.splice(0).forEach(reply);
        await expect.poll(() => payloads.length).toBe(3);
        await expect(select).toBeEnabled();
        expect(payloads.every(payload => payload.searchType === 'video')).toBe(true);
        await page.getByRole('button', {name:'Select search types',exact:true}).click();
        await page.getByText('Images', {exact:true}).last().click();
        await page.getByText('Videos', {exact:true}).last().click();
        await expect(page.locator('#selected')).toHaveText('["image","video"]');
        expect(errors).toEqual([]);
    } finally {
        hold = false;
        waiting.splice(0).forEach(reply);
        await page.close();
        await new Promise(done => server.close(done));
    }
});
