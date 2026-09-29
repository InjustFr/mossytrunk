import { test, expect } from '@playwright/test';
import { choose } from './support/select.js';
import { unique } from './support/unique.js';
import { createProduct, createType, restock } from './support/api.js';

test('the product page shows stock, movements and selling price history', async ({ page, request }) => {
    const product = await createProduct(request, { name: unique('Sticker'), sellingPrice: 400 });
    await restock(request, product, { quantity: 20, totalPaid: 1_000 });

    await page.goto('/produits');
    await page.getByLabel('Rechercher un produit').fill(product.name);
    await page.getByRole('link', { name: product.name }).click();

    await expect(page.getByRole('heading', { level: 1, name: product.name })).toBeVisible();
    const movements = page.getByRole('region', { name: 'Mouvements' });
    await expect(movements).toContainText('Achat');
    await expect(movements).toContainText('+20');

    await page.getByRole('button', { name: 'Modifier' }).click();
    await page.getByRole('dialog', { name: 'Modifier le produit' }).getByLabel('Prix de vente (€)').fill('5');
    await page.getByRole('dialog', { name: 'Modifier le produit' }).getByRole('button', { name: 'Enregistrer' }).click();

    const prices = page.getByRole('region', { name: 'Prix de vente' });
    await expect(prices.getByRole('listitem')).toHaveCount(2);
    await expect(prices.getByRole('listitem').first()).toContainText('5,00');
    await expect(prices.getByRole('listitem').first()).toContainText('+1,00');
});

test('an existing product gets its design, then a new declination becomes a new product', async ({ page, request }) => {
    const glossy = unique('Brillant');
    const card = unique('Carte');
    const cardType = await createType(request, unique('Carte'));
    for (const [name, typeId, adaptations] of [[glossy, null, []], [card, cardType.id, ['Marges 5 mm']]]) {
        expect((await request.post('/api/gabarits', { data: { name, typeId, sellingPrice: 300, adaptations } })).status()).toBe(201);
    }
    const product = await createProduct(request, { name: unique('Héron'), sellingPrice: 450 });

    await page.goto(`/produits/${product.id}`);
    const design = page.getByRole('region', { name: 'Design' });
    await design.getByRole('button', { name: 'Créer son design' }).click();
    await choose(page, design.getByRole('combobox', { name: 'Gabarit' }), glossy);
    await design.getByRole('button', { name: 'Créer le design' }).click();

    await expect(page.getByRole('heading', { level: 1, name: product.name })).toBeVisible();
    await expect(page.getByRole('article', { name: glossy }).getByRole('link', { name: 'Voir le produit' })).toBeVisible();

    await page.getByRole('button', { name: card }).click();
    const declination = page.getByRole('article', { name: card });
    await declination.getByRole('checkbox').click();
    await expect(declination).toContainText('Prête');
    await page.getByRole('button', { name: 'Créer les nouveaux produits' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Créer les produits' }).click();

    await expect(page.getByTestId('toast').last()).toContainText('1 produit créé');
    await declination.getByRole('link', { name: 'Voir le produit' }).click();
    await expect(page.getByRole('region', { name: 'Design' })).toContainText(product.name);
});
