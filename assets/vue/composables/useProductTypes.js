import { computed, ref } from 'vue';
import { intlLocale } from '../i18n/locale.js';
import { useApi } from './useApi.js';

const types = ref([]);

const byName = (a, b) => a.name.localeCompare(b.name, intlLocale());
const activeTypes = computed(() => types.value.filter((type) => !type.archived));

export function useProductTypes() {
    const api = useApi();

    async function load() {
        await api.load('/api/product-types', types);
    }

    async function create(name, color, code = null, variants = [], prefixesNames = true) {
        const type = await api.post('/api/product-types', { name, color, code, variants, prefixesNames });
        types.value = [...types.value, type].sort(byName);
        return type;
    }

    const update = (id, { name, color, code, variants, prefixesNames, archivedVariants }) => api.put(`/api/product-types/${id}`, { name, color, code, variants, prefixesNames, archivedVariants }).then(load);
    const archive = (id) => api.put(`/api/product-types/${id}/archive`).then(load);
    const restore = (id) => api.del(`/api/product-types/${id}/archive`).then(load);
    const remove = (id) => api.del(`/api/product-types/${id}`).then(load);
    const renameVariant = (id, from, to) => api.post(`/api/product-types/${id}/variant-renaming`, { from, to }).then(load);

    const typeOf = (typeId) => types.value.find((type) => type.id === typeId);
    const allVariantsOf = (typeId) => typeOf(typeId)?.variants ?? [];
    const variantsOf = (typeId) => allVariantsOf(typeId).filter((variant) => !typeOf(typeId).archivedVariants.includes(variant));

    async function suggestCode(name) {
        if (name.trim() === '') return '';
        return (await api.peek(`/api/product-types/code-suggestion?${new URLSearchParams({ name })}`)).code;
    }

    return { types, activeTypes, load, create, update, archive, restore, remove, renameVariant, variantsOf, allVariantsOf, suggestCode };
}
