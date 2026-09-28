import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useWorkspaceSettings() {
    const api = useApi();
    const settings = ref(null);

    async function load() {
        settings.value = await api.get('/api/workspace/settings');
    }

    const saveSumUp = (payload) => api.put('/api/workspace/settings/sumup', payload);
    const removeSumUpApiKey = () => api.del('/api/workspace/settings/sumup/api-key');

    return { settings, load, saveSumUp, removeSumUpApiKey };
}
