import { test, expect } from './support/test.js';
import { createEvent, createProduct } from './support/api.js';
import { unique } from './support/unique.js';

test('choose the format of order references from the settings, with tags and a live example', async ({ page, request }) => {
    const product = await createProduct(request);
    const event = await createEvent(request);
    const placeOrder = () => request.post('/api/orders', { data: { placedAt: `${event.startDate}T11:00`, lines: [{ productId: product.id, variant: null, quantity: 1 }] } });
    expect((await placeOrder()).status()).toBe(201);

    await page.goto('/settings');
    const references = page.getByRole('region', { name: 'Références' });
    await expect(references).toContainText('CMD-{date}-{random}');

    await references.getByRole('button', { name: 'Modifier le format des commandes', exact: true }).click();
    const dialog = page.getByRole('dialog', { name: 'Modifier le format des commandes' });
    const template = dialog.getByLabel('Format');
    await template.fill('VENTE {number}');
    await expect(dialog.getByRole('alert')).toContainText('n\'est pas permis dans une référence');

    await template.fill('V-');
    await dialog.getByRole('button', { name: 'Insérer Année' }).click();
    await template.press('End');
    await template.pressSequentially('-');
    await dialog.getByRole('button', { name: 'Insérer Numéro croissant' }).click();
    await expect(template).toHaveValue('V-{year}-{number}');
    await expect(dialog).toContainText(new RegExp(`Exemple V-${new Date().getFullYear()}-\\d+`));
    await dialog.getByRole('radio', { name: /Prochaines créations seulement/ }).click();
    await dialog.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByTestId('toast')).toContainText('Format des commandes enregistré.');
    await expect(references).toContainText('V-{year}-{number}');

    const placed = await placeOrder();
    expect(placed.status()).toBe(201);
    expect((await placed.json()).reference).toMatch(new RegExp(`^V-${event.startDate.slice(0, 4)}-\\d+$`));
});

test('renumber the existing supplier orders, oldest first, after a confirmation', async ({ page, request }) => {
    const product = await createProduct(request);
    const supplier = await request.post('/api/suppliers', { data: { name: unique('Fournisseur'), contact: '' } });
    expect(supplier.status()).toBe(201);
    const order = await request.post('/api/supplier-orders', {
        data: { supplierId: (await supplier.json()).id, orderedOn: '2040-01-02', lines: [{ productId: product.id, variant: null, quantity: 5, totalPrice: 1_000 }] },
    });
    expect(order.status()).toBe(201);

    await page.goto('/settings');
    await page.getByRole('button', { name: 'Modifier le format des commandes fournisseur' }).click();
    const dialog = page.getByRole('dialog', { name: 'Modifier le format des commandes fournisseur' });
    await dialog.getByLabel('Format').fill('ACH-{number:5}');
    await expect(dialog).toContainText(/Exemple ACH-\d{5}/);
    await dialog.getByRole('radio', { name: /Renuméroter aussi l'existant/ }).click();
    await dialog.getByRole('button', { name: 'Enregistrer' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Renuméroter' }).click();

    await expect(page.getByTestId('toast')).toContainText(/Format des commandes fournisseur enregistré : \d+ références? renumérotées?\./);
    await page.goto(`/supplier-orders/${(await order.json()).id}`);
    await expect(page.getByRole('heading', { level: 1, name: /^ACH-\d{5}$/ })).toBeVisible();
});
