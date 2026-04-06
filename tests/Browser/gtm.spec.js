import { test, expect } from '@playwright/test';

test.describe('Google Tag Manager Implementation', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
    });

    test('GTM should not be present when using placeholder ID', async ({ page }) => {
        // Since we are in dev with GTM-XXXXXXX, it should BE ABSENT
        const gtmScript = await page.locator('head script').filter({ 
            hasText: /googletagmanager\.com\/gtm\.js/ 
        });
        await expect(gtmScript).not.toBeAttached();
        
        const gtmNoscript = await page.locator('body noscript iframe[src*="googletagmanager.com/ns.html"]');
        await expect(gtmNoscript).not.toBeAttached();
    });

    // Note: To test positive injection, we would need to set a real GTM_ID in .env
    // or mock the controller response.
});
