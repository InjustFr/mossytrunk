import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useWorkspaceSettings() {
    const api = useApi();
    const settings = ref(null);

    async function load() {
        await api.load('/api/workspace/settings', settings);
    }

    return { settings, load };
}
