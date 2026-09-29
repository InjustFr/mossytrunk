import { test, expect } from '@playwright/test';
import { forgetService } from './support/api.js';

test('add SumUp from the settings: its API key is never shown again', async ({ page, request }) => {
    await forgetService(request, 'sumup');
    await page.goto('/tableau-de-bord');
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

test('delete every order, then every product, after a warning', async ({ page, request }) => {
    const product = await (await request.post('/api/products', { data: { name: 'Badge à effacer', sellingPrice: 300 } })).json();
    const event = await request.post('/api/events', { data: { name: 'Salon à effacer', location: 'Nantes', startDate: '2037-05-10', endDate: '2037-05-10' } });
    expect(event.status()).toBe(201);
    const order = await request.post('/api/orders', { data: { placedAt: '2037-05-10T11:00', lines: [{ productId: product.id, variant: null, quantity: 1 }] } });
    expect(order.status()).toBe(201);

    await page.goto('/parametres');
    await expect(page.getByText('Ces suppressions sont définitives')).toBeVisible();

    await page.getByRole('button', { name: 'Supprimer toutes les commandes' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Annuler' }).click();
    await page.goto('/commandes');
    await expect(page.getByRole('link', { name: 'Salon à effacer' })).toBeVisible();

    await page.goto('/parametres');
    await page.getByRole('button', { name: 'Supprimer toutes les commandes' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Tout supprimer' }).click();
    await expect(page.getByTestId('toast').filter({ hasText: 'Commandes supprimées' })).toBeVisible();

    await page.getByRole('button', { name: 'Supprimer tous les produits' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Tout supprimer' }).click();
    await expect(page.getByTestId('toast').filter({ hasText: 'Produits supprimés' })).toBeVisible();

    await page.goto('/commandes');
    await expect(page.getByRole('link', { name: 'Salon à effacer' })).toHaveCount(0);
    await page.goto('/produits');
    await expect(page.getByText('Badge à effacer')).toHaveCount(0);
});
