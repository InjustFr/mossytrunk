import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';
import { createEvent, createProduct } from './support/api.js';

test('products split per variant are gathered into one product, their sales with them', async ({ page, request }) => {
    const mug = unique('Mug');
    const lichen = await createProduct(request, { name: `${mug} Lichen`, sellingPrice: 1_200 });
    const fougere = await createProduct(request, { name: `${mug} Fougère`, sellingPrice: 1_200 });
    const event = await createEvent(request);
    const response = await request.post('/api/orders', {
        data: { placedAt: `${event.startDate}T11:00`, lines: [{ productId: lichen.id, variant: null, quantity: 2 }] },
    });
    expect(response.status()).toBe(201);
    const order = await response.json();

    await page.goto('/produits');
    const search = page.getByLabel('Rechercher un produit');
    await search.fill(lichen.name);
    await page.getByRole('button', { name: `Faire de ${lichen.name} une variante` }).click();
    let dialog = page.getByRole('dialog', { name: 'Faire une variante de ce produit' });
    await expect(dialog.getByLabel('Nom du nouveau produit')).toHaveValue(mug);
    await expect(dialog.getByLabel('Variante dans le produit de destination')).toHaveValue('Lichen');
    await dialog.getByRole('button', { name: 'Déplacer' }).click();
    await expect(page.getByTestId('toast')).toContainText(`Déplacé vers « ${mug} — Lichen ».`);

    await search.fill(fougere.name);
    await page.getByRole('button', { name: `Faire de ${fougere.name} une variante` }).click();
    dialog = page.getByRole('dialog', { name: 'Faire une variante de ce produit' });
    await dialog.getByRole('group', { name: 'Destination' }).getByRole('button', { name: 'Produit existant' }).click();
    await choose(page, dialog.getByRole('combobox', { name: 'Produit de destination' }), mug);
    await expect(dialog.getByLabel('Variante dans le produit de destination')).toHaveValue('Fougère');
    await dialog.getByRole('button', { name: 'Déplacer' }).click();
    await expect(page.getByTestId('toast').last()).toContainText(`Déplacé vers « ${mug} — Fougère ».`);

    await search.fill(mug);
    const rows = page.getByRole('row').filter({ hasText: mug });
    await expect(rows).toHaveCount(1);
    await expect(rows).toContainText('Lichen, Fougère');

    await page.goto(`/commandes/${order.id}`);
    await expect(page.getByRole('cell', { name: `${mug} — Lichen` })).toBeVisible();
});
