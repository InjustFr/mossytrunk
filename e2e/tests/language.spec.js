import { test, expect } from '@playwright/test';
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

    test('an English browser gets the English app', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
        await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible();

        await choose(page, page.getByRole('combobox', { name: 'Language' }), 'Français');
        await expect(page.getByRole('button', { name: 'Se connecter' })).toBeVisible();
    });
});
