import { ref } from 'vue';
import { useApi } from './useApi.js';

// Module-level: every component of the page shares the same list (a type created in a form shows up in filters).
const types = ref([]);

export function useProductTypes() {
    const api = useApi();

    async function load() {
        types.value = await api.get('/api/product-types');
    }

    async function create(name) {
        const type = await api.post('/api/product-types', { name });
        types.value = [...types.value, type].sort((a, b) => a.name.localeCompare(b.name, 'fr'));
        return type;
    }

    const rename = (id, name) => api.put(`/api/product-types/${id}`, { name });

    return { types, load, create, rename };
}
