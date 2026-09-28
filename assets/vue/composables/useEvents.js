import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useEvents() {
    const api = useApi();
    const events = ref([]);

    async function load() {
        events.value = await api.get('/api/events');
    }

    const create = (payload) => api.post('/api/events', payload);

    return { events, load, create };
}

export function useEvent(eventId) {
    const api = useApi();
    const event = ref(null);

    async function load() {
        event.value = await api.get(`/api/events/${eventId}`);
    }

    const update = (payload) => api.put(`/api/events/${eventId}`, payload);
    const addExpense = (payload) => api.post(`/api/events/${eventId}/expenses`, payload);
    const removeExpense = (expenseId) => api.del(`/api/events/${eventId}/expenses/${expenseId}`);

    return { event, load, update, addExpense, removeExpense };
}
