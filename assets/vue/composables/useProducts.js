import { computed, ref } from 'vue';
import { useApi } from './useApi.js';

export function useProducts() {
    const api = useApi();
    const products = ref([]);
    const loading = ref(false);

    async function load() {
        loading.value = true;
        try {
            products.value = await api.get('/api/products');
        } finally {
            loading.value = false;
        }
    }

    const create = (payload) => api.post('/api/products', payload);
    const update = (id, payload) => api.put(`/api/products/${id}`, payload);
    const batchUpdate = (payload) => api.post('/api/products/batch', payload);
    const moveVariant = (id, payload) => api.post(`/api/products/${id}/move-variant`, payload);
    const remove = (id) => api.del(`/api/products/${id}`);
    const archive = (id) => api.put(`/api/products/${id}/archive`);
    const restore = (id) => api.del(`/api/products/${id}/archive`);
    const removeAll = () => api.del('/api/products');
    const get = (id) => api.get(`/api/products/${id}`);
    const suggestReference = async (name, typeId) => {
        if (name.trim() === '') return '';
        const query = new URLSearchParams({ name, typeId: typeId ?? '' });
        return (await api.peek(`/api/products/reference-suggestion?${query}`)).reference;
    };
    const designProduct = (id, payload) => api.post(`/api/products/${id}/design`, payload);
    const savePrice = (id, changeId, payload) => (changeId ? api.put(`/api/products/${id}/prices/${changeId}`, payload) : api.post(`/api/products/${id}/prices`, payload));
    const forgetPrice = (id, changeId) => api.del(`/api/products/${id}/prices/${changeId}`);

    const activeProducts = computed(() => products.value.filter((product) => !product.archived));

    return { products, activeProducts, loading, load, create, update, batchUpdate, moveVariant, remove, archive, restore, removeAll, get, suggestReference, designProduct, savePrice, forgetPrice };
}
