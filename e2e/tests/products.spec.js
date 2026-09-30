import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';
import { createProduct, createType, defineVariants } from './support/api.js';

const uniqueCode = (prefix) => `${prefix}${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`.toUpperCase();

test('create a product with variants and a suggested reference, then change its selling price and reference; its buying price comes from stock', async ({ page, request }) => {
    const type = await createType(request, unique('Textile'), { variants: ['Mousse', 'Fougère'] });
    const name = unique('T-shirt');
    const displayName = `${type.name} ${name}`;
    const reference = uniqueCode('TSH-');

    await page.goto('/products');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await choose(page, form.getByRole('combobox', { name: 'Type' }), type.name);
    await form.getByLabel('Nom').fill(name);
    await form.getByRole('button', { name: 'Référence et stock' }).click();
    await expect(form.getByLabel('Référence')).toHaveValue(new RegExp(`^${type.code}-TSH`));
    await form.getByLabel('Prix de vente').fill('20');
    await form.getByRole('button', { name: 'Mousse', exact: true }).click();
    await form.getByRole('button', { name: 'Fougère', exact: true }).click();
    await expect(form.getByRole('button', { name: 'Fougère', exact: true })).toHaveAttribute('data-state', 'on');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Produit « ${displayName} » ajouté.`);
    // The list is paginated: search to keep the row on screen whatever the number of products.
    await page.getByLabel('Rechercher un produit').fill(name);
    const row = page.getByRole('row').filter({ hasText: name });
    await expect(row).toContainText('Mousse, Fougère');
    await expect(row).toContainText('0,00');

    await expect(page.getByRole('dialog')).toHaveCount(0);

    await row.getByRole('button', { name: `Modifier ${displayName}` }).click();
    const edit = page.getByRole('dialog', { name: 'Modifier le produit' });
    await expect(edit.getByLabel('Nom')).toHaveValue(name);
    await edit.getByRole('button', { name: 'Référence et stock' }).click();
    await expect(edit.getByLabel('Référence')).toHaveValue(new RegExp(`^${type.code}-TSH`));
    await expect(edit.getByText('Pas encore acheté')).toBeVisible();
    await edit.getByLabel('Référence').fill(reference);
    await edit.getByLabel('Prix de vente').fill('25');
    await edit.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByTestId('toast').last()).toContainText('mis à jour');
    await expect(row).toContainText('25,00');
    await expect(row).toContainText(reference);
});

test('shows validation errors inline', async ({ page }) => {
    await page.goto('/products');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog').locator('form');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(form.getByText('Le nom est obligatoire.')).toBeVisible();
    await expect(form.getByRole('group', { name: 'Type', exact: true }).getByRole('alert')).toBeVisible();
});

test('create a type inline with its colour and display products as "Type Nom"', async ({ page, request }) => {
    const typeName = unique('Print');

    await page.goto('/products');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await choose(page, form.getByRole('combobox', { name: 'Type' }), '＋ Créer un type…');
    await form.getByLabel('Nom du nouveau type').fill(typeName);
    await form.getByRole('option', { name: 'Framboise' }).click();
    await form.getByRole('button', { name: 'Créer', exact: true }).click();
    await expect(form.getByRole('combobox', { name: 'Type' })).toHaveText(typeName);
    const types = await (await request.get('/api/product-types')).json();
    expect(types.find((type) => type.name === typeName).color).toBe('#a3485a');

    await form.getByLabel('Nom').fill('Forêt');
    await expect(form.getByRole('figure', { name: "Aperçu de l'étiquette" })).toContainText(`${typeName} Forêt`);
    await form.getByLabel('Prix de vente').fill('15');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Produit « ${typeName} Forêt » ajouté.`);
    await page.getByLabel('Rechercher un produit').fill(`${typeName} Forêt`);
    await expect(page.getByRole('row').filter({ hasText: `${typeName} Forêt` })).toContainText(typeName);
});

test('filter by type and edit the selection in batch', async ({ page, request }) => {
    const sticker = await createType(request, unique('Sticker'));
    const mousse = await createProduct(request, { name: 'Mousse', sellingPrice: 400, type: sticker });
    const fougere = await createProduct(request, { name: 'Fougère', sellingPrice: 450, type: sticker });
    await defineVariants(request, sticker, ['Mat', 'Brillant']);
    const other = await createProduct(request, { name: unique('Print'), sellingPrice: 1_500 });

    await page.goto('/products');
    await page.getByRole('group', { name: 'Filtrer par type' }).getByRole('button', { name: sticker.name }).click();
    await expect(page.getByRole('row').filter({ hasText: other.displayName })).toHaveCount(0);
    await page.getByRole('checkbox', { name: 'Tout sélectionner' }).check();
    await expect(page.getByRole('region', { name: 'Sélection' })).toContainText('2 produits sélectionnés');

    await page.getByRole('button', { name: 'Modifier la sélection' }).click();
    const batch = page.getByRole('dialog', { name: 'Modifier la sélection' });
    await batch.getByLabel('Prix de vente').fill('5');
    await batch.getByRole('group', { name: 'Variantes à ajouter' }).getByRole('button', { name: 'Brillant' }).click();
    await batch.getByRole('button', { name: 'Appliquer à 2 produits' }).click();

    await expect(page.getByTestId('toast')).toContainText('2 produits mis à jour.');
    for (const product of [mousse, fougere]) {
        const row = page.getByRole('row').filter({ hasText: product.displayName });
        await expect(row).toContainText('5,00');
        await expect(row).toContainText('Brillant');
    }
});

test('long lists are paginated', async ({ page, request }) => {
    const type = await createType(request, unique('Carte'));
    for (let i = 1; i <= 25; i++) {
        await createProduct(request, { name: `Modèle ${String(i).padStart(2, '0')}`, sellingPrice: 300, type });
    }

    await page.goto('/products');
    await page.getByRole('group', { name: 'Filtrer par type' }).getByRole('button', { name: type.name }).click();

    const pagination = page.getByRole('navigation', { name: 'Pagination' });
    await expect(pagination).toContainText('1–20 sur 25');
    await expect(page.getByRole('row')).toHaveCount(21); // header + 20
    await pagination.getByRole('button', { name: 'Page suivante' }).click();
    await expect(pagination).toContainText('21–25 sur 25');
    await expect(page.getByRole('row').filter({ hasText: `${type.name} Modèle 25` })).toBeVisible();
    await expect(pagination.getByRole('button', { name: 'Page 2' })).toHaveAttribute('aria-current', 'page');

    await choose(page, pagination.getByRole('combobox', { name: 'Par page' }), '50');
    await expect(page.getByRole('row')).toHaveCount(26);
    await expect(pagination).toContainText('1–25 sur 25');
});

test('the dashboard leads to the products never bought', async ({ page, request }) => {
    const unknown = await createProduct(request, { name: unique('Mystère'), sellingPrice: 1_000, buyingPrice: 0 });
    const known = await createProduct(request, { name: unique('Connu'), sellingPrice: 1_000, buyingPrice: 400 });

    await page.goto('/dashboard');
    await page.getByRole('status').getByRole('link', { name: "Voir les produits sans coût d'achat" }).click();

    const missingCost = page.getByRole('button', { name: /sans coût d'achat/ });
    await expect(missingCost).toHaveAttribute('data-state', 'on');
    await page.getByLabel('Rechercher un produit').fill(unknown.name);
    await expect(page.getByRole('row').filter({ hasText: unknown.name })).toBeVisible();
    await page.getByLabel('Rechercher un produit').fill(known.name);
    await expect(page.getByRole('row').filter({ hasText: known.name })).toHaveCount(0);

    await missingCost.click();
    const row = page.getByRole('row').filter({ hasText: known.name });
    await expect(row).toContainText('6,00');
    await expect(row).toContainText('60 %');
});

test('delete a product', async ({ page, request }) => {
    const doomed = await createProduct(request, { name: unique('Obsolète') });

    await page.goto('/products');
    await page.getByLabel('Rechercher un produit').fill(doomed.name);
    await page.getByRole('button', { name: `Supprimer ${doomed.name}` }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Confirmer ?' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Produit « ${doomed.name} » supprimé.`);
    await expect(page.getByRole('row').filter({ hasText: doomed.name })).toHaveCount(0);
});

test('create a product type with a colour and a suggested code, then rename it, change its code and give it a custom colour offered to other types', async ({ page, request }) => {
    const name = unique('Carte');
    const renamed = unique('Affiche');
    const code = uniqueCode('A').slice(0, 8);

    await page.goto('/products');
    await page.getByRole('button', { name: 'Types de produit' }).click();
    const types = page.getByRole('dialog', { name: 'Types de produit' });
    await types.getByRole('button', { name: 'Ajouter un type' }).click();
    const dialog = page.getByRole('dialog', { name: 'Nouveau type de produit' });
    await dialog.getByRole('textbox', { name: /^Nom/ }).fill(name);
    await dialog.getByRole('button', { name: 'Code et affichage' }).click();
    await expect(dialog.getByRole('textbox', { name: /^Code/ })).toHaveValue(/^CAR\d*$/);
    await dialog.getByRole('option', { name: 'Sarcelle' }).click();
    await dialog.getByRole('button', { name: 'Créer le type' }).click();
    await expect(page.getByTestId('toast')).toContainText(`Type « ${name} » créé.`);
    await expect(types.getByRole('listitem').filter({ hasText: name })).toBeVisible();

    await types.getByRole('button', { name: `Modifier le type ${name}` }).click();
    const edit = page.getByRole('dialog', { name: `Modifier le type ${name}` });
    await expect(edit.getByRole('option', { name: 'Sarcelle' })).toHaveAttribute('aria-selected', 'true');
    await edit.getByRole('textbox', { name: /^Nom/ }).fill(renamed);
    await edit.getByRole('button', { name: 'Code et affichage' }).click();
    await edit.getByRole('textbox', { name: /^Code/ }).fill(code.toLowerCase());
    await edit.getByRole('button', { name: 'Couleur personnalisée' }).click();
    const custom = page.getByRole('dialog', { name: 'Couleur personnalisée' });
    await custom.getByLabel('Code hexadécimal').fill('#123abc');
    await custom.getByLabel('Code hexadécimal').press('Enter');
    await custom.getByRole('button', { name: 'Ajouter', exact: true }).click();
    await expect(edit.getByRole('option', { name: 'Personnalisée #123ABC' })).toHaveAttribute('aria-selected', 'true');
    await edit.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByTestId('toast').filter({ hasText: `Type « ${renamed} » modifié.` })).toBeVisible();
    await expect(types).toContainText(renamed);
    const saved = (await (await request.get('/api/product-types')).json()).find((type) => type.name === renamed);
    expect(saved.color).toBe('#123abc');
    expect(saved.code).toBe(code);

    await types.getByRole('button', { name: 'Ajouter un type' }).click();
    await expect(page.getByRole('dialog', { name: 'Nouveau type de produit' }).getByRole('option', { name: 'Personnalisée #123ABC' })).toBeVisible();
});
