import { expect } from '@playwright/test';
import { unique, uniqueDay } from './unique.js';

/** Arrange helpers: create data through the JSON API to keep UI tests focused. */

export async function createProduct(request, { name = unique('Produit'), sellingPrice = 400, buyingPrice = 0, variants = [] } = {}) {
    const reference = unique('REF').replace(' ', '-');
    const response = await request.post('/api/products', { data: { reference, name, sellingPrice, buyingPrice, variants } });
    expect(response.status()).toBe(201);
    return { id: (await response.json()).id, name, reference, sellingPrice, buyingPrice, variants };
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
