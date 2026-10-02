import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';
import { createType } from './support/api.js';

test('create a gabarit, decline a design on it, tick its adaptations and validate it into a product', async ({ page, request }) => {
    const gabarit = unique('Carte');
    const design = unique('Clairière');
    const type = await createType(request, unique('Carte'));
    const response = await request.post('/api/gabarits', { data: { name: gabarit, typeId: type.id, sellingPrice: 250, adaptations: ['Marges 5 mm', 'Texte au dos'] } });
    expect(response.status()).toBe(201);

    await page.goto('/designs');
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

    await page.getByRole('button', { name: "Sortir de l'atelier" }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Créer les produits' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('1 produit créé');
    await expect(card.getByRole('link', { name: 'Voir le produit' })).toBeVisible();

    await page.goto('/products');
    await page.getByLabel('Rechercher un produit').fill(design);
    await expect(page.getByRole('row').filter({ hasText: design })).toContainText('2,50');
});

test('a gabarit needs a type', async ({ page }) => {
    await page.goto('/designs');
    await page.getByRole('button', { name: 'Gabarits' }).click();
    const dialog = page.getByRole('dialog', { name: 'Gabarits' });
    await dialog.getByLabel('Nom du gabarit').fill(unique('Carte'));
    await dialog.getByLabel('Prix de vente').fill('2');
    await dialog.getByRole('button', { name: 'Ajouter le gabarit' }).click();

    await expect(dialog.getByRole('group', { name: 'Type', exact: true }).getByRole('alert')).toBeVisible();
    await expect(page.getByTestId('toast')).toHaveCount(0);
});

test('find a design by searching, then open its collection alone', async ({ page, request }) => {
    const collection = unique('Sous-bois');
    const design = unique('Mycelium');
    const { id } = await (await request.post('/api/design-collections', { data: { name: collection, description: '' } })).json();
    await request.post('/api/designs', { data: { name: design, collectionId: id, notes: '' } });

    await page.goto('/designs');
    const index = page.getByRole('navigation', { name: 'Collections' });
    await index.getByRole('searchbox').fill(design);
    const shelf = page.getByRole('region', { name: 'Toutes les créations' });
    await expect(shelf.getByRole('link', { name: design })).toBeVisible();

    await shelf.getByRole('button', { name: collection }).click();
    await expect(page.getByRole('heading', { level: 2, name: collection })).toBeVisible();
    await expect(page).toHaveURL(new RegExp(`collection=${id}`));

    await page.getByRole('group', { name: 'Statut des designs' }).getByRole('button', { name: /Sortis de l'atelier/ }).click();
    await expect(page.getByText('Aucun design ne correspond à la recherche.')).toBeVisible();
});
