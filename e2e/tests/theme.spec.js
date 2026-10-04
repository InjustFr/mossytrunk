import { test, expect } from './support/test.js';

test('the user picks a theme or their own colours, and keeps them', async ({ page }) => {
    await page.goto('/settings');
    const html = page.locator('html');

    await page.getByRole('radio', { name: 'Nuit' }).click();
    await expect(page.getByTestId('toast').getByText('Apparence enregistrée.')).toBeVisible();
    await page.reload();
    await expect(html).toHaveAttribute('style', /--theme-background: #1d201b; --theme-accent: #93bb6c; color-scheme: dark/);
    await expect(page.getByRole('radio', { name: 'Nuit' })).toHaveAttribute('aria-checked', 'true');

    await page.getByRole('button', { name: 'Accent' }).click();
    const hex = page.getByLabel('Code hexadécimal');
    await hex.fill('#b0532c');
    await hex.press('Enter');
    await page.keyboard.press('Escape');
    await expect(page.getByRole('radio', { name: 'Nuit' })).toHaveAttribute('aria-checked', 'false');
    await page.reload();
    await expect(html).toHaveAttribute('style', /--theme-background: #1d201b; --theme-accent: #b0532c/);

    await page.getByRole('radio', { name: 'Mousse' }).click();
    await expect(page.getByTestId('toast').getByText('Apparence enregistrée.')).toBeVisible();
    await page.reload();
    await expect(html).toHaveAttribute('style', /--theme-background: #f5f5f3; --theme-accent: #5b7f3a; color-scheme: light/);
});
