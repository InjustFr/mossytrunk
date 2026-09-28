import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';

test('create a product with variants, then set its buying price', async ({ page }) => {
    const name = unique('T-shirt');
    const reference = unique('TS').replace(' ', '-');

    await page.goto('/produits');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await form.getByLabel('Référence').fill(reference);
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

    await expect(form.getByText('La référence est obligatoire.')).toBeVisible();
    await expect(form.getByText('Le nom est obligatoire.')).toBeVisible();
});

test('create a type inline and display products as "Type Nom"', async ({ page }) => {
    const typeName = unique('Print');
    const reference = unique('PR').replace(' ', '-');

    await page.goto('/produits');
    await page.getByRole('button', { name: 'Nouveau produit' }).click();
    const form = page.getByRole('dialog', { name: 'Nouveau produit' }).locator('form');
    await form.getByLabel('Référence').fill(reference);
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
