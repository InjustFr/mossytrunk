import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';
import { createEvent, createProduct, restock } from './support/api.js';

test('the product report ranks what sold and tells one product story', async ({ page, request }) => {
    const event = await createEvent(request);
    const product = await createProduct(request, { name: unique('Carte'), sellingPrice: 500 });
    await restock(request, product, { quantity: 20, totalPaid: 2_000 });
    await request.post('/api/orders', { data: { placedAt: `${event.startDate}T10:00`, lines: [{ productId: product.id, variant: null, quantity: 3 }] } });

    await page.goto(`/reports/products?period=${event.startDate.slice(0, 4)}`);
    const palmares = page.getByRole('region', { name: 'Palmarès' });
    await palmares.getByRole('searchbox', { name: 'Rechercher un produit' }).fill(product.name);
    const row = palmares.getByRole('button', { name: `Voir le détail de ${product.displayName}` });
    await expect(row).toContainText('3 vendus');
    await row.click();

    await expect(page).toHaveURL(/product=/);
    const story = page.getByRole('article', { name: product.displayName });
    await expect(story).toContainText('17 en stock aujourd');
    await expect(story.getByRole('region', { name: 'Ventes par mois' })).toBeVisible();
    await expect(story.getByRole('region', { name: 'Mouvements de stock' })).toBeVisible();
    await expect(story.getByRole('region', { name: 'Remises' })).toBeVisible();

    await palmares.getByRole('button', { name: 'Articles vendus', exact: true }).click();
    await expect(row).toBeVisible();
});
