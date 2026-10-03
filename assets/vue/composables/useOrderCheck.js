import { useApi } from './useApi.js';

export function useOrderCheck(eventId) {
    const api = useApi();

    return {
        load: (target) => api.load(`/api/events/${eventId}/orders-to-check`, target),
        check: (orderId) => api.put(`/api/orders/${orderId}/check`),
        uncheck: (orderId) => api.del(`/api/orders/${orderId}/check`),
    };
}
