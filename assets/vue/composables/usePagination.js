import { computed, ref, watch } from 'vue';

export const PAGE_SIZES = [20, 50, 100];

/** Client-side pagination of a reactive list. The page is kept in range when the list shrinks. */
export function usePagination(items, initialPageSize = PAGE_SIZES[0]) {
    const page = ref(1);
    const pageSize = ref(initialPageSize);

    const total = computed(() => items.value.length);
    const pageCount = computed(() => Math.max(1, Math.ceil(total.value / pageSize.value)));
    const pageItems = computed(() => items.value.slice((page.value - 1) * pageSize.value, page.value * pageSize.value));
    const from = computed(() => (total.value === 0 ? 0 : (page.value - 1) * pageSize.value + 1));
    const to = computed(() => Math.min(total.value, page.value * pageSize.value));

    function goTo(target) {
        page.value = Math.min(Math.max(1, target), pageCount.value);
    }

    watch(pageCount, () => goTo(page.value));
    watch(pageSize, () => goTo(1));

    return { page, pageSize, total, pageCount, pageItems, from, to, goTo };
}
