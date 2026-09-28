import { test, expect } from '@playwright/test';

test('home redirects to the dashboard with navigation', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/tableau-de-bord$/);
    await expect(page.getByRole('heading', { level: 1, name: 'Tableau de bord' })).toBeVisible();
    await expect(page.getByRole('navigation', { name: 'Navigation principale' })).toBeVisible();
});
