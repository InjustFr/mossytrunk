import { ref } from 'vue';
import { useApi } from './useApi.js';

export function useGabarits() {
    const api = useApi();
    const gabarits = ref([]);

    async function load() {
        gabarits.value = await api.get('/api/gabarits');
    }

    const save = (id, payload) => (id ? api.put(`/api/gabarits/${id}`, payload) : api.post('/api/gabarits', payload));

    return { gabarits, load, save };
}

export function useDesignBoard() {
    const api = useApi();
    const board = ref(null);

    async function load() {
        board.value = await api.get('/api/designs');
    }

    return {
        board,
        load,
        createDesign: (payload) => api.post('/api/designs', payload),
        saveCollection: (id, payload) => (id ? api.put(`/api/design-collections/${id}`, payload) : api.post('/api/design-collections', payload)),
        workOnCollection: (id, current) => api.put(`/api/design-collections/${id}/current`, { current }),
        validateCollection: (id) => api.post(`/api/design-collections/${id}/validation`),
    };
}

export function useDesign(designId) {
    const api = useApi();
    const design = ref(null);
    const base = `/api/designs/${designId}`;

    async function load() {
        design.value = await api.get(base);
    }

    return {
        design,
        load,
        update: (payload) => api.put(base, payload),
        remove: () => api.del(base),
        workOn: (current) => api.put(`${base}/current`, { current }),
        decline: (gabaritId) => api.post(`${base}/declinations`, { gabaritId }),
        adjust: (declinationId, payload) => api.put(`${base}/declinations/${declinationId}`, payload),
        withdraw: (declinationId) => api.del(`${base}/declinations/${declinationId}`),
        tick: (declinationId, adaptation, done) => api.put(`${base}/declinations/${declinationId}/adaptations`, { adaptation, done }),
        validate: () => api.post(`${base}/validation`),
    };
}
