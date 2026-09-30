import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { fillDate } from './support/date.js';
import { unique } from './support/unique.js';
import { createEvent, createProduct, createType } from './support/api.js';

test('create "2 prints and 1 sticker for 15 €", start it today and delete it', async ({ page, request }) => {
    const printType = await createType(request, unique('Print'));
    const stickerType = await createType(request, unique('Sticker'));
    const foret = await createProduct(request, { name: 'Forêt', sellingPrice: 1_500, type: printType });
    const riviere = await createProduct(request, { name: 'Rivière', sellingPrice: 1_500, type: printType });
    const mousse = await createProduct(request, { name: 'Mousse', sellingPrice: 400, type: stickerType });
    const event = await createEvent(request);
    const name = unique('2 prints et 1 sticker pour 15 €');

    await page.goto('/discounts');
    await page.getByRole('button', { name: 'Nouvelle remise' }).click();
    const form = page.getByRole('dialog', { name: 'Nouvelle remise' }).locator('form');
    await form.getByLabel('Nom').fill(name);
    await form.getByLabel('Quantité de la condition 1').fill('2');
    await choose(page, form.getByRole('combobox', { name: 'Type de la condition 1' }), printType.name);
    await form.getByRole('button', { name: 'Ajouter une condition' }).click();
    await choose(page, form.getByRole('combobox', { name: 'Type de la condition 2' }), stickerType.name);
    await form.getByRole('spinbutton', { name: 'Prix', exact: true }).fill('15');
    await form.getByRole('button', { name: /^Période/ }).click();
    await fillDate(form.getByRole('group', { name: /^Début/ }), event.startDate);
    await fillDate(form.getByRole('group', { name: /^Fin/ }), event.startDate);
    await expect(form.getByTestId('discount-rule-pricing')).toContainText('34,00');
    await expect(form.getByTestId('discount-rule-pricing')).toContainText('15,00');
    await form.getByRole('button', { name: 'Créer la remise' }).click();

    await expect(page.getByTestId('toast')).toContainText(`Remise « ${name} » créée.`);
    const item = page.getByTestId(`discount-rule-${name}`);
    await expect(item).toContainText(`2 × ${printType.name} + 1 × ${stickerType.name} pour 15,00`);
    await expect(item).toContainText('19,00');

    const preview = async (placedAt) => (await request.post('/api/orders/preview', {
        data: { placedAt, lines: [foret, riviere, mousse].map((p) => ({ productId: p.id, variant: null, quantity: 1 })) },
    })).json();
    const during = await preview(`${event.startDate}T12:00`);
    expect(during.discounts).toContainEqual({ label: name, amount: 1_900, ruleId: expect.any(String) });
    expect(during.total).toBe(1_500);

    await expect(item).toContainText('À venir');
    await item.getByRole('switch').check();
    await expect(item).toContainText('En cours');
    expect((await preview(`${event.startDate}T12:00`)).total).toBe(1_500);

    await item.getByRole('switch').uncheck();
    await expect(page.getByTestId('toast').last()).toContainText('commence aujourd\'hui');
    await expect(item).toContainText('En cours');

    await item.getByRole('button', { name: 'Supprimer' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Confirmer ?' }).click();
    await expect(item).toHaveCount(0);
});

test('a product variant condition and an amount off, edited afterwards', async ({ page, request }) => {
    const tshirt = await createProduct(request, { name: unique('T-shirt'), sellingPrice: 2_500, variants: ['S', 'M'] });
    const name = unique('T-shirt −5 €');

    await page.goto('/discounts');
    await page.getByRole('button', { name: 'Nouvelle remise' }).click();
    const form = page.getByRole('dialog', { name: 'Nouvelle remise' }).locator('form');
    await form.getByLabel('Nom').fill(name);
    await form.getByRole('group', { name: 'Cible de la condition 1' }).getByRole('button', { name: 'Produit' }).click();
    await choose(page, form.getByRole('combobox', { name: 'Produit de la condition 1' }), tshirt.displayName);
    await choose(page, form.getByRole('combobox', { name: 'Variante de la condition 1' }), 'M');
    await form.getByRole('group', { name: 'Type d\'action' }).getByRole('button', { name: 'Remise en €' }).click();
    await form.getByRole('spinbutton', { name: 'Montant', exact: true }).fill('5');
    await form.getByRole('button', { name: 'Créer la remise' }).click();

    const item = page.getByTestId(`discount-rule-${name}`);
    await expect(item).toContainText(`1 × ${tshirt.displayName} · M −5,00`);

    await item.getByRole('button', { name: `Modifier ${name}` }).click();
    const edit = page.getByRole('dialog', { name: 'Modifier la remise' }).locator('form');
    await expect(edit.getByRole('combobox', { name: 'Variante de la condition 1' })).toContainText('M');
    await edit.getByRole('group', { name: 'Type d\'action' }).getByRole('button', { name: 'Remise en %' }).click();
    await edit.getByRole('spinbutton', { name: 'Pourcentage' }).fill('20');
    await edit.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(item).toContainText(`1 × ${tshirt.displayName} · M −20 %`);
    await expect(item).toContainText('5,00');
});

test('stopping a running discount ends it yesterday; an expired discount has no switch', async ({ page, request }) => {
    const sticker = await createProduct(request, { name: unique('Sticker'), sellingPrice: 400 });
    const name = unique('Sticker −1 €');
    const response = await request.post('/api/discount-rules', {
        data: { name, conditions: [{ kind: 'product', id: sticker.id, quantity: 1 }], action: { kind: 'amountOff', value: 100 }, startsOn: '2026-01-01', endsOn: null },
    });
    expect(response.status()).toBe(201);

    await page.goto('/discounts');
    const item = page.getByTestId(`discount-rule-${name}`);
    await expect(item).toContainText('En cours');
    await item.getByRole('switch').uncheck();

    await expect(item).toContainText('Expirée');
    await expect(item.getByRole('switch')).toHaveCount(0);
});
