import { computed, ref, watch } from 'vue';

export const PAGE_SIZES = [20, 50, 100];

export function usePagination(items, initialPageSize = PAGE_SIZES[0], state = { page: ref(1), pageSize: ref(initialPageSize) }) {
    const { page, pageSize } = state;

    const total = computed(() => items.value.length);
    const pageCount = computed(() => Math.max(1, Math.ceil(total.value / pageSize.value)));
    const pageItems = computed(() => items.value.slice((page.value - 1) * pageSize.value, page.value * pageSize.value));
    const from = computed(() => (total.value === 0 ? 0 : (page.value - 1) * pageSize.value + 1));
    const to = computed(() => Math.min(total.value, page.value * pageSize.value));

    function goTo(target) {
        page.value = Math.min(Math.max(1, target), pageCount.value);
    }

    watch(pageCount, () => {
        if (total.value > 0) {
            goTo(page.value);
        }
    }, { immediate: true });
    watch(pageSize, () => goTo(1));

    return { page, pageSize, total, pageCount, pageItems, from, to, goTo };
}
