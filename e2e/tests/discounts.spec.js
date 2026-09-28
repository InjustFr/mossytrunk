import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';
import { createProduct } from './support/api.js';

test('create, deactivate and delete a bundle discount', async ({ page, request }) => {
    const sticker = await createProduct(request, { name: unique('Sticker'), sellingPrice: 400 });
    const name = unique('3 stickers pour 10 €');

    await page.goto('/remises');
    const form = page.locator('form.discount-rule-form');
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
