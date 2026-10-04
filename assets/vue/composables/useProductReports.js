import { ref } from 'vue';
import { useApi } from './useApi.js';
import { queryText } from './useQueryState.js';

export const LAST_TWELVE_MONTHS = '12m';
export const SINCE_THE_START = 'all';

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
