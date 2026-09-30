import { computed, ref } from 'vue';
import { intlLocale } from '../i18n/locale.js';
import { useApi } from './useApi.js';

// Module-level: every component of the page shares the same list (a type created in a form shows up in filters).
const types = ref([]);

const byName = (a, b) => a.name.localeCompare(b.name, intlLocale());
const activeTypes = computed(() => types.value.filter((type) => !type.archived));

export function useProductTypes() {
    const api = useApi();

    async function load() {
        types.value = await api.get('/api/product-types');
    }

    async function create(name, color, code = null, variants = [], prefixesNames = true) {
        const type = await api.post('/api/product-types', { name, color, code, variants, prefixesNames });
        types.value = [...types.value, type].sort(byName);
        return type;
    }

    async function update(id, { name, color, code, variants, prefixesNames, archivedVariants }) {
        await api.put(`/api/product-types/${id}`, { name, color, code, variants, prefixesNames, archivedVariants });
        await load();
    }

    async function archive(id) {
        await api.put(`/api/product-types/${id}/archive`);
        await load();
    }

    async function restore(id) {
        await api.del(`/api/product-types/${id}/archive`);
        await load();
    }

    async function remove(id) {
        await api.del(`/api/product-types/${id}`);
        await load();
    }

    async function renameVariant(id, from, to) {
        await api.post(`/api/product-types/${id}/variant-renaming`, { from, to });
        await load();
    }

    const typeOf = (typeId) => types.value.find((type) => type.id === typeId);
    const allVariantsOf = (typeId) => typeOf(typeId)?.variants ?? [];
    const variantsOf = (typeId) => allVariantsOf(typeId).filter((variant) => !typeOf(typeId).archivedVariants.includes(variant));

    async function suggestCode(name) {
        if (name.trim() === '') return '';
        return (await api.peek(`/api/product-types/code-suggestion?${new URLSearchParams({ name })}`)).code;
    }

    return { types, activeTypes, load, create, update, archive, restore, remove, renameVariant, variantsOf, allVariantsOf, suggestCode };
}
