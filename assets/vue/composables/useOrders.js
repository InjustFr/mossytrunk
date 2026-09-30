import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useOrders() {
    const api = useApi();
    const orders = ref([]);
    // Pre-selected by links such as /orders?event=<id> from an event report.
    const eventFilter = ref(new URLSearchParams(window.location.search).get('event') ?? '');

    async function load() {
        const query = eventFilter.value ? `?eventId=${encodeURIComponent(eventFilter.value)}` : '';
        orders.value = await api.get(`/api/orders${query}`);
    }

    const place = (payload) => api.post('/api/orders', payload);
    const removeAll = () => api.del('/api/orders');

    return { orders, eventFilter, load, place, removeAll };
}

export function useOrder(orderId) {
    const api = useApi();
    const order = ref(null);

    async function load() {
        order.value = await api.get(`/api/orders/${orderId}`);
    }

    const remove = () => api.del(`/api/orders/${orderId}`);
    const identifyLine = (lineId, payload) => api.put(`/api/orders/${orderId}/lines/${lineId}/product`, payload);
    const mergeWith = (otherOrderId) => api.post(`/api/orders/${orderId}/merge`, { orderId: otherOrderId });
    const candidates = async () => {
        const query = order.value.event ? `?eventId=${encodeURIComponent(order.value.event.id)}` : '';
        const orders = await api.get(`/api/orders${query}`);
        return orders.filter((other) => other.id !== orderId && other.source === order.value.source && (other.eventId ?? null) === (order.value.event?.id ?? null));
    };

    return { order, load, remove, identifyLine, mergeWith, candidates };
}
