import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';
import { createProduct, createType, restock } from './support/api.js';

test('a type owns its variants: rename one everywhere, then filter products by variant with the stock of those variants', async ({ page, request }) => {
    const print = unique('Print');

    await page.goto('/products');
    await page.getByRole('button', { name: 'Types de produit' }).click();
    const types = page.getByRole('dialog', { name: 'Types de produit' });
    await types.getByRole('button', { name: 'Ajouter un type' }).click();
    const creation = page.getByRole('dialog', { name: 'Nouveau type de produit' });
    await creation.getByRole('textbox', { name: /^Nom/ }).fill(print);
    for (const variant of ['A5', 'A4', 'A3']) {
        await creation.getByLabel('Nouvelle variante').fill(variant);
        await creation.getByLabel('Nouvelle variante').press('Enter');
    }
    await creation.getByRole('button', { name: 'Code et affichage' }).click();
    await expect(creation.getByRole('switch', { name: 'Préfixer le nom des produits' })).toBeChecked();
    await expect(creation.getByText(`Ses produits s'affichent « ${print} Forêt ».`)).toBeVisible();
    await creation.getByRole('button', { name: 'Créer le type' }).click();
    await expect(page.getByTestId('toast')).toContainText(`Type « ${print} » créé.`);
    await expect(types.getByRole('listitem').filter({ hasText: print })).toContainText('A5 · A4 · A3');

    await types.getByRole('button', { name: `Modifier le type ${print}` }).click();
    const edit = page.getByRole('dialog', { name: `Modifier le type ${print}` });
    await edit.getByRole('button', { name: 'Renommer A4', exact: true }).click();
    await edit.getByRole('textbox', { name: 'Nouveau nom de A4' }).fill('A4+');
    await edit.getByRole('textbox', { name: 'Nouveau nom de A4' }).press('Tab');
    await expect(page.getByTestId('toast').last()).toContainText('Variante « A4 » renommée « A4+ »');
    await expect(edit.getByRole('button', { name: 'Renommer A4+' })).toBeVisible();
    await expect(edit.getByRole('button', { name: 'Renommer A4', exact: true })).toHaveCount(0);
    await edit.getByRole('button', { name: 'Tous les types' }).click();
    await expect(types.getByRole('listitem').filter({ hasText: print })).toContainText('A5 · A4+ · A3');
    await page.keyboard.press('Escape');

    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await choose(page, form.getByRole('combobox', { name: 'Type' }), print);
    await form.getByLabel('Nom').fill('Forêt');
    await form.getByLabel('Prix de vente').fill('15');
    await form.getByRole('button', { name: 'A5', exact: true }).click();
    await form.getByRole('button', { name: 'A3', exact: true }).click();
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();
    await expect(page.getByTestId('toast').last()).toContainText(`Produit « ${print} Forêt » ajouté.`);

    const typeList = await (await request.get('/api/product-types')).json();
    const type = typeList.find((candidate) => candidate.name === print);
    expect(type.variants).toEqual(['A5', 'A4+', 'A3']);
    const foret = (await (await request.get('/api/products')).json()).find((product) => product.displayName === `${print} Forêt`);
    expect(foret.variants).toEqual(['A5', 'A3']);
    const riviere = await createProduct(request, { name: 'Rivière', sellingPrice: 1_500, variants: ['A4+', 'A3'], type });
    for (const [product, variant, quantity] of [[foret, 'A5', 3], [foret, 'A3', 5], [riviere, 'A4+', 7], [riviere, 'A3', 2]]) {
        await restock(request, product, { variant, quantity, totalPaid: quantity * 400 });
    }

    await page.reload();
    await page.getByRole('group', { name: 'Filtrer par type' }).getByRole('button', { name: print }).click();
    const rows = page.getByRole('row').filter({ hasText: print });
    await expect(rows).toHaveCount(2);
    const stockOf = (name) => page.getByRole('button', { name: `Historique de la réserve de ${print} ${name}` });
    await expect(stockOf('Forêt')).toHaveText('8');
    await expect(stockOf('Rivière')).toHaveText('9');

    const byVariant = page.getByRole('group', { name: 'Filtrer par variante' });
    await expect(byVariant.getByRole('button')).toHaveText(['A5', 'A4+', 'A3']);
    await byVariant.getByRole('button', { name: 'A4+' }).click();
    await expect(rows).toHaveCount(1);
    await expect(stockOf('Rivière')).toHaveText('7');

    await byVariant.getByRole('button', { name: 'A3' }).click();
    await expect(rows).toHaveCount(2);
    await expect(stockOf('Forêt')).toHaveText('5');
    await expect(stockOf('Rivière')).toHaveText('9');

    await byVariant.getByRole('button', { name: 'A4+' }).click();
    await expect(stockOf('Forêt')).toHaveText('5');
    await expect(stockOf('Rivière')).toHaveText('2');

    await page.getByRole('group', { name: 'Filtrer par type' }).getByRole('button', { name: 'Tous', exact: true }).click();
    await expect(byVariant).toHaveCount(0);
});

test('a variant still used by a product cannot be dropped from its type', async ({ page, request }) => {
    const print = unique('Print');
    const type = await createType(request, print, { variants: ['A4', 'A3'] });
    await createProduct(request, { name: 'Forêt', variants: ['A3'], type });

    await page.goto('/products');
    await page.getByRole('button', { name: 'Types de produit' }).click();
    await page.getByRole('dialog', { name: 'Types de produit' }).getByRole('button', { name: `Modifier le type ${print}` }).click();
    const edit = page.getByRole('dialog', { name: `Modifier le type ${print}` });
    await edit.getByRole('button', { name: 'Retirer A3' }).click();
    await edit.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(edit.getByRole('alert')).toBeVisible();
    const saved = (await (await request.get('/api/product-types')).json()).find((candidate) => candidate.name === print);
    expect(saved.variants).toEqual(['A4', 'A3']);
});
