import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { createProduct, forgetService } from './support/api.js';

test('connect the Etsy shop, import its orders and link an unknown listing to a product', async ({ page, request }) => {
    await createProduct(request, { name: 'Sticker Mousse', sellingPrice: 400 });
    const print = await createProduct(request, { name: 'Print Forêt Etsy', sellingPrice: 1_800, variants: ['A4', 'A3'] });

    await forgetService(request, 'etsy');
    await page.goto('/settings');
    await page.getByRole('button', { name: 'Ajouter un service' }).click();
    await page.getByRole('dialog', { name: 'Ajouter un service' }).getByRole('button', { name: /^Etsy/ }).click();
    const form = page.getByRole('dialog', { name: 'Ajouter Etsy' });
    await expect(form.locator('code')).toContainText('/settings/etsy/callback');
    await form.getByRole('textbox', { name: 'Keystring', exact: true }).fill('keystring123');
    await form.locator('input[type="password"]').fill('shared-secret');
    await form.getByRole('button', { name: 'Ajouter Etsy' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Etsy ajouté.');

    const services = page.getByRole('region', { name: 'Services connectés' });
    await expect(services).toContainText('À connecter');
    await services.getByRole('link', { name: 'Connecter la boutique' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Etsy connecté');
    await expect(services).toContainText('Atelier Mousse sur Etsy');

    await services.getByRole('button', { name: 'Importer le catalogue' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Catalogue Etsy importé');
    await services.getByRole('button', { name: 'Écrire les références sur Etsy' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Écrire les références' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Etsy :');

    await page.goto('/orders');
    await page.getByRole('button', { name: 'Importer depuis Etsy' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('1 commande importée');
    await page.getByRole('button', { name: 'Associer les articles' }).click();

    const linker = page.getByRole('dialog', { name: 'Articles Etsy' });
    const a4 = linker.getByRole('listitem').filter({ has: page.getByRole('combobox', { name: /^Produit pour Illustration forêt.* — A4$/ }) });
    await choose(page, a4.getByRole('combobox', { name: /^Produit pour/ }), print.name);
    await expect(a4.getByRole('combobox', { name: /^Variante pour/ })).toHaveText('A4');
    await a4.getByRole('button', { name: 'Associer', exact: true }).click();
    await expect(page.getByTestId('toast').last()).toContainText('associé');

    await linker.getByRole('button', { name: "Relancer l'import Etsy" }).click();
    await expect(page.getByTestId('toast').last()).toContainText('1 commande importée, 1 déjà importée');
    await expect(linker.getByRole('listitem')).toHaveCount(1);
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog')).toHaveCount(0);

    await page.getByRole('row').filter({ hasText: 'ETSY-3100000001' }).getByRole('link', { name: /^CMD-/ }).click();
    await expect(page.getByText('Boutique Etsy · importée depuis Etsy')).toBeVisible();
    await expect(page.getByText('Frais de port')).toBeVisible();
    await expect(page.getByTestId('order-total')).toContainText('27,50');
});
