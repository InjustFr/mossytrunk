import { ref } from 'vue';
import { useApi } from './useApi.js';
import { t } from '../i18n/index.js';
import { perLocale } from '../i18n/locale.js';

const monthFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric', timeZone: 'UTC' }));
const shortMonthFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { month: 'short', timeZone: 'UTC' }));

export const PERIOD_STATUSES = {
    declared: { tone: 'success' },
    changed: { tone: 'warning' },
    late: { tone: 'danger' },
    due: { tone: 'warning' },
    current: { tone: 'neutral' },
    upcoming: { tone: 'neutral' },
    inactive: { tone: 'neutral' },
};

export function periodStatusLabel(status) {
    return t(`accounting.status.${status}`);
}

export function periodLabel(period) {
    if (period.periodicity === 'monthly') {
        const label = monthFormatter().format(new Date(`${period.start}T00:00:00Z`));
        return label.charAt(0).toUpperCase() + label.slice(1);
    }
    return t(period.index === 1 ? 'accounting.period.firstQuarter' : 'accounting.period.quarter', { index: period.index, year: period.year });
}

export function periodShortLabel(period) {
    return period.periodicity === 'monthly' ? shortMonthFormatter().format(new Date(`${period.start}T00:00:00Z`)) : t('accounting.period.shortQuarter', { index: period.index });
}

export function useAccounting() {
    const api = useApi();
    const overview = ref(null);

    async function load(year = null) {
        await api.load(`/api/accounting/urssaf${year ? `?year=${year}` : ''}`, overview);
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
