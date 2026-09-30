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

    async function create(name, color, code = null) {
        const type = await api.post('/api/product-types', { name, color, code });
        types.value = [...types.value, type].sort(byName);
        return type;
    }

    async function update(id, { name, color, code }) {
        await api.put(`/api/product-types/${id}`, { name, color, code });
        types.value = types.value.map((type) => (type.id === id ? { ...type, name: name.trim(), color, code: code.trim().toUpperCase() } : type)).sort(byName);
    }

    async function suggestCode(name) {
        if (name.trim() === '') return '';
        return (await api.peek(`/api/product-types/code-suggestion?${new URLSearchParams({ name })}`)).code;
    }

    return { types, load, create, update, suggestCode };
}
