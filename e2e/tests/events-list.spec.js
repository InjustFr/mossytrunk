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
    const item = page.getByRole('listitem').filter({ hasText: event.name });
    await expect(item).toContainText('Dépenses engagées');
    await expect(item).toContainText('100,00');
    // CA 150 − coût 50 − dépenses 100 − URSSAF 19,20 = −19,20
    await expect(page.getByTestId(`event-result-${event.id}`)).toHaveText(/[−-]19,20/);
});

test('events are split between upcoming and past ones', async ({ page, request }) => {
    const past = await createEvent(request, { name: unique('Salon passé'), startDate: '2019-05-04' });
    const upcoming = await createEvent(request, { name: unique('Salon à venir') });

    await page.goto('/evenements');
    const upcomingSection = page.getByRole('region', { name: 'À venir' });
    const pastSection = page.getByRole('region', { name: 'Passés' });

    await expect(upcomingSection.getByRole('link', { name: new RegExp(upcoming.name) })).toBeVisible();
    await expect(upcomingSection.getByRole('link', { name: new RegExp(past.name) })).toHaveCount(0);
    await expect(pastSection.getByRole('table').getByRole('link', { name: new RegExp(past.name) })).toBeVisible();
    await expect(pastSection.getByRole('link', { name: new RegExp(upcoming.name) })).toHaveCount(0);
    await expect(pastSection.getByRole('row').filter({ hasText: past.name })).toContainText('Lyon');
});
