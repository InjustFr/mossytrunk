import { test, expect } from '@playwright/test';

test('home redirects to the orders page with navigation', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/commandes$/);
    await expect(page.getByRole('heading', { level: 1, name: 'Commandes' })).toBeVisible();
    await expect(page.getByRole('navigation', { name: 'Navigation principale' })).toBeVisible();
});
