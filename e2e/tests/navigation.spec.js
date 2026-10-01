import { test, expect } from '@playwright/test';

test('navigating between pages does not reload the document (Turbo Drive)', async ({ page }) => {
    await page.goto('/orders');
    await page.evaluate(() => { window.__sameDocument = true; });

    const nav = page.getByRole('navigation', { name: 'Navigation principale' });
    await nav.getByRole('link', { name: 'Produits' }).click();
    await expect(page).toHaveURL(/\/products$/);
    await expect(page.locator('.progress-bar--visible')).toHaveCount(0);
    await expect(page.getByRole('heading', { level: 1, name: 'Produits' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Produits' })).toHaveAttribute('aria-current', 'page');

    await page.goBack();
    await expect(page.getByRole('heading', { level: 1, name: 'Commandes' })).toBeVisible();
    expect(await page.evaluate(() => window.__sameDocument)).toBe(true);
});
