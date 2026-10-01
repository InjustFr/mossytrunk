import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';
import { createProduct, createType } from './support/api.js';

test('a sales channel gets its own prices, one by one or in batch', async ({ page, request }) => {
    const main = (await (await request.get('/api/sales-channels')).json()).find((channel) => channel.main);
    const name = unique('Boutique');
    const type = await createType(request, unique('Badge'));
    const moss = await createProduct(request, { name: 'Mousse', sellingPrice: 400, type });
    const fern = await createProduct(request, { name: 'Fougère', sellingPrice: 600, type });

    await page.goto('/settings');
    const channels = page.getByRole('region', { name: 'Canaux de vente' });
    await expect(channels).toContainText(main.name);
    await expect(channels.getByRole('button', { name: `Supprimer ${main.name}` })).toHaveCount(0);
    await page.getByRole('button', { name: 'Ajouter un canal' }).click();
    const dialog = page.getByRole('dialog', { name: 'Ajouter un canal' });
    await dialog.getByLabel('Nom').fill(name);
    await dialog.getByRole('radio', { name: /^En ligne/ }).click();
    await dialog.getByRole('button', { name: 'Ajouter le canal' }).click();
    await expect(page.getByTestId('toast')).toContainText(`Canal « ${name} » ajouté.`);
    await expect(channels).toContainText(name);

    await page.goto('/products');
    await page.getByRole('group', { name: 'Filtrer par type' }).getByRole('button', { name: type.name }).click();
    await page.getByRole('checkbox', { name: 'Tout sélectionner' }).check();
    await page.getByRole('button', { name: 'Modifier la sélection' }).click();
    const batch = page.getByRole('dialog', { name: 'Modifier la sélection' });
    await choose(page, batch.getByRole('combobox', { name: 'Prix à modifier' }), name);
    await choose(page, batch.getByRole('combobox', { name: 'Nouveau prix' }), "D'après un autre prix");
    await expect(batch.getByRole('combobox', { name: 'À partir de' })).toContainText(main.name);
    await batch.getByRole('spinbutton', { name: 'Ajustement' }).fill('10');
    await batch.getByRole('spinbutton', { name: 'Ajustement' }).press('Tab');
    await expect(batch.getByRole('list', { name: 'Aperçu' })).toContainText('4,40');
    await batch.getByRole('button', { name: 'Appliquer à 2 produits' }).click();
    await expect(page.getByTestId('toast')).toContainText('2 produits mis à jour.');

    await expect(page.getByRole('columnheader', { name: name })).toBeVisible();
    await expect(page.getByRole('row').filter({ hasText: moss.displayName })).toContainText('4,40');
    await expect(page.getByRole('row').filter({ hasText: fern.displayName })).toContainText('6,60');

    await page.getByRole('button', { name: `Modifier ${moss.displayName}` }).click();
    await page.getByRole('dialog').getByLabel(`Prix ${name}`).fill('5');
    await page.getByRole('dialog').getByRole('button', { name: 'Enregistrer' }).click();
    await expect(page.getByRole('row').filter({ hasText: moss.displayName })).toContainText('5,00');

    await page.goto('/settings');
    await page.getByRole('button', { name: `Supprimer ${name}` }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: /Supprimer|Confirmer/ }).click();
    await expect(page.getByTestId('toast').last()).toContainText(`Canal « ${name} » supprimé.`);
});
