import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';

test('create a product with variants, then set its buying price', async ({ page }) => {
    const name = unique('T-shirt');
    const reference = unique('TS').replace(' ', '-');

    await page.goto('/produits');
    const form = page.locator('form.product-form');
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

    await row.getByRole('button', { name: `Modifier ${name}` }).click();
    await form.getByLabel("Prix d'achat (€)").fill('7,50');
    await form.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByTestId('toast').last()).toContainText('mis à jour');
    await expect(row).toContainText('7,50');
});

test('shows validation errors inline', async ({ page }) => {
    await page.goto('/produits');
    const form = page.locator('form.product-form');
    await form.getByRole('button', { name: 'Ajouter le produit' }).click();

    await expect(form.getByText('La référence est obligatoire.')).toBeVisible();
    await expect(form.getByText('Le nom est obligatoire.')).toBeVisible();
});
