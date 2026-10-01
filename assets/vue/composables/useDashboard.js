import { ref } from 'vue';
import { useApi } from './useApi.js';
import { perLocale } from '../i18n/locale.js';

const monthFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { month: 'short', timeZone: 'UTC' }));

export function monthName(month) {
    return monthFormatter().format(new Date(Date.UTC(2000, month - 1, 1)));
}

export function useDashboard() {
    const api = useApi();
    const dashboard = ref(null);

    async function load(year = null) {
        await api.load(`/api/dashboard${year ? `?year=${year}` : ''}`, dashboard);
    }

    return { dashboard, load };
}
