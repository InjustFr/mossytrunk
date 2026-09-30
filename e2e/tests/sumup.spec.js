import { test, expect } from '@playwright/test';
import { addSumUp } from './support/api.js';
import { choose } from './support/select.js';

test('import SumUp orders: one error for uncovered dates, no duplicates on re-import', async ({ page, request }) => {
    await addSumUp(request);
    const response = await request.post('/api/events', {
        data: { name: 'Salon de printemps 2030', location: 'Lyon', startDate: '2030-03-14', endDate: '2030-03-15' },
    });
    expect(response.status()).toBe(201);

    await page.goto('/orders');
    await page.getByRole('button', { name: 'Importer depuis SumUp' }).click();

    await expect(page.getByTestId('toast')).toContainText('2 commandes importées');
    await expect(page.getByTestId('toast')).toContainText('3 produits créés');
    const problem = page.getByTestId('import-problem');
    await expect(problem).toHaveCount(1);
    await expect(problem).toContainText('2 commandes non importées');
    await expect(problem).toContainText('21 mars 2030, 23 mars 2030');
    await choose(page, page.getByRole('combobox', { name: 'Événement' }), 'Salon de printemps 2030');
    await expect(page.getByRole('row').filter({ hasText: 'TFAKE0001' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Salon de printemps 2030' }).first()).toBeVisible();
    await expect(page.getByRole('row').filter({ hasText: 'TFAKE0002' })).toContainText('SumUp');
    await expect(page.getByRole('row').filter({ hasText: 'TFAKE0002' })).toContainText('Espèces');
    await expect(page.getByRole('row').filter({ hasText: 'TFAKE0001' })).toContainText('Carte');

    await page.getByRole('button', { name: 'Importer depuis SumUp' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('0 commande importée, 2 déjà importées');
    await expect(page.getByRole('row').filter({ hasText: /TFAKE000[12]/ })).toHaveCount(2);

    await page.goto('/products');
    await page.getByLabel('Rechercher un produit').fill('Tote bag');
    await expect(page.getByRole('row').filter({ hasText: 'Tote bag' })).toContainText('0,00');
});

test('an amount typed on the terminal is imported without product, then linked to one', async ({ page, request }) => {
    await addSumUp(request);
    await request.post('/api/events', { data: { name: 'Salon de printemps 2030', location: 'Lyon', startDate: '2030-03-14', endDate: '2030-03-15' } });
    await request.post('/api/services/sumup/import');

    await page.goto('/orders');
    await choose(page, page.getByRole('combobox', { name: 'Événement' }), 'Salon de printemps 2030');
    await page.getByRole('row').filter({ hasText: 'TFAKE0002' }).getByRole('link', { name: 'TFAKE0002' }).click();
    await expect(page.getByRole('row').filter({ hasText: 'Produit inconnu' })).toContainText('2,00');

    await page.getByRole('button', { name: 'Choisir le produit' }).click();
    const dialog = page.getByRole('dialog', { name: 'Choisir le produit' });
    await choose(page, dialog.getByRole('combobox', { name: 'Produit' }), 'Tote bag');
    await dialog.getByRole('button', { name: 'Associer' }).click();

    await expect(page.getByTestId('toast')).toContainText('Vente associée à « Tote bag ».');
    await expect(page.getByRole('row').filter({ hasText: 'Produit inconnu' })).toHaveCount(0);
    await expect(page.getByRole('row').filter({ hasText: 'Tote bag' })).toHaveCount(2);
});
