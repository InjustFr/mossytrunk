import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';
import { createProduct } from './support/api.js';

test('create, deactivate and delete a bundle discount', async ({ page, request }) => {
    const sticker = await createProduct(request, { name: unique('Sticker'), sellingPrice: 400 });
    const name = unique('3 stickers pour 10 €');

    await page.goto('/remises');
    await page.getByRole('button', { name: 'Nouvelle remise' }).click();
    const form = page.getByRole('dialog', { name: 'Nouvelle remise' }).locator('form');
    await form.getByLabel('Nom').fill(name);
    await form.getByLabel('Articles par lot').fill('3');
    await form.getByLabel('Prix du lot (€)').fill('10');
    await form.getByLabel('Filtrer les produits').fill(sticker.name);
    await form.getByRole('checkbox', { name: sticker.name }).check();
    await expect(form.getByText('Prix normal de 3 articles')).toContainText('12,00');
    await form.getByRole('button', { name: 'Créer la remise' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Remise « ${name} » créée.`);
    const item = page.getByTestId(`discount-rule-${name}`);
    await expect(item).toContainText('3 articles pour 10,00');
    await expect(item).toContainText(sticker.name);

    await item.getByRole('switch').uncheck();
    await expect(item).toContainText('Inactive');

    await item.getByRole('button', { name: 'Supprimer' }).click();
    await item.getByRole('button', { name: 'Confirmer ?' }).click();
    await expect(item).toHaveCount(0);
});

test('a discount on several types mixes their products in one bundle', async ({ page, request }) => {
    const { createEvent, createProduct, createType } = await import('./support/api.js');
    const printType = await createType(request, unique('Print'));
    const stickerType = await createType(request, unique('Sticker'));
    const foret = await createProduct(request, { name: 'Forêt', sellingPrice: 1_500, type: printType });
    const riviere = await createProduct(request, { name: 'Rivière', sellingPrice: 1_500, type: printType });
    const mousse = await createProduct(request, { name: 'Mousse', sellingPrice: 400, type: stickerType });
    const event = await createEvent(request);
    const name = unique('3 articles pour 30 €');

    await page.goto('/remises');
    await page.getByRole('button', { name: 'Nouvelle remise' }).click();
    const form = page.getByRole('dialog', { name: 'Nouvelle remise' }).locator('form');
    await form.getByLabel('Nom').fill(name);
    await form.getByLabel('Articles par lot').fill('3');
    await form.getByLabel('Prix du lot (€)').fill('30');
    await form.getByRole('group', { name: 'Types concernés', exact: true }).getByRole('button', { name: printType.name }).click();
    await form.getByRole('group', { name: 'Types concernés', exact: true }).getByRole('button', { name: stickerType.name }).click();
    await form.getByRole('button', { name: 'Créer la remise' }).click();
    await expect(page.getByTestId(`discount-rule-${name}`)).toContainText(`Types : ${printType.name}, ${stickerType.name}`);

    const preview = await request.post('/api/orders/preview', {
        data: {
            placedAt: `${event.startDate}T12:00`,
            lines: [foret, riviere, mousse].map((p) => ({ productId: p.id, variant: null, quantity: 1 })),
        },
    });
    const body = await preview.json();
    expect(body.discounts).toContainEqual({ label: name, amount: 400 });
    expect(body.total).toBe(3_000);
});
