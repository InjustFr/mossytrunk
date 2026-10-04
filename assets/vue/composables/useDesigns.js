import { computed, ref } from 'vue';
import { useApi } from './useApi.js';

export function useGabarits() {
    const api = useApi();
    const gabarits = ref([]);

    async function load() {
        await api.load('/api/gabarits', gabarits);
    }

    const save = (id, payload) => (id ? api.put(`/api/gabarits/${id}`, payload) : api.post('/api/gabarits', payload));

    const remove = (id) => api.del(`/api/gabarits/${id}`);

    return { gabarits, load, save, remove };
}

export function useDesignBoard() {
    const api = useApi();
    const board = ref(null);

    async function load() {
        await api.load('/api/designs', board);
    }

    return {
        board,
        load,
        createDesign: (payload) => api.post('/api/designs', payload),
        saveCollection: (id, payload) => (id ? api.put(`/api/design-collections/${id}`, payload) : api.post('/api/design-collections', payload)),
        removeCollection: (id) => api.del(`/api/design-collections/${id}`),
        workOnCollection: (id, current) => api.put(`/api/design-collections/${id}/current`, { current }),
        validateCollection: (id) => api.post(`/api/design-collections/${id}/validation`),
    };
}

export function useDesign(designId) {
    const api = useApi();
    const design = ref(null);
    const base = `/api/designs/${designId}`;

    async function load() {
        await api.load(base, design);
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

export function useDesignProgress(design) {
    const pending = computed(() => design.value?.declinations.filter((declination) => !declination.productId) ?? []);
    const hasProducts = computed(() => (design.value?.declinations.length ?? 0) > pending.value.length);
    const ready = computed(() => design.value?.readyToValidate ?? false);

    return { pending, hasProducts, ready };
}
