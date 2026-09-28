import { test, expect } from '@playwright/test';

// The e2e app runs in the test env: SumUp is replaced by tests/Fixtures/sumup/transactions.json
// (payments on 14, 15, 21 and 23 March 2030, Paris time).
test('import SumUp orders: one error for uncovered dates, no duplicates on re-import', async ({ page, request }) => {
    const response = await request.post('/api/events', {
        data: { name: 'Salon de printemps 2030', location: 'Lyon', startDate: '2030-03-14', endDate: '2030-03-15' },
    });
    expect(response.status()).toBe(201);

    await page.goto('/commandes');
    await page.getByRole('button', { name: 'Importer depuis SumUp' }).click();

    await expect(page.getByTestId('toast')).toContainText('2 commande(s) importée(s), 3 produit(s) créé(s)');
    const problem = page.getByTestId('sumup-import-problem');
    await expect(problem).toHaveCount(1);
    await expect(problem).toContainText('2 commande(s) non importée(s)');
    await expect(problem).toContainText('21 mars 2030, 23 mars 2030');
    await expect(page.getByRole('row').filter({ hasText: 'TFAKE0001' })).toContainText('Salon de printemps 2030');
    await expect(page.getByRole('row').filter({ hasText: 'TFAKE0002' })).toContainText('SumUp');

    await page.getByRole('button', { name: 'Importer depuis SumUp' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('0 commande(s) importée(s), 0 produit(s) créé(s), 2 déjà importée(s)');
    await expect(page.getByRole('row').filter({ hasText: /TFAKE000[12]/ })).toHaveCount(2);

    // Imported products have an unknown (0 €) buying price, editable later.
    await page.goto('/produits');
    await expect(page.getByRole('row').filter({ hasText: 'Tote bag' })).toContainText('0,00');
});
