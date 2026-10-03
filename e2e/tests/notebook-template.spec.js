import { test, expect } from '@playwright/test';

test.afterEach(async ({ request }) => {
    await request.put('/api/notebook-template', { data: { separation: 'numbered', abbreviations: [] } });
});

test('the notebook template sets how sales are separated and which abbreviations are used', async ({ page }) => {
    await page.goto('/settings');
    const card = page.getByRole('region', { name: 'Carnet de ventes' });
    await expect(card.getByRole('radio', { name: /Chaque vente commence par son numéro/ })).toBeChecked();

    await card.getByRole('radio', { name: /Une vente par ligne/ }).click();
    await card.getByRole('button', { name: 'Ajouter une abréviation' }).click();
    await card.getByRole('textbox', { name: 'Abréviation 1', exact: true }).fill('stk');
    await card.getByRole('textbox', { name: "Signification de l'abréviation 1" }).fill('sticker');
    await card.getByRole('button', { name: 'Enregistrer le modèle' }).click();
    await expect(page.getByTestId('toast')).toContainText('Modèle de carnet enregistré.');

    await page.reload();
    await expect(card.getByRole('radio', { name: /Une vente par ligne/ })).toBeChecked();
    await expect(card.getByRole('textbox', { name: 'Abréviation 1', exact: true })).toHaveValue('stk');

    await card.getByRole('button', { name: 'Ajouter une abréviation' }).click();
    await card.getByRole('textbox', { name: 'Abréviation 2', exact: true }).fill('STK');
    await card.getByRole('textbox', { name: "Signification de l'abréviation 2" }).fill('stickers');
    await card.getByRole('button', { name: 'Enregistrer le modèle' }).click();
    await expect(card.getByRole('alert')).toContainText("L'abréviation « STK » est déjà définie.");
});
