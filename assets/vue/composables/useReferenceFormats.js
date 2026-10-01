import { ref } from 'vue';
import { useApi } from './useApi.js';

const FORMATS_URL = '/api/references/formats';

export function useReferenceFormats() {
    const api = useApi();
    const formats = ref([]);

    async function load() {
        await api.load(FORMATS_URL, formats);
    }

    async function preview(kind, template) {
        const query = new URLSearchParams({ template });
        return (await api.peek(`${FORMATS_URL}/${kind}/preview?${query}`)).example;
    }

    const change = (kind, template, applyToExisting) => api.put(`${FORMATS_URL}/${kind}`, { template, applyToExisting });

    return { formats, load, preview, change };
}
