import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useDiscountRules() {
    const api = useApi();
    const rules = ref([]);

    async function load() {
        rules.value = await api.get('/api/discount-rules');
    }

    const create = (payload) => api.post('/api/discount-rules', payload);
    const update = (id, payload) => api.put(`/api/discount-rules/${id}`, payload);
    const setActive = (id, active) => (active
        ? api.put(`/api/discount-rules/${id}/activation`)
        : api.del(`/api/discount-rules/${id}/activation`));
    const remove = (id) => api.del(`/api/discount-rules/${id}`);

    return { rules, load, create, update, setActive, remove };
}
