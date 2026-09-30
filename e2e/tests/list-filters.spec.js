import { test, expect } from '@playwright/test';
import { unique } from './support/unique.js';
import { createProduct } from './support/api.js';

test('product filters survive a visit to a product page, by its back link or the browser', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Sticker') });
    const search = page.getByLabel('Rechercher un produit');
    const missingCost = page.getByRole('button', { name: /sans coût d'achat/ });

    await page.goto('/products');
    await search.fill(product.name);
    await missingCost.click();
    await expect(page).toHaveURL(/purchase-price=missing/);

    await page.getByRole('link', { name: product.name }).click();
    await expect(page.getByRole('heading', { level: 1, name: product.name })).toBeVisible();
    await page.locator('.back-link').click();

    await expect(search).toHaveValue(product.name);
    await expect(missingCost).toHaveAttribute('data-state', 'on');
    await page.getByRole('link', { name: product.name }).click();
    await expect(page.getByRole('heading', { level: 1, name: product.name })).toBeVisible();

    await page.goBack();
    await expect(search).toHaveValue(product.name);
    await expect(missingCost).toHaveAttribute('data-state', 'on');
});
