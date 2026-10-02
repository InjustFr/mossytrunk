import { ref } from 'vue';
import { useApi } from './useApi.js';

const CHANNELS_URL = '/api/sales-channels';

export function useSalesChannels() {
    const api = useApi();
    const channels = ref([]);

    async function load() {
        await api.load(CHANNELS_URL, channels);
    }

    const create = (payload) => api.post(CHANNELS_URL, payload);
    const update = (id, payload) => api.put(`${CHANNELS_URL}/${id}`, payload);
    const remove = (id) => api.del(`${CHANNELS_URL}/${id}`);
    const loadOne = (id, target) => api.load(`${CHANNELS_URL}/${id}`, target);
    const setPrice = (productId, channelId, price) => api.put(`/api/products/${productId}/channel-prices/${channelId}`, { price });

    const offerSupplies = (id, supplyIds) => api.put(`${CHANNELS_URL}/${id}/supplies`, { supplyIds });

    const addCost = (id, payload) => api.post(`${CHANNELS_URL}/${id}/costs`, payload);
    const reviseCost = (id, costId, payload) => api.put(`${CHANNELS_URL}/${id}/costs/${costId}`, payload);
    const removeCost = (id, costId) => api.del(`${CHANNELS_URL}/${id}/costs/${costId}`);

    return { channels, load, loadOne, create, update, remove, setPrice, offerSupplies, addCost, reviseCost, removeCost };
}
