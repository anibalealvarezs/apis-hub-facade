import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });

execFileSync('php', ['vendor/bin/pest', 'tests/Feature/Catalog/CatalogEntityPageTest.php',
    '--filter=account_layout|catalog_navigation|catalog_empty|marketing_overview', '--compact'], {
    env: { ...process.env, CATALOG_BROWSER_FIXTURES: '1' }, stdio: 'pipe',
});
// Deliberately use Filament's shipped CSS, without the application's generated
// Tailwind utilities: the Catalog stylesheet must work in the real panel shell.
const styles = ['public/css/filament/filament/app.css', 'public/css/filament/support/support.css',
    'public/css/branding.css', 'public/css/catalog.css'].map(file => readFileSync(file, 'utf8')).join('\n');
const alpine = readFileSync('node_modules/alpinejs/dist/cdn.min.js', 'utf8');
const fixture = name => readFileSync(`storage/framework/testing/catalog-layout/${name}.html`, 'utf8');

for (const width of [1440, 390]) {
    for (const theme of ['light', 'dark']) {
        test(`Catalog at ${width}px in ${theme} mode`, async ({ page }, testInfo) => {
            await page.setViewportSize({ width, height: 1000 });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            // A previous collapsed Catalog preference must no longer hide Overview.
            await page.addInitScript(() => localStorage.setItem('collapsedGroups', JSON.stringify(['Catalog'])));
            await page.route('**/*', route => {
                const name = new URL(route.request().url()).pathname.slice(1);
                if (!['accounts', 'account', 'overview', 'empty'].includes(name)) return route.fulfill({ status: 404, body: '' });
                return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="en" class="${theme === 'dark' ? 'dark' : ''}">
                    <head><meta charset="utf-8"><style>${styles}</style><style>
                        body{margin:0;background:${theme === 'dark' ? '#0b1220' : '#f4f7fb'};font-family:ui-sans-serif,system-ui,sans-serif}
                        .fixture-shell{display:grid;grid-template-columns:230px minmax(0,1fr);min-height:100vh}
                        aside{padding:28px 16px;background:${theme === 'dark' ? '#0f172a' : '#fff'};border-right:1px solid ${theme === 'dark' ? '#263345' : '#e4e9f0'}}
                        main{padding:24px 32px;min-width:0} .fi-header-heading{color:${theme === 'dark' ? '#e9eff7' : '#172033'}}
                        [x-cloak]{display:none!important}@media(max-width:700px){.fixture-shell{grid-template-columns:minmax(0,1fr)}aside{display:none}main{padding:12px}}
                    </style><script>document.addEventListener('alpine:init',()=>{
                        Alpine.store('sidebar',{isOpen:true,collapsedGroups:JSON.parse(localStorage.getItem('collapsedGroups')||'[]'),
                            groupIsCollapsed(label){return this.collapsedGroups.includes(label)},
                            toggleCollapsedGroup(label){this.collapsedGroups=this.groupIsCollapsed(label)?this.collapsedGroups.filter(group=>group!==label):[...this.collapsedGroups,label];localStorage.setItem('collapsedGroups',JSON.stringify(this.collapsedGroups))}});
                        Alpine.magic('wire',()=>({}));
                    });</script></head><body><div class="fixture-shell"><aside>${fixture('navigation')}</aside><main>${fixture(name)}</main></div>
                    <script>${alpine}</script></body></html>` });
            });

            await page.goto('http://catalog.test/accounts');
            await page.evaluate(() => document.querySelectorAll('[wire\\:loading\\.delay]').forEach(element => { element.style.display = 'none'; }));
            await expect(page.locator('.catalog-record-row')).toHaveCount(10);
            await expect(page.getByLabel('Rows per page')).toBeVisible();
            await expect(page.locator('th[aria-sort="ascending"]')).toHaveText(/Name/);
            await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
            if (width > 700) {
                await expect(page.getByRole('link', { name: 'Marketing Overview' })).toBeVisible();
                await expect(page.getByRole('link', { name: 'Campaigns', exact: true })).toBeHidden();
                await page.getByRole('button', { name: 'Entities', exact: true }).click();
                await expect(page.getByRole('link', { name: 'Campaigns', exact: true })).toBeVisible();
                const positions = await page.locator('aside .fi-sidebar-item-button').evaluateAll(links =>
                    Object.fromEntries(links.map(link => [link.textContent.trim(), link.querySelector('.fi-sidebar-item-label').getBoundingClientRect().x])));
                expect(positions['Ad sets / groups']).toBeGreaterThan(positions.Campaigns);
                expect(positions.Ads).toBeGreaterThan(positions['Ad sets / groups']);
            }
            const fields = await page.locator('.catalog-filter-grid > div').evaluateAll(elements => elements.map(element => element.getBoundingClientRect().y));
            if (width > 1100) expect(new Set(fields)).toHaveProperty('size', 1);
            else expect(new Set(fields).size).toBeGreaterThan(1);
            await page.screenshot({ path: testInfo.outputPath('accounts.png'), fullPage: true });

            for (const name of ['account', 'overview', 'empty']) {
                await page.goto(`http://catalog.test/${name}`);
                await page.evaluate(() => {
                    document.querySelectorAll('input[type="date"]').forEach((input, index) => { input.value = index ? '2026-11-06' : '2026-10-08'; });
                    document.querySelectorAll('[wire\\:loading\\.delay]').forEach(element => { element.style.display = 'none'; });
                });
                if (name === 'account') {
                    await expect(page.locator('.catalog-attributes .catalog-attribute')).toHaveCount(3);
                    await expect(page.locator('.catalog-activity-item')).toHaveCount(2);
                }
                if (name === 'overview') await expect(page.locator('.catalog-domain-card')).toHaveCount(5);
                if (name === 'empty') {
                    await expect(page.getByText('No Permitted Assets in Scope')).toBeVisible();
                    const notice = page.locator('.catalog-notice');
                    const colors = await notice.evaluate(element => {
                        const style = getComputedStyle(element);
                        return [style.color, style.backgroundColor].map(color => color.match(/[\d.]+/g).slice(0, 3).map(Number));
                    });
                    const luminance = color => color.map(value => value / 255).map(value => value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4)
                        .reduce((sum, value, index) => sum + value * [.2126, .7152, .0722][index], 0);
                    const lightness = colors.map(luminance).sort((a, b) => a - b);
                    expect((lightness[1] + .05) / (lightness[0] + .05)).toBeGreaterThanOrEqual(4.5);
                    if (theme === 'dark') {
                        await expect(notice).not.toHaveCSS('background-color', 'rgb(255, 255, 255)');
                        await expect(notice).not.toHaveCSS('color', 'rgb(255, 255, 255)');
                    }
                }
                await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
                await page.screenshot({ path: testInfo.outputPath(`${name}.png`), fullPage: true });
            }
            expect(errors).toEqual([]);
        });
    }
}
