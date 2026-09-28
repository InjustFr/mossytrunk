import { test, expect } from '@playwright/test';
import { unique, uniqueDay } from './support/unique.js';

test('schedule an event and track its expenses', async ({ page }) => {
    const name = unique('Japan Expo');
    const start = uniqueDay();
    const end = uniqueDay(2);

    await page.goto('/evenements');
    const form = page.locator('form.event-form');
    await form.getByLabel('Nom').fill(name);
    await form.getByLabel('Lieu').fill('Villepinte');
    await form.getByLabel('Début').fill(start);
    await form.getByLabel('Fin').fill(end);
    await form.getByRole('button', { name: "Créer l'événement" }).click();

    await expect(page.getByTestId('toast')).toContainText(`Événement « ${name} » créé.`);
    await page.getByRole('link', { name }).click();

    await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();
    const expenses = page.locator('form.expense-list__form');
    await expenses.getByLabel('Libellé').fill('Stand');
    await expenses.getByLabel('Montant (€)').fill('300');
    await expenses.getByRole('button', { name: 'Ajouter la dépense' }).click();
    await expenses.getByLabel('Libellé').fill('Train');
    await expenses.getByLabel('Montant (€)').fill('89,50');
    await expenses.getByRole('button', { name: 'Ajouter la dépense' }).click();

    await expect(page.getByRole('row', { name: /Total/ })).toContainText('389,50');

    await page.getByRole('button', { name: 'Supprimer Train' }).click();
    await expect(page.getByRole('row', { name: /Total/ })).toContainText('300,00');
});

test('refuses overlapping events', async ({ page }) => {
    const day = uniqueDay();
    await page.goto('/evenements');
    const form = page.locator('form.event-form');

    for (const name of [unique('Premier'), unique('Second')]) {
        await form.getByLabel('Nom').fill(name);
        await form.getByLabel('Lieu').fill('Lyon');
        await form.getByLabel('Début').fill(day);
        await form.getByLabel('Fin').fill(day);
        await form.getByRole('button', { name: "Créer l'événement" }).click();
    }

    await expect(form.getByRole('alert')).toContainText('chevauchent');
});

test('event report details expenses, orders, URSSAF and the result', async ({ page, request }) => {
    const { createEvent, createProduct } = await import('./support/api.js');
    const event = await createEvent(request);
    const print = await createProduct(request, { name: unique('Print'), sellingPrice: 1_500, buyingPrice: 500 });
    const mystery = await createProduct(request, { name: unique('Mystère'), sellingPrice: 1_000, buyingPrice: 0 });
    await request.post(`/api/events/${event.id}/expenses`, { data: { label: 'Stand', amount: 10_000 } });
    for (const lines of [
        [{ productId: print.id, variant: null, quantity: 10 }],
        [{ productId: mystery.id, variant: null, quantity: 1 }],
    ]) {
        const response = await request.post('/api/orders', { data: { placedAt: `${event.startDate}T11:00`, lines } });
        expect(response.status()).toBe(201);
    }

    await page.goto(`/evenements/${event.id}`);
    const report = page.locator('.event-report');
    const sections = report.locator('summary');
    await expect(sections).toHaveText([/Dépenses\s*−\s*100,00/, /Commandes\s*\+\s*160,00/, /URSSAF\s*−\s*20,48/]);

    // CA 160 − coût 50 − dépenses 100 − URSSAF 20,48 = −10,48
    await expect(report.getByTestId('event-result')).toHaveText(/−10,48|-10,48/);
    await expect(report.getByRole('row', { name: new RegExp(mystery.name) })).toContainText('⚠︎');

    await report.getByRole('link', { name: 'Voir les commandes' }).click();
    await expect(page.getByLabel('Événement')).toHaveValue(event.id);
    await expect(page.getByRole('row')).toHaveCount(3); // header + 2 orders
});
