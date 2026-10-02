import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';
import { createEvent, createProduct, createType, restock } from './support/api.js';

test('restock a product twice and read its lots, oldest first', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Sticker'), sellingPrice: 400 });

    await page.goto('/products');
    await page.getByLabel('Rechercher un produit').fill(product.name);
    const row = page.getByRole('row').filter({ hasText: product.name });
    await expect(row.getByText('Stock bas')).toBeVisible();

    for (const [quantity, total] of [['10', '5'], ['10', '10']]) {
        await row.getByRole('button', { name: `Réapprovisionner ${product.name}` }).click();
        const form = page.getByRole('dialog', { name: `Réapprovisionner ${product.name}` }).locator('form');
        await form.getByRole('spinbutton', { name: 'Quantité reçue' }).fill(quantity);
        await form.getByLabel('Total payé').fill(total);
        await form.getByRole('button', { name: 'Ajouter au stock' }).click();
        await expect(page.getByTestId('toast').last()).toContainText(`10 unités ajoutées au stock de « ${product.name} »`);
    }

    await expect(row).toContainText('20');
    await expect(row.getByText('Stock bas')).toHaveCount(0);
    await expect(row).toContainText('0,75');

    await row.getByRole('button', { name: `Historique de la réserve de ${product.name}` }).click();
    const history = page.getByRole('dialog', { name: `Réserve de ${product.name}` });
    const lots = history.getByRole('list', { name: 'Stock restant, du plus ancien au plus récent' }).getByRole('listitem');
    await expect(lots).toHaveCount(2);
    await expect(lots.first()).toContainText('0,50');
});

test('an inventory with missing units flags a missing order until it is dismissed', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Pin'), sellingPrice: 800 });
    await restock(request, product, { quantity: 10, totalPaid: 2_000 });
    const event = await createEvent(request);

    await page.goto(`/events/${event.id}`);
    await page.getByRole('link', { name: "Faire l'inventaire" }).click();
    await page.getByLabel('Rechercher un article').fill(product.name);
    const count = page.getByLabel(`Quantité comptée de ${product.name}`);
    await count.fill('7');
    await count.press('Enter');
    await expect(page.getByRole('row').filter({ hasText: product.name })).toContainText('-3');
    await page.getByRole('button', { name: "Enregistrer l'inventaire" }).click();

    await expect(page.getByTestId('toast').last()).toContainText('Inventaire enregistré');
    await expect(page).toHaveURL(new RegExp(`/events/${event.id}$`));
    const banner = page.getByRole('alert').filter({ hasText: 'Commande manquante probable' });
    await expect(banner).toContainText('3 articles');
    await expect(banner).toContainText('24,00');

    await banner.getByRole('button', { name: "Classer l'écart" }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Classer' }).click();
    await expect(banner).toHaveCount(0);
});

test('flyers missing at the inventory are consumed by the event, not flagged', async ({ page, request }) => {
    const type = await createType(request, unique('Papeterie'), { prefixesNames: false });
    const name = unique('Flyer');
    const flyer = await (await request.post('/api/products', { data: { name, typeId: type.id, kind: 'supply' } })).json();
    await restock(request, flyer, { quantity: 100, totalPaid: 1_000 });
    const event = await createEvent(request);
    const sticker = await createProduct(request, { name: unique('Sticker') });
    await request.post('/api/orders', { data: { placedAt: `${event.startDate}T10:00`, lines: [{ productId: sticker.id, variant: null, quantity: 1 }] } });

    await page.goto(`/events/${event.id}`);
    await page.getByRole('link', { name: "Faire l'inventaire" }).click();
    await page.getByLabel('Rechercher un article').fill(name);
    const count = page.getByLabel(`Quantité comptée de ${name}`);
    await count.fill('60');
    await count.press('Enter');
    await page.getByRole('button', { name: "Enregistrer l'inventaire" }).click();

    await expect(page.getByTestId('toast').last()).toContainText('Inventaire enregistré');
    await expect(page.getByRole('alert').filter({ hasText: 'Commande manquante probable' })).toHaveCount(0);
    await expect(page.getByText('Fournitures consommées (inventaire)')).toBeVisible();
    await expect(page.locator('main')).toContainText('4,00');
});
