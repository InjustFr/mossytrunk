import { ref } from 'vue';
import { useApi } from './useApi.js';

// Module-level: every component of the page shares the same list (a type created in a form shows up in filters).
const types = ref([]);

const byName = (a, b) => a.name.localeCompare(b.name, 'fr');

export function useProductTypes() {
    const api = useApi();

    async function load() {
        types.value = await api.get('/api/product-types');
    }

    async function create(name, color) {
        const type = await api.post('/api/product-types', { name, color });
        types.value = [...types.value, type].sort(byName);
        return type;
    }

    async function update(id, { name, color }) {
        await api.put(`/api/product-types/${id}`, { name, color });
        types.value = types.value.map((type) => (type.id === id ? { ...type, name: name.trim(), color } : type)).sort(byName);
    }

    return { types, load, create, update };
}
