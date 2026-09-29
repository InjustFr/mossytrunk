import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useOrders() {
    const api = useApi();
    const orders = ref([]);
    // Pre-selected by links such as /commandes?event=<id> from an event report.
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

    return { order, load, remove };
}
