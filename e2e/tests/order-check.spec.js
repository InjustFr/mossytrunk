import { test, expect } from './support/test.js';
import { createEvent, createProduct } from './support/api.js';
import { unique } from './support/unique.js';

test('the orders of an event are ticked off one by one, then the stock check follows', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Sticker'), sellingPrice: 400 });
    const event = await createEvent(request);
    const references = [];
    for (const [time, quantity] of [['10:00', 1], ['11:00', 3]]) {
        const response = await request.post('/api/orders', { data: { placedAt: `${event.startDate}T${time}`, lines: [{ productId: product.id, variant: null, quantity }] } });
        expect(response.status()).toBe(201);
        references.push((await response.json()).reference);
    }

    await page.goto(`/events/${event.id}`);
    await page.getByRole('link', { name: 'Vérifier les commandes' }).click();
    await expect(page.getByText('0 / 2 commandes vérifiées')).toBeVisible();
    await expect(page.getByRole('listitem').filter({ hasText: references[1] })).toContainText(`3 × ${product.name}`);

    await page.getByRole('checkbox', { name: `Commande ${references[0]} vérifiée` }).click();
    await expect(page.getByText('1 / 2 commandes vérifiées')).toBeVisible();
    await page.reload();
    await expect(page.getByRole('checkbox', { name: `Commande ${references[0]} vérifiée` })).toBeChecked();

    await page.getByText('Seulement les commandes à vérifier').click();
    await expect(page.getByRole('checkbox', { name: `Commande ${references[0]} vérifiée` })).toHaveCount(0);
    await page.getByRole('checkbox', { name: `Commande ${references[1]} vérifiée` }).click();
    await expect(page.getByText('Plus aucune commande à vérifier.')).toBeVisible();
    await expect(page.getByRole('status')).toContainText('Les 2 commandes sont vérifiées.');

    await page.getByRole('status').getByRole('link', { name: "Passer à l'inventaire" }).click();
    await expect(page).toHaveURL(new RegExp(`/events/${event.id}/stock-check$`));
});

test('the check can be stopped and resumed later', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Badge'), sellingPrice: 300 });
    const event = await createEvent(request);
    await request.post('/api/orders', { data: { placedAt: `${event.startDate}T10:00`, lines: [{ productId: product.id, variant: null, quantity: 1 }] } });

    await page.goto(`/events/${event.id}/check`);
    await page.getByRole('link', { name: "Arrêter pour l'instant" }).click();
    await expect(page).toHaveURL(new RegExp(`/events/${event.id}$`));
});
