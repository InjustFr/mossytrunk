import { ref } from 'vue';
import { useApi } from './useApi.js';

const monthFormatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric', timeZone: 'UTC' });
const shortMonthFormatter = new Intl.DateTimeFormat('fr-FR', { month: 'short', timeZone: 'UTC' });

export const PERIOD_STATUSES = {
    declared: { label: 'Déclarée', tone: 'success' },
    changed: { label: 'Montant modifié', tone: 'warning' },
    late: { label: 'En retard', tone: 'danger' },
    due: { label: 'À déclarer', tone: 'warning' },
    current: { label: 'En cours', tone: 'neutral' },
    upcoming: { label: 'À venir', tone: 'neutral' },
    inactive: { label: 'Avant l\'activité', tone: 'neutral' },
};

export function periodLabel(period) {
    if (period.periodicity === 'monthly') {
        const label = monthFormatter.format(new Date(`${period.start}T00:00:00Z`));
        return label.charAt(0).toUpperCase() + label.slice(1);
    }
    return `${period.index === 1 ? '1er' : `${period.index}e`} trimestre ${period.year}`;
}

export function periodShortLabel(period) {
    return period.periodicity === 'monthly' ? shortMonthFormatter.format(new Date(`${period.start}T00:00:00Z`)) : `T${period.index}`;
}

export function useAccounting() {
    const api = useApi();
    const overview = ref(null);

    async function load(year = null) {
        overview.value = await api.get(`/api/accounting/urssaf${year ? `?year=${year}` : ''}`);
    }

    return {
        overview,
        load,
        setPeriodicity: (periodicity) => api.put('/api/accounting/urssaf/periodicity', { periodicity }),
        declare: (key) => api.put(`/api/accounting/urssaf/${key}/declaration`),
        withdraw: (key) => api.del(`/api/accounting/urssaf/${key}/declaration`),
        exportUrl: (from, to) => `/api/accounting/orders.csv?from=${from}&to=${to}`,
    };
}
