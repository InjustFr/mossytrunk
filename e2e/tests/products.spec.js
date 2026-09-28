import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';

test('create a product with variants, then set its buying price', async ({ page }) => {
    const name = unique('T-shirt');

    await page.goto('/produits');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await form.getByLabel('Nom').fill(name);
    await form.getByLabel('Prix de vente (€)').fill('20');
    await form.getByLabel('Nouvelle variante').fill('Mousse');
    await form.getByLabel('Nouvelle variante').press('Enter');
    await form.getByLabel('Nouvelle variante').fill('Fougère');
    await form.getByLabel('Nouvelle variante').press('Enter');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Produit « ${name} » ajouté.`);
    const row = page.getByRole('row').filter({ hasText: name });
    await expect(row).toContainText('Mousse, Fougère');
    await expect(row).toContainText('0,00');

    await expect(page.getByRole('dialog')).toHaveCount(0);

    await row.getByRole('button', { name: `Modifier ${name}` }).click();
    const edit = page.getByRole('dialog', { name: 'Modifier le produit' });
    await expect(edit.getByLabel('Nom')).toHaveValue(name);
    await expect(edit.getByText(/Référence PRD-T-SHIRT/)).toBeVisible();
    await edit.getByLabel("Prix d'achat (€)").fill('7,50');
    await edit.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByTestId('toast').last()).toContainText('mis à jour');
    await expect(row).toContainText('7,50');
});

test('shows validation errors inline', async ({ page }) => {
    await page.goto('/produits');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog').locator('form');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(form.getByText('Le nom est obligatoire.')).toBeVisible();
});

test('create a type inline and display products as "Type Nom"', async ({ page }) => {
    const typeName = unique('Print');

    await page.goto('/produits');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await form.getByRole('combobox', { name: 'Type' }).selectOption({ label: '＋ Créer un type…' });
    await form.getByLabel('Nom du nouveau type').fill(typeName);
    await form.getByRole('button', { name: 'Créer', exact: true }).click();
    await expect(form.getByRole('combobox', { name: 'Type' })).toHaveValue(/.+/);

    await form.getByLabel('Nom').fill('Forêt');
    await expect(form.getByText(`Affiché « ${typeName} Forêt »`)).toBeVisible();
    await form.getByLabel('Prix de vente (€)').fill('15');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Produit « ${typeName} Forêt » ajouté.`);
    await expect(page.getByRole('row').filter({ hasText: `${typeName} Forêt` })).toContainText(typeName);
});

test('filter by type and edit the selection in batch', async ({ page, request }) => {
    const { createProduct, createType } = await import('./support/api.js');
    const sticker = await createType(request, unique('Sticker'));
    const mousse = await createProduct(request, { name: 'Mousse', sellingPrice: 400, type: sticker });
    const fougere = await createProduct(request, { name: 'Fougère', sellingPrice: 450, type: sticker });
    const other = await createProduct(request, { name: unique('Print'), sellingPrice: 1_500 });

    await page.goto('/produits');
    await page.getByRole('group', { name: 'Filtrer par type' }).getByRole('button', { name: sticker.name }).click();
    await expect(page.getByRole('row').filter({ hasText: other.displayName })).toHaveCount(0);
    await page.getByRole('checkbox', { name: 'Tout sélectionner' }).check();
    await expect(page.getByRole('region', { name: 'Sélection' })).toContainText('2 produit(s) sélectionné(s)');

    await page.getByRole('button', { name: 'Modifier la sélection' }).click();
    const batch = page.getByRole('dialog', { name: 'Modifier la sélection' });
    await batch.getByLabel('Changer le prix de vente').check();
    await batch.getByLabel('Nouveau prix de vente (€)').fill('5');
    await batch.getByRole('textbox', { name: 'Variante à ajouter' }).fill('Brillant');
    await batch.getByRole('textbox', { name: 'Variante à ajouter' }).press('Enter');
    await batch.getByRole('button', { name: 'Appliquer à 2 produit(s)' }).click();

    await expect(page.getByTestId('toast')).toContainText('2 produit(s) mis à jour.');
    for (const product of [mousse, fougere]) {
        const row = page.getByRole('row').filter({ hasText: product.displayName });
        await expect(row).toContainText('5,00');
        await expect(row).toContainText('Brillant');
    }
});
