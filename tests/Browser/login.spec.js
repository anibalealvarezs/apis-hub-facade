import { test, expect } from '@playwright/test';

test.describe('Login flow with reCAPTCHA', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate relative to the baseURL
        await page.goto('/admin/login');
    });

    test('reCAPTCHA script is present in the admin login page', async ({ page }) => {
        // 1. Check if the script exists by its source
        const script = await page.locator('script[src*="google.com/recaptcha/enterprise.js"]');
        await expect(script).toBeAttached();

        // 2. Check if the script container with our custom logic is present
        const container = await page.locator('#recaptcha-script-container-admin');
        await expect(container).toBeAttached();
    });

    test('reCAPTCHA token hidden input is injected into the form', async ({ page }) => {
        // Wait for DOM and for our custom script to run
        await page.waitForLoadState('load');
        
        // Give it a bit of time for grecaptcha.enterprise.ready to execute
        await page.waitForTimeout(2000);

        // 3. Check if the input exists in the login form
        const tokenInput = await page.locator('input[name="recaptcha_token"]');
        
        // We expect it to be present (it might be empty initially until API responds)
        await expect(tokenInput).toBeAttached();
    });

    test('login with invalid credentials returns appropriate error', async ({ page }) => {
        // This test ensures that the full flow works: reCAPTCHA -> backend validation
        await page.fill('input[type="email"]', 'invalid@user.com');
        await page.fill('input[type="password"]', 'wrong-pass');
        
        // Use generic selector for submit button in Filament login
        await page.click('button[type="submit"]');

        // Check if there is an error message on screen
        // Filament common error selector
        const errorMsg = await page.locator('.fi-fo-field-wrp-error-message');
        await expect(errorMsg).toBeVisible();
    });
});
