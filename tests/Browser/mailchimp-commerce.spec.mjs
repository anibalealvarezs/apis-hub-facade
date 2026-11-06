import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });

// Real Livewire/Blade output, backed only by synthetic test records and a mocked tenant API.
execFileSync('php', ['vendor/bin/pest', 'tests/Feature/Analytics/MailchimpCommercePresentationTest.php', '--compact'], {
    env: { ...process.env, MAILCHIMP_BROWSER_FIXTURES: '1' }, stdio: 'pipe',
});
const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
const css = [
    'public/css/filament/filament/app.css', 'public/css/filament/support/support.css',
    `public/build/${manifest['resources/css/app.css'].file}`, 'public/css/dashboards.css', 'public/css/catalog.css',
].map(file => readFileSync(file, 'utf8')).join('\n');
const scripts = [
    'node_modules/chart.js/dist/chart.umd.js', 'node_modules/dayjs/dayjs.min.js',
    'resources/js/components/dropdown-position.js', 'resources/js/components/ui-dropdown.js',
    'resources/js/components/ui-asset-selector.js', 'resources/js/dashboards/data-table.js',
    'resources/js/dashboards/entity-attributes.js', 'resources/js/dashboards/mailchimp-dashboard.js',
].map(file => readFileSync(file, 'utf8').replace(/^import .*;\r?\n/gm, '').replace(/export function /g, 'function ')).join('\n');
const alpine = readFileSync('node_modules/alpinejs/dist/cdn.min.js', 'utf8');

for (const width of [1440, 390]) {
    for (const theme of ['light', 'dark']) {
        test(`commerce pages at ${width}px in ${theme} mode`, async ({ page }, testInfo) => {
            await page.setViewportSize({ width, height: 1000 });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            const requests = [];
            const row = { id: 'store:EUR', store_id: 'store', name: 'Example connected store',
                domain: 'store.example.test', currency: 'EUR', orders: 2, revenue: 22.5,
                catalog_url: '/profile' };
            await page.route('**/*', async route => {
                const url = new URL(route.request().url());
                if (route.request().method() === 'POST') {
                    const payload = route.request().postDataJSON();
                    requests.push({ path: url.pathname, payload });
                    return route.fulfill({ json: url.pathname.endsWith('/summary')
                        ? { summary: { orders: 2, revenue: 22.5 } }
                        : url.pathname.endsWith('/chart') ? { chart: [
                            { daily: '2026-11-01', orders: 1, revenue: 12.5 },
                            { daily: '2026-11-02', orders: 1, revenue: 10 },
                        ] }
                        : url.pathname.endsWith('/table') ? { table: [row] } : { status: 'success', data: {} } });
                }
                const view = url.pathname.slice(1);
                if (!['preview', 'stores', 'profile'].includes(view)) return route.fulfill({ status: 404, body: '' });
                const html = readFileSync(`storage/framework/testing/mailchimp-commerce/${view}.html`, 'utf8');
                return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="en" class="${theme === 'dark' ? 'dark' : ''}">
                    <head><meta charset="utf-8"><style>${css}</style><style>[x-cloak]{display:none!important}body{margin:0}main{padding:16px}</style>
                    <script>document.addEventListener('alpine:init', () => {
                        Alpine.magic('wire', () => ({}));
                        Alpine.store('subnav', {isOpen:false,toggle(){this.isOpen=!this.isOpen}});
                        Alpine.store('sidebar', {isOpen:false,groups:[],groupIsCollapsed:()=>false});
                    });</script></head>
                    <body><main>${html}</main><script>
                    window.Livewire = {find: () => ({entangle: key => ({selectedAccount:'42',dateStart:'2026-11-01',dateEnd:'2026-11-02',activeTab:'stores',campaignType:'all'})[key]})};
                    ${scripts}
                    </script><script>${alpine}</script></body></html>` });
            });
            await page.goto('http://commerce.test/preview');
            await expect(page.getByText('store.example.test', { exact: true })).toBeVisible();
            await expect(page.getByTitle('View in Catalog')).toHaveAttribute('href', '/profile');
            await expect(page.getByText('Store Revenue (source currency)', { exact: true })).toBeVisible();
            await expect(page.getByRole('columnheader', { name: 'Currency', exact: true })).toBeVisible();
            await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
            await expect.poll(() => page.evaluate(() => document.querySelector('canvas')?._chartInstance?.data.datasets.length)).toBe(2);
            const chartValues = await page.evaluate(() => {
                const chart = document.querySelector('canvas')._chartInstance;
                chart.stop();
                chart.update('none');
                return chart.data.datasets.map(dataset => ({ label: dataset.label, values: dataset.data }));
            });
            expect(chartValues).toEqual([
                { label: 'Store orders', values: [1, 1] }, { label: 'Store revenue', values: [12.5, 10] },
            ]);
            if (theme === 'dark') await expect(page.getByLabel('Store currency')).not.toHaveCSS('background-color', 'rgb(255, 255, 255)');
            await page.screenshot({ path: testInfo.outputPath('preview.png'), fullPage: true });
            await page.getByRole('combobox').filter({ has: page.getByRole('option', { name: 'All currencies' }) }).selectOption('EUR');
            await expect.poll(() => requests.some(request => request.payload.filters?.['dimensions.currency'] === 'EUR')).toBe(true);
            for (const view of ['stores', 'profile']) {
                await page.goto(`http://commerce.test/${view}`);
                await expect(page.getByText('Example connected store', { exact: true }).first()).toBeVisible();
                if (view === 'profile') await expect(page.getByText('Order 1001', { exact: true })).toBeVisible();
                await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
                await page.screenshot({ path: testInfo.outputPath(`${view}.png`), fullPage: true });
            }
            expect(errors).toEqual([]);
        });
    }
}
