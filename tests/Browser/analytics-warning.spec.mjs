import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

test.use({ channel: process.env.PLAYWRIGHT_CHANNEL || undefined });

const component = execFileSync('php', ['-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo Illuminate\\Support\\Facades\\Blade::render("<x-analytics-warning />");'], { encoding: 'utf8' });
const tooltip = readFileSync('resources/js/components/floating-tooltip.js', 'utf8').replace('export function', 'function');
const alpine = readFileSync('node_modules/alpinejs/dist/cdn.min.js', 'utf8');

test('header warning toggles without changing canvas size and clears on metric change', async ({ page }) => {
    await page.setViewportSize({ width: 420, height: 600 });
    await page.setContent(`<style>[x-cloak]{display:none!important} header{display:flex;justify-content:flex-end} [role=note]{width:320px;max-width:calc(100vw - 16px);box-sizing:border-box;background:white} .grid-stack-item-content{height:240px;overflow:hidden} #canvas{height:180px}</style>
        <section class="grid-stack-item-content"><header>${component}<button>Refresh</button></header><div id="canvas">Chart</div></section><button id="outside">Outside</button>`);
    await page.addScriptTag({ content: tooltip });
    await page.addScriptTag({ content: alpine });
    const trigger = page.getByRole('button', { name: 'GA4 user count information' });
    await expect(trigger).toBeHidden();
    await page.addScriptTag({ content: readFileSync('public/js/dashboard-renderer.js', 'utf8') });
    await page.evaluate(() => { window.dashboardRenderer._renderWidget = (_type, el) => { el.textContent = 'Chart'; }; });
    const update = async messages => page.evaluate(messages => window.dashboardRenderer.render(document.querySelector('#canvas'), { widget_type: 'tile', data: {}, controls: {}, analytics_warnings: messages }), messages);
    const message = '<img src=x onerror=alert(1)> Users are reported sums.';
    await update([message]);
    await expect(trigger).toBeVisible();
    const height = await page.locator('#canvas').evaluate(el => el.getBoundingClientRect().height);
    await trigger.click();
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByRole('note')).toBeVisible();
    await expect(page.getByRole('note')).toHaveText(message);
    await expect(page.getByRole('note').locator('img')).toHaveCount(0);
    expect(await page.locator('#canvas').evaluate(el => el.getBoundingClientRect().height)).toBe(height);
    expect(await page.getByRole('note').evaluate(el => el.parentElement.tagName)).toBe('BODY');
    await page.keyboard.press('Escape');
    await expect(page.getByRole('note')).toBeHidden();
    await trigger.click();
    await trigger.click();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await trigger.click();
    await page.locator('#outside').click();
    await expect(page.getByRole('note')).toBeHidden();
    await page.setViewportSize({ width: 280, height: 600 });
    await trigger.click();
    const bounds = await page.getByRole('note').boundingBox();
    expect(bounds.x).toBeGreaterThanOrEqual(8);
    expect(bounds.x + bounds.width).toBeLessThanOrEqual(272);
    await update([]);
    await expect(trigger).toBeHidden();
});
