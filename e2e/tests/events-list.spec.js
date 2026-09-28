import { test, expect } from '@playwright/test';
import { createEvent, createProduct } from './support/api.js';
import { unique } from './support/unique.js';

test('events list shows each event turnover and result', async ({ page, request }) => {
    const event = await createEvent(request);
    const print = await createProduct(request, { name: unique('Print'), sellingPrice: 1_500, buyingPrice: 500 });
    await request.post(`/api/events/${event.id}/expenses`, { data: { label: 'Stand', amount: 10_000 } });
    const response = await request.post('/api/orders', {
        data: { placedAt: `${event.startDate}T11:00`, lines: [{ productId: print.id, variant: null, quantity: 10 }] },
    });
    expect(response.status()).toBe(201);

    await page.goto('/evenements');
    const item = page.getByRole('link', { name: new RegExp(event.name) });
    await expect(item).toContainText('150,00');
    // CA 150 − coût 50 − dépenses 100 − URSSAF 19,20 = −19,20
    await expect(page.getByTestId(`event-result-${event.id}`)).toHaveText(/[−-]19,20/);
});
