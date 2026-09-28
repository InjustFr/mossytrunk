import { expect } from '@playwright/test';
import { unique, uniqueDay } from './unique.js';

/** Arrange helpers: create data through the JSON API to keep UI tests focused. */

export async function createType(request, name = unique('Type')) {
    const response = await request.post('/api/product-types', { data: { name } });
    expect(response.status()).toBe(201);
    return response.json();
}

export async function createProduct(request, { name = unique('Produit'), sellingPrice = 400, buyingPrice = 0, variants = [], type = null } = {}) {
    const response = await request.post('/api/products', { data: { name, sellingPrice, buyingPrice, variants, typeId: type?.id ?? null } });
    expect(response.status()).toBe(201);
    const displayName = type ? `${type.name} ${name}` : name;
    return { id: (await response.json()).id, name, displayName, sellingPrice, buyingPrice, variants };
}

export async function createEvent(request, { name = unique('Convention'), startDate = uniqueDay(), endDate = null } = {}) {
    const response = await request.post('/api/events', {
        data: { name, location: 'Lyon', startDate, endDate: endDate ?? startDate },
    });
    expect(response.status()).toBe(201);
    return { id: (await response.json()).id, name, startDate, endDate: endDate ?? startDate };
}

export async function createDiscountRule(request, { name = unique('Lot'), productIds, bundleSize, bundlePrice }) {
    const response = await request.post('/api/discount-rules', { data: { name, productIds, bundleSize, bundlePrice } });
    expect(response.status()).toBe(201);
    return { id: (await response.json()).id, name };
}
