import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { createProduct } from './support/api.js';

test('connect the Etsy shop, import its orders and link an unknown listing to a product', async ({ page, request }) => {
    await createProduct(request, { name: 'Sticker Mousse', sellingPrice: 400 });
    const print = await createProduct(request, { name: 'Print Forêt Etsy', sellingPrice: 1_800, variants: ['A4', 'A3'] });

    await page.goto('/parametres');
    const card = page.getByRole('region', { name: 'Etsy' });
    await expect(card.getByRole('link', { name: 'Connecter ma boutique Etsy' })).toHaveCount(0);
    await card.getByRole('textbox', { name: 'Keystring', exact: true }).fill('keystring123');
    await card.locator('input[type="password"]').fill('shared-secret');
    await card.getByRole('button', { name: 'Enregistrer les clés' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Clés Etsy enregistrées.');
    await card.getByRole('link', { name: 'Connecter ma boutique Etsy' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Boutique Etsy connectée');
    await expect(card).toContainText('Atelier Mousse sur Etsy');

    await page.goto('/commandes');
    await page.getByRole('button', { name: 'Importer depuis Etsy' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('1 commande importée');
    await page.getByRole('button', { name: 'Associer les annonces' }).click();

    const linker = page.getByRole('dialog', { name: 'Annonces Etsy' });
    await choose(page, linker.getByRole('combobox', { name: /^Produit pour Illustration forêt/ }), print.name);
    await expect(linker.getByRole('combobox', { name: /^Variante pour/ })).toHaveText('A4');
    await linker.getByRole('button', { name: 'Associer', exact: true }).click();
    await expect(page.getByTestId('toast').last()).toContainText('associée');

    await linker.getByRole('button', { name: "Relancer l'import Etsy" }).click();
    await expect(page.getByTestId('toast').last()).toContainText('1 commande importée, 1 déjà importée');
    await expect(page.getByRole('dialog')).toHaveCount(0);

    await page.getByRole('link', { name: 'ETSY-3100000001' }).click();
    await expect(page.getByText("Boutique Etsy · importée d'Etsy")).toBeVisible();
    await expect(page.getByText('Frais de port')).toBeVisible();
    await expect(page.getByTestId('order-total')).toContainText('27,50');
});
