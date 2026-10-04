import { ref } from 'vue';
import { useApi } from './useApi.js';
import { formatMonth } from './useDate.js';

export function monthName(month) {
    return formatMonth(`2000-${String(month).padStart(2, '0')}`);
}

export function useDashboard() {
    const api = useApi();
    const dashboard = ref(null);

    async function load(year = null) {
        await api.load(`/api/dashboard${year ? `?year=${year}` : ''}`, dashboard);
    }

    return { dashboard, load };
}
