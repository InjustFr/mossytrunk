import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useWorkspaceSettings() {
    const api = useApi();
    const settings = ref(null);

    async function load() {
        settings.value = await api.get('/api/workspace/settings');
    }

    return { settings, load };
}
