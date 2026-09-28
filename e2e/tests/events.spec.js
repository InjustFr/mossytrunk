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
