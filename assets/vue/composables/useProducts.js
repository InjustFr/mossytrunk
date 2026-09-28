import { ref } from 'vue';
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

    return { products, loading, load, create, update };
}
