import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });

for (const template of ['public-view/show', 'shared/public-dashboard']) {
    test(`${template} honors the selected theme even when the system prefers dark`, async ({ page }) => {
        const blade = readFileSync(`resources/views/${template}.blade.php`, 'utf8');
        const runtime = blade.indexOf('<script src="https://cdn.tailwindcss.com"></script>');
        const config = blade.indexOf("asset('js/demo-tailwind-config.js')");
        expect(runtime).toBeGreaterThan(-1);
        expect(config).toBeGreaterThan(runtime);
        await page.emulateMedia({ colorScheme: 'dark' });
        const configuration = readFileSync('public/js/demo-tailwind-config.js', 'utf8');
        await page.addInitScript(() => localStorage.setItem('pv_theme', 'light'));
        await page.route('https://theme.test/', route => route.fulfill({ contentType: 'text/html', body: `<html><head>
            <script>window.demoThemeStorageKey = 'pv_theme';</script>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>${configuration}</script></head>
            <body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">
            <div id="card" class="bg-white dark:bg-gray-800">Widget</div>
            <input type="date" class="bg-white dark:bg-gray-800"></body></html>` }));
        await page.goto('https://theme.test/');
        await expect(page.locator('#card')).toHaveCSS('background-color', 'rgb(255, 255, 255)');
        await page.evaluate(() => document.documentElement.classList.add('dark'));
        await expect(page.locator('#card')).toHaveCSS('background-color', 'rgb(31, 41, 55)');
        await page.evaluate(() => document.documentElement.classList.remove('dark'));
        await expect(page.locator('#card')).toHaveCSS('background-color', 'rgb(255, 255, 255)');
    });
}
