import { test, expect } from '@playwright/test';

test('save the SumUp credentials: the API key is never shown again', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await page.getByRole('navigation', { name: 'Navigation principale' }).getByRole('link', { name: 'Paramètres' }).click();
    await expect(page.getByRole('heading', { level: 1, name: 'Paramètres' })).toBeVisible();

    await page.getByLabel('Code marchand').fill('mc42');
    await page.getByLabel('Clé API').fill('sup_sk_settings_4321');
    await page.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByTestId('toast')).toContainText('Paramètres SumUp enregistrés.');
    await expect(page.getByLabel('Code marchand')).toHaveValue('MC42');
    await expect(page.getByLabel('Clé API')).toHaveValue('');
    await expect(page.getByText('Clé enregistrée (••••4321)')).toBeVisible();

    await page.reload();
    await expect(page.getByText('sup_sk_settings_4321')).toHaveCount(0);

    await page.getByRole('button', { name: 'Supprimer la clé' }).click();
    await page.getByRole('button', { name: 'Confirmer ?' }).click();
    await expect(page.getByTestId('toast')).toContainText('Clé API SumUp supprimée.');
    await expect(page.getByText('Clé enregistrée')).toHaveCount(0);
});
