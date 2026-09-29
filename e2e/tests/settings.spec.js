import { test, expect } from '@playwright/test';

test('save the SumUp credentials: the API key is never shown again', async ({ page }) => {
    await page.goto('/tableau-de-bord');
    await page.getByRole('navigation', { name: 'Navigation principale' }).getByRole('link', { name: 'Paramètres' }).click();
    await expect(page.getByRole('heading', { level: 1, name: 'Paramètres' })).toBeVisible();

    await page.getByLabel('Code marchand').fill('mc42');
    await page.getByLabel('Clé API').fill('sup_sk_settings_4321');
    await page.getByRole('button', { name: 'Enregistrer', exact: true }).click();

    await expect(page.getByTestId('toast')).toContainText('Paramètres SumUp enregistrés.');
    await expect(page.getByLabel('Code marchand')).toHaveValue('MC42');
    await expect(page.getByLabel('Clé API')).toHaveValue('');
    await expect(page.getByText('Clé enregistrée (••••4321)')).toBeVisible();

    await page.reload();
    await expect(page.getByText('sup_sk_settings_4321')).toHaveCount(0);

    await page.getByRole('button', { name: 'Supprimer la clé' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Confirmer ?' }).click();
    await expect(page.getByTestId('toast')).toContainText('Clé API SumUp supprimée.');
    await expect(page.getByText('Clé enregistrée')).toHaveCount(0);
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
