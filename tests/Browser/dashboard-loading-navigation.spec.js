import { test, expect } from '@playwright/test';
import path from 'node:path';

const rendererPath = path.resolve('public/js/dashboard-renderer.js');

async function openLoadingDashboard(page) {
    const state = { aborted: 0 };
    await page.exposeFunction('recordWidgetAbort', () => { state.aborted++; });
    await page.route('http://dashboard.test/**', route => route.fulfill({
        contentType: 'text/html',
        body: '<a id="leave" href="/next">Leave</a><a id="anchor" href="#widgets">Widgets</a><div id="widgets"></div>',
    }));
    await page.goto('http://dashboard.test/current');
    await page.addScriptTag({ path: rendererPath });
    await page.evaluate(() => {
        sessionStorage.clear();
        window.fetch = (url, { signal }) => new Promise((resolve, reject) => {
            sessionStorage.setItem('started', Number(sessionStorage.getItem('started') || 0) + 1);
            signal.addEventListener('abort', () => {
                window.recordWidgetAbort();
                sessionStorage.setItem('aborted', Number(sessionStorage.getItem('aborted') || 0) + 1);
                reject(signal.reason);
            }, { once: true });
        });
        // Four active requests and two queued widgets.
        for (let id = 1; id <= 6; id++) {
            const container = document.createElement('div');
            document.querySelector('#widgets').append(container);
            window.dashboardRenderer.renderWidget(id, container, {}, 'test-project');
        }
    });
    await expect.poll(() => page.evaluate(() => sessionStorage.getItem('started'))).toBe('4');
    return state;
}

test('ordinary navigation cancels widget requests while the destination is loading', async ({ page }) => {
    const state = await openLoadingDashboard(page);
    // Keep the response pending so pagehide cannot perform the cancellation.
    await page.route('http://dashboard.test/next', async route => {
        await expect.poll(() => state.aborted).toBe(4);
        await route.fulfill({ contentType: 'text/html', body: 'Destination' });
    });
    await page.click('#leave');
    await expect(page).toHaveURL('http://dashboard.test/next');
    expect(state.aborted).toBe(4);
    expect(await page.evaluate(() => sessionStorage.getItem('started'))).toBe('4');
});

test('programmatic departure cancels widgets while the destination is loading', async ({ page }) => {
    const state = await openLoadingDashboard(page);
    await page.route('http://dashboard.test/next?lang=es', async route => {
        await expect.poll(() => state.aborted).toBe(4);
        await route.fulfill({ contentType: 'text/html', body: 'Destination' });
    });
    await page.evaluate(() => {
        window.location.assign('/next?lang=es');
    });
    await expect(page).toHaveURL('http://dashboard.test/next?lang=es');
    expect(state.aborted).toBe(4);
});

test('Builder-style unsaved navigation guard preserves in-flight widget requests', async ({ page }) => {
    await openLoadingDashboard(page);
    await page.evaluate(() => {
        document.addEventListener('click', event => {
            event.preventDefault();
            window.unsavedPromptOpen = true;
        });
    });
    await page.click('#leave');
    expect(await page.evaluate(() => window.unsavedPromptOpen)).toBe(true);
    expect(await page.evaluate(() => sessionStorage.getItem('aborted'))).toBeNull();
    expect(await page.evaluate(() => window.dashboardRenderer._queue.length)).toBe(2);
    await expect(page).toHaveURL('http://dashboard.test/current');
});

test('same-page links and modified clicks preserve widget requests', async ({ page }) => {
    await openLoadingDashboard(page);
    await page.click('#anchor');
    await page.evaluate(() => {
        document.querySelector('#leave').dispatchEvent(new MouseEvent('click', {
            bubbles: true, cancelable: true, ctrlKey: true, button: 0,
        }));
    });
    expect(await page.evaluate(() => sessionStorage.getItem('aborted'))).toBeNull();
});

test('Livewire navigation cancels active and queued work unless prevented', async ({ page }) => {
    await openLoadingDashboard(page);
    await page.evaluate(() => {
        const blocked = new Event('livewire:navigate', { cancelable: true });
        blocked.preventDefault();
        document.dispatchEvent(blocked);
    });
    expect(await page.evaluate(() => sessionStorage.getItem('aborted'))).toBeNull();
    await page.evaluate(() => document.dispatchEvent(new Event('livewire:navigate', { cancelable: true })));
    await expect.poll(() => page.evaluate(() => sessionStorage.getItem('aborted'))).toBe('4');
    expect(await page.evaluate(() => window.dashboardRenderer._queue.length)).toBe(0);
});
