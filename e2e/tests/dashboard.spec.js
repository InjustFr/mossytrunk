import { test, expect } from './support/test.js';
import { createEvent, createProduct } from './support/api.js';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';

test('dashboard shows the results of a year, month by month', async ({ page, request }) => {
    // A dedicated year far from other tests' data (uniqueDay uses 2040+): 2035.
    const event = await createEvent(request, { startDate: '2035-03-10' });
    const print = await createProduct(request, { name: unique('Print'), sellingPrice: 1_000, buyingPrice: 250 });
    await request.post(`/api/events/${event.id}/expenses`, { data: { label: 'Stand', amount: 2_000 } });
    const response = await request.post('/api/orders', {
        data: { placedAt: '2035-03-10T14:00', lines: [{ productId: print.id, variant: null, quantity: 10 }] },
    });
    expect(response.status()).toBe(201);

    await page.goto('/');
    await expect(page).toHaveURL(/\/dashboard$/);
    await choose(page, page.getByRole('combobox', { name: 'Année' }), '2035');

    // CA 100 − achats 25 − dépenses 20 − URSSAF 12,80 = 42,20
    await expect(page.getByTestId('year-result')).toHaveText(/42,20/);
    const march = page.getByRole('row').filter({ hasText: /^mars/ });
    await expect(march).toContainText('100,00');
    await expect(march).toContainText('12,80');
    await expect(page.getByRole('listitem', { name: /mars : résultat 42,20/ })).toBeVisible();
});
