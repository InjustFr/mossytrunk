import { test, expect } from './support/test.js';
import { choose } from './support/select.js';

test('the app follows the browser language until a language is chosen, and the choice is kept', async ({ page }) => {
    await page.goto('/products');
    await expect(page.locator('html')).toHaveAttribute('lang', 'fr');
    await expect(page.getByRole('link', { name: 'Produits', exact: true })).toBeVisible();

    await choose(page, page.getByRole('combobox', { name: 'Langue' }), 'English');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.getByRole('link', { name: 'Products' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 1, name: 'Products' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'New product' })).toBeVisible();

    await page.reload();
    await expect(page.getByRole('link', { name: 'Products' })).toBeVisible();

    await choose(page, page.getByRole('combobox', { name: 'Language' }), 'Français');
    await expect(page.getByRole('link', { name: 'Produits', exact: true })).toBeVisible();
});

test.describe('signed out', () => {
    test.use({ storageState: { cookies: [], origins: [] }, locale: 'en-US' });

    test('an English browser gets the English sign-in page, and a chosen language is kept', async ({ page }) => {
        await page.goto('/login/check?state=forged&code=forged');
        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
        await expect(page.getByRole('link', { name: 'Sign in with mossyleaf' })).toBeVisible();

        await choose(page, page.getByRole('combobox', { name: 'Language' }), 'Français');
        await expect(page.locator('input[name="username"]')).toBeVisible();

        await page.goto('/login/check?state=forged&code=forged');
        await expect(page.locator('html')).toHaveAttribute('lang', 'fr');
        await expect(page.getByRole('link', { name: 'Se connecter avec mossyleaf' })).toBeVisible();
    });
});
