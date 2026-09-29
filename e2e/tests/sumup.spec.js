import { test, expect } from '@playwright/test';
import { configureSumUp } from './support/api.js';
import { choose } from './support/select.js';

// The e2e app runs in the test env: SumUp is replaced by tests/Fixtures/sumup/transactions.json
// (payments on 14, 15, 21 and 23 March 2030, Paris time).
test('import SumUp orders: one error for uncovered dates, no duplicates on re-import', async ({ page, request }) => {
    await configureSumUp(request);
    const response = await request.post('/api/events', {
        data: { name: 'Salon de printemps 2030', location: 'Lyon', startDate: '2030-03-14', endDate: '2030-03-15' },
    });
    expect(response.status()).toBe(201);

    await page.goto('/commandes');
    await page.getByRole('button', { name: 'Importer depuis SumUp' }).click();

    await expect(page.getByTestId('toast')).toContainText('2 commandes importées, 3 produits créés');
    const problem = page.getByTestId('sumup-import-problem');
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
    await expect(page.getByTestId('toast').last()).toContainText('0 commande importée, 0 produit créé, 2 déjà importées');
    await expect(page.getByRole('row').filter({ hasText: /TFAKE000[12]/ })).toHaveCount(2);

    // Imported products have an unknown (0 €) buying price, editable later.
    await page.goto('/produits');
    await page.getByLabel('Rechercher un produit').fill('Tote bag');
    await expect(page.getByRole('row').filter({ hasText: 'Tote bag' })).toContainText('0,00');
});
