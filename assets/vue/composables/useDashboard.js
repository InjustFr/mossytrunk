import { ref } from 'vue';
import { useApi } from './useApi.js';

export const MONTHS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

export function useDashboard() {
    const api = useApi();
    const dashboard = ref(null);

    async function load(year = null) {
        dashboard.value = await api.get(`/api/dashboard${year ? `?year=${year}` : ''}`);
    }

    return { dashboard, load };
}
