import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';
import { createEvent, createProduct } from './support/api.js';

const notebookPage = path.join(path.dirname(fileURLToPath(import.meta.url)), '../../tests/Fixtures/notebook/page.png');

async function productNamed(request, name) {
    const products = await (await request.get('/api/products')).json();
    return products.find((product) => product.displayName === name) ?? createProduct(request, { name, sellingPrice: 1_200 });
}

async function placeOrder(request, event, time, lines) {
    const response = await request.post('/api/orders', { data: { placedAt: `${event.startDate}T${time}`, lines: lines.map(([product, quantity]) => ({ productId: product.id, variant: null, quantity })) } });
    expect(response.status()).toBe(201);
}

test('the photographed notebook is compared sale by sale with the event orders', async ({ page, request }) => {
    const lichen = await productNamed(request, 'Carnet Lichen');
    const fern = await productNamed(request, 'Carnet Fougère');
    const event = await createEvent(request);
    await placeOrder(request, event, '10:00', [[lichen, 2]]);
    await placeOrder(request, event, '11:00', [[lichen, 1], [fern, 2]]);
    await placeOrder(request, event, '12:00', [[lichen, 5]]);

    await page.goto(`/events/${event.id}`);
    await page.getByRole('link', { name: 'Comparer le carnet' }).click();
    await page.getByLabel('Ajouter des photos').setInputFiles([notebookPage, notebookPage]);
    await expect(page.getByRole('img', { name: 'Page 2' })).toBeVisible();
    await page.getByRole('button', { name: 'Retirer la page 2' }).click();
    await expect(page.getByRole('img', { name: 'Page 2' })).toHaveCount(0);
    await page.getByRole('button', { name: 'Analyser le carnet' }).click();

    await expect(page.getByTestId('toast')).toContainText('Carnet analysé : 3 ventes lues.');
    const notRecorded = page.getByRole('region', { name: "Notées dans le carnet, absentes de l'app" });
    await expect(notRecorded).toContainText('Vente 2 du carnet · page 1');
    await expect(notRecorded).toContainText('1 × Carnet Fougère');
    const notNoted = page.getByRole('region', { name: "Dans l'app, absentes du carnet" });
    await expect(notNoted).toContainText('5 × Carnet Lichen');
    const differing = page.getByRole('region', { name: 'Ventes avec écart' });
    await expect(differing).toContainText('Vendu seulement');
    await expect(differing).toContainText('1 × Carnet Fougère');
    await page.getByRole('button', { name: 'Ventes concordantes (1)' }).click();
    await expect(page.getByText('2 × Carnet Lichen').first()).toBeVisible();

    await notNoted.getByRole('link').click();
    await expect(page).toHaveURL(/\/orders\//);
    await page.goBack();
    await expect(page.getByRole('button', { name: 'Nouvelle analyse' })).toBeVisible();
});
