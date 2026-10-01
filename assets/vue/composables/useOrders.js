import { ref } from 'vue';
import { useApi } from './useApi.js';
import { queryText } from './useQueryState.js';

export function useOrders() {
    const api = useApi();
    const orders = ref([]);
    const eventFilter = queryText('event');

    async function load() {
        const query = eventFilter.value ? `?eventId=${encodeURIComponent(eventFilter.value)}` : '';
        orders.value = await api.get(`/api/orders${query}`);
    }

    const place = (payload) => api.post('/api/orders', payload);
    const removeAll = () => api.del('/api/orders');
    const removeSelected = (orderIds) => api.post('/api/orders/deletion', { orderIds });

    return { orders, eventFilter, load, place, removeAll, removeSelected };
}

export function useOrder(orderId) {
    const api = useApi();
    const order = ref(null);

    async function load() {
        order.value = await api.get(`/api/orders/${orderId}`);
    }

    const remove = () => api.del(`/api/orders/${orderId}`);
    const refund = () => api.post(`/api/orders/${orderId}/refund`);
    const identifyLine = (lineId, payload) => api.put(`/api/orders/${orderId}/lines/${lineId}/product`, payload);
    const mergeWith = (otherOrderId) => api.post(`/api/orders/${orderId}/merge`, { orderId: otherOrderId });
    const candidates = () => api.get(`/api/orders/${orderId}/merge-candidates`);

    return { order, load, remove, refund, identifyLine, mergeWith, candidates };
}
