import { computed, ref } from 'vue';
import { useApi } from './useApi.js';

export const SUPPLIER_ORDER_STATUSES = {
    ordered: { label: 'Commandée', tone: 'warning' },
    received: { label: 'Reçue', tone: 'success' },
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
