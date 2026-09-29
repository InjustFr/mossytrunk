import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';
import { createProduct } from './support/api.js';

test('order from a new supplier, receive more than ordered, and stock the real unit cost', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Print'), sellingPrice: 1_500, variants: ['A4'] });
    const supplier = unique('Imprimerie');

    await page.goto('/commandes-fournisseurs');
    await page.getByRole('button', { name: 'Nouvelle commande' }).click();
    const form = page.getByRole('dialog', { name: 'Nouvelle commande fournisseur' }).locator('form');
    await choose(page, form.getByRole('combobox', { name: 'Fournisseur' }), '＋ Nouveau fournisseur…');
    await form.getByLabel('Nom du nouveau fournisseur').fill(supplier);
    await form.getByRole('button', { name: 'Créer', exact: true }).click();
    await expect(form.getByRole('combobox', { name: 'Fournisseur' })).toHaveText(supplier);

    await choose(page, form.getByRole('combobox', { name: 'Produit' }), product.name);
    await choose(page, form.getByRole('combobox', { name: 'Variante' }), 'A4');
    await form.getByRole('spinbutton', { name: 'Quantité commandée' }).fill('20');
    await form.getByLabel('Prix total (€)').fill('60');
    await form.getByRole('button', { name: 'Ajouter', exact: true }).click();
    await expect(form.getByRole('row').filter({ hasText: `${product.name} — A4` })).toContainText('3,00');
    await form.getByRole('button', { name: 'Passer la commande' }).click();

    await expect(page.getByTestId('toast').last()).toContainText('Commande fournisseur passée.');
    const row = page.getByRole('row').filter({ hasText: supplier });
    await expect(row).toContainText('Commandée');
    await row.getByRole('link').click();

    await expect(page.getByRole('heading', { level: 1, name: /^CMF-/ })).toBeVisible();
    await page.getByRole('button', { name: 'Réceptionner' }).click();
    await expect(page.getByText('Ligne 1 sur 1')).toBeVisible();
    const received = page.getByRole('spinbutton', { name: 'Quantité reçue' });
    await received.fill('24');
    await received.press('Enter');
    await expect(page.getByRole('region', { name: 'Vérifier et valider' })).toContainText('+4');
    await expect(page.getByRole('region', { name: 'Vérifier et valider' })).toContainText('2,50');
    await page.getByRole('button', { name: 'Valider la réception' }).click();

    await expect(page.getByTestId('toast').last()).toContainText('réceptionnée');
    await expect(page.getByText('Reçue', { exact: true })).toBeVisible();

    await page.goto('/produits');
    await page.getByLabel('Rechercher un produit').fill(product.name);
    const productRow = page.getByRole('row').filter({ hasText: product.name });
    await expect(productRow).toContainText('24');
    await expect(productRow).toContainText('2,50');
});
