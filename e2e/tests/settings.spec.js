import { test, expect } from '@playwright/test';
import { forgetService } from './support/api.js';

test('add SumUp from the settings: its API key is never shown again', async ({ page, request }) => {
    await forgetService(request, 'sumup');
    await page.goto('/dashboard');
    await page.getByRole('navigation', { name: 'Navigation principale' }).getByRole('link', { name: 'Paramètres' }).click();
    await expect(page.getByRole('heading', { level: 1, name: 'Paramètres' })).toBeVisible();

    await page.getByRole('button', { name: 'Ajouter un service' }).click();
    const dialog = page.getByRole('dialog', { name: 'Ajouter un service' });
    await dialog.getByRole('button', { name: /^SumUp/ }).click();
    await expect(page.getByRole('dialog', { name: 'Ajouter SumUp' })).toBeVisible();
    await page.getByRole('button', { name: 'Ajouter SumUp' }).click();
    await expect(page.getByText('Ce champ est obligatoire.').first()).toBeVisible();

    await page.getByLabel('Code marchand').fill('mc42');
    await page.getByLabel('Clé API').fill('sup_sk_settings_4321');
    await page.getByRole('radio', { name: /En ligne/ }).click();
    await page.getByRole('button', { name: 'Ajouter SumUp' }).click();

    await expect(page.getByTestId('toast')).toContainText('SumUp ajouté.');
    const services = page.getByRole('region', { name: 'Services connectés' });
    await expect(services).toContainText('Prêt');
    await expect(services).toContainText('En ligne');

    await page.reload();
    await expect(page.getByText('sup_sk_settings_4321')).toHaveCount(0);
    await page.getByRole('button', { name: 'Modifier SumUp' }).click();
    await expect(page.getByLabel('Code marchand')).toHaveValue('MC42');
    await expect(page.getByLabel('Clé API')).toHaveValue('');
    await expect(page.getByText('Clé enregistrée (••••4321)')).toBeVisible();
    await page.getByRole('radio', { name: /Au marché du jour/ }).click();
    await page.getByRole('button', { name: 'Enregistrer', exact: true }).click();
    await expect(page.getByTestId('toast').last()).toContainText('SumUp modifié.');
    await expect(services).toContainText('Au marché du jour');

    await page.getByRole('button', { name: 'Retirer SumUp' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Retirer' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('SumUp retiré.');
    await expect(services.getByRole('button', { name: 'Modifier SumUp' })).toHaveCount(0);
});
