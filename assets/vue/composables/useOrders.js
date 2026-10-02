import { ref } from 'vue';
import { useApi } from './useApi.js';
import { queryText } from './useQueryState.js';

export function useOrders() {
    const api = useApi();
    const orders = ref([]);
    const eventFilter = queryText('event');

    async function load() {
        const query = eventFilter.value ? `?eventId=${encodeURIComponent(eventFilter.value)}` : '';
        await api.load(`/api/orders${query}`, orders);
    }

    const place = (payload) => api.post('/api/orders', payload);
    const removeSelected = (orderIds) => api.post('/api/orders/deletion', { orderIds });
    const addSupplies = (orderIds, payload) => api.post('/api/orders/supplies', { orderIds, ...payload });

    return { orders, eventFilter, load, place, removeSelected, addSupplies };
}

export function useOrder(orderId) {
    const api = useApi();
    const order = ref(null);

    async function load() {
        await api.load(`/api/orders/${orderId}`, order);
    }

    const remove = () => api.del(`/api/orders/${orderId}`);
    const refund = () => api.post(`/api/orders/${orderId}/refund`);
    const identifyLine = (lineId, payload) => api.put(`/api/orders/${orderId}/lines/${lineId}/product`, payload);
    const mergeWith = (otherOrderId) => api.post(`/api/orders/${orderId}/merge`, { orderId: otherOrderId });
    const candidates = () => api.get(`/api/orders/${orderId}/merge-candidates`);
    const addSupply = (payload) => api.post('/api/orders/supplies', { orderIds: [orderId], ...payload });
    const removeSupply = (supplyLineId) => api.del(`/api/orders/${orderId}/supplies/${supplyLineId}`);

    return { order, load, remove, refund, identifyLine, mergeWith, candidates, addSupply, removeSupply };
}
