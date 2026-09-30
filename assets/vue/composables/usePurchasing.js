import { computed, ref } from 'vue';
import { useApi } from './useApi.js';

export const SUPPLIER_ORDER_STATUSES = {
    ordered: { label: 'purchasing.status.ordered', tone: 'warning' },
    received: { label: 'purchasing.status.received', tone: 'success' },
};

export function useSuppliers() {
    const api = useApi();
    const suppliers = ref([]);

    async function load() {
        suppliers.value = await api.get('/api/suppliers');
    }

    const save = (id, payload) => (id ? api.put(`/api/suppliers/${id}`, payload) : api.post('/api/suppliers', payload));
    const options = computed(() => suppliers.value.map((supplier) => ({ value: supplier.id, label: supplier.name })));

    return { suppliers, options, load, save };
}

export function useSupplierOrders() {
    const api = useApi();
    const orders = ref([]);

    async function load() {
        orders.value = await api.get('/api/supplier-orders');
    }

    return {
        orders,
        load,
        get: (id) => api.get(`/api/supplier-orders/${id}`),
        create: (payload) => api.post('/api/supplier-orders', payload),
        update: (id, payload) => api.put(`/api/supplier-orders/${id}`, payload),
        remove: (id) => api.del(`/api/supplier-orders/${id}`),
        receive: (id, lines) => api.post(`/api/supplier-orders/${id}/reception`, { lines }),
    };
}

function equally(amount, parts) {
    const base = Math.floor(amount / parts);
    const left = amount - base * parts;
    return Array.from({ length: parts }, (_, index) => base + (index < left ? 1 : 0));
}

function proportionally(amount, weights) {
    const total = weights.reduce((sum, weight) => sum + weight, 0);
    if (total === 0) return equally(amount, weights.length);
    const shares = weights.map((weight) => Math.floor((amount * weight) / total));
    const order = weights.map((weight, index) => ({ index, remainder: (amount * weight) % total })).sort((a, b) => b.remainder - a.remainder);
    let left = amount - shares.reduce((sum, share) => sum + share, 0);
    for (const { index } of order) {
        if (left <= 0) break;
        shares[index] += 1;
        left -= 1;
    }
    return shares;
}

export function landedCosts(lines, discount, deliveryFees) {
    if (lines.length === 0) return [];
    const prices = lines.map((line) => line.totalPrice ?? 0);
    const discounts = proportionally(discount ?? 0, prices);
    const fees = equally(deliveryFees ?? 0, lines.length);
    return prices.map((price, index) => price - discounts[index] + fees[index]);
}
