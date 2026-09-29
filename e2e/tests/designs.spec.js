import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';

test('create a gabarit, decline a design on it, tick its adaptations and validate it into a product', async ({ page, request }) => {
    const gabarit = unique('Carte');
    const design = unique('Clairière');
    const response = await request.post('/api/gabarits', { data: { name: gabarit, sellingPrice: 250, buyingPrice: 40, adaptations: ['Marges 5 mm', 'Texte au dos'] } });
    expect(response.status()).toBe(201);

    await page.goto('/creations');
    await page.getByRole('button', { name: 'Nouveau design' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau design' }).locator('form');
    await form.getByLabel('Nom du design').fill(design);
    await form.getByRole('button', { name: gabarit }).click();
    await form.getByRole('button', { name: 'Commencer le design' }).click();

    await expect(page.getByRole('heading', { level: 1, name: design })).toBeVisible();
    const card = page.getByRole('article', { name: gabarit });
    await expect(card).toContainText('2 à adapter');
    await card.getByRole('checkbox').first().click();
    await expect(card).toContainText('1 à adapter');
    await card.getByRole('checkbox').nth(1).click();
    await expect(card).toContainText('Prête');

    await page.getByRole('button', { name: 'Valider le design' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Créer les produits' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('1 produit créé');
    await expect(card).toContainText('Produit créé');

    await page.goto('/produits');
    await page.getByLabel('Rechercher un produit').fill(design);
    await expect(page.getByRole('row').filter({ hasText: design })).toContainText('2,50');
});
