import { ref } from 'vue';
import { perLocale } from '../i18n/locale.js';
import { useApi } from './useApi.js';
import { queryText } from './useQueryState.js';

export const LAST_TWELVE_MONTHS = '12m';
export const SINCE_THE_START = 'all';

const monthFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { month: 'short', timeZone: 'UTC' }));
const longMonthFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric', timeZone: 'UTC' }));
const percentFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: 1 }));

export const monthLabel = (month) => monthFormatter().format(new Date(`${month}-01T00:00:00Z`));
export const longMonthLabel = (month) => longMonthFormatter().format(new Date(`${month}-01T00:00:00Z`));
export const formatShare = (part, whole) => (whole > 0 ? percentFormatter().format(part / whole) : '—');

export function useProductReports() {
    const api = useApi();
    const period = queryText('period', LAST_TWELVE_MONTHS);
    const productId = queryText('product');
    const report = ref(null);
    const story = ref(null);

    const query = () => `?period=${encodeURIComponent(period.value)}`;
    const loadReport = () => api.load(`/api/reports/products${query()}`, report);

    async function loadStory() {
        if (!productId.value) {
            story.value = null;
            return;
        }
        await api.load(`/api/reports/products/${productId.value}${query()}`, story);
    }

    return { period, productId, report, story, loadReport, loadStory };
}
