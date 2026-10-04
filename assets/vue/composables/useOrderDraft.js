import { reactive, ref, watch } from 'vue';
import { useApi } from './useApi.js';
import { nowForInput } from './useDate.js';

export function useOrderDraft() {
    const api = useApi();
    const placedAt = ref(nowForInput());
    const lines = reactive([]);
    const preview = ref(null);
    const previewError = ref(null);
    let timer = null;
    let requestId = 0;

    const sameTuple = (a, b) => a.productId === b.productId && (a.variant ?? null) === (b.variant ?? null);

    function add({ productId, variant, quantity }) {
        const existing = lines.find((line) => sameTuple(line, { productId, variant }));
        if (existing) {
            existing.quantity += quantity;
            return;
        }
        lines.push({ key: `${productId}|${variant ?? ''}`, productId, variant: variant ?? null, quantity });
    }

    function setQuantity(key, quantity) {
        const line = lines.find((l) => l.key === key);
        if (!line) return;
        if (quantity < 1) {
            remove(key);
            return;
        }
        line.quantity = quantity;
    }

    function remove(key) {
        const index = lines.findIndex((l) => l.key === key);
        if (index !== -1) lines.splice(index, 1);
    }

    function reset() {
        lines.splice(0, lines.length);
        preview.value = null;
        previewError.value = null;
    }

    const payload = () => ({
        placedAt: placedAt.value,
        lines: lines.map(({ productId, variant, quantity }) => ({ productId, variant, quantity })),
    });

    async function refreshPreview() {
        if (lines.length === 0 || !placedAt.value) {
            preview.value = null;
            previewError.value = null;
            return;
        }
        const current = ++requestId;
        try {
            const result = await api.query('/api/orders/preview', payload());
            if (current === requestId) {
                preview.value = result;
                previewError.value = null;
            }
        } catch (error) {
            if (current === requestId) {
                preview.value = null;
                previewError.value = error.message;
            }
        }
    }

    watch([placedAt, lines], () => {
        clearTimeout(timer);
        timer = setTimeout(refreshPreview, 200);
    }, { deep: true });

    return { placedAt, lines, preview, previewError, add, setQuantity, remove, reset, payload };
}
