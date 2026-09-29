import { expect } from '@playwright/test';
import { unique, uniqueDay } from './unique.js';

/** Arrange helpers: create data through the JSON API to keep UI tests focused. */

export async function createType(request, name = unique('Type')) {
    const response = await request.post('/api/product-types', { data: { name } });
    expect(response.status()).toBe(201);
    return response.json();
}

export async function createProduct(request, { name = unique('Produit'), sellingPrice = 400, buyingPrice = 0, variants = [], type = null } = {}) {
    const response = await request.post('/api/products', { data: { name, sellingPrice, variants, typeId: type?.id ?? null } });
    expect(response.status()).toBe(201);
    const id = (await response.json()).id;
    if (buyingPrice > 0) {
        for (const variant of variants.length ? variants : [null]) {
            await restock(request, { id }, { variant, quantity: 100, totalPaid: buyingPrice * 100 });
        }
    }
    const displayName = type ? `${type.name} ${name}` : name;
    return { id, name, displayName, sellingPrice, buyingPrice, variants };
}

export async function createEvent(request, { name = unique('Convention'), startDate = uniqueDay(), endDate = null } = {}) {
    const response = await request.post('/api/events', {
        data: { name, location: 'Lyon', startDate, endDate: endDate ?? startDate },
    });
    expect(response.status()).toBe(201);
    return { id: (await response.json()).id, name, startDate, endDate: endDate ?? startDate };
}

export async function createDiscountRule(request, { name = unique('Remise'), conditions, action }) {
    const response = await request.post('/api/discount-rules', { data: { name, conditions, action } });
    expect(response.status()).toBe(201);
    return { id: (await response.json()).id, name };
}

export async function configureSumUp(request, { merchantCode = 'MCODE', apiKey = 'sup_sk_e2e_key' } = {}) {
    const response = await request.put('/api/workspace/settings/sumup', { data: { merchantCode, apiKey } });
    expect(response.status()).toBe(204);
}

export async function restock(request, product, { variant = null, quantity, totalPaid }) {
    const response = await request.post('/api/stock/restock', { data: { productId: product.id, variant, quantity, totalPaid } });
    expect(response.status()).toBe(204);
}
