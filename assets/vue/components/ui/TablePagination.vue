<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from '@lucide/vue';
import { PAGE_SIZES } from '../../composables/usePagination.js';

const props = defineProps({
    page: { type: Number, required: true },
    pageCount: { type: Number, required: true },
    from: { type: Number, required: true },
    to: { type: Number, required: true },
    total: { type: Number, required: true },
});
const pageSize = defineModel('pageSize', { type: Number, required: true });
const emit = defineEmits(['go']);

// Current page ± 1, plus first and last; "…" marks the gaps.
const pages = computed(() => {
    const shown = new Set([1, props.pageCount, props.page - 1, props.page, props.page + 1]);
    const list = [...shown].filter((p) => p >= 1 && p <= props.pageCount).sort((a, b) => a - b);
    return list.flatMap((p, i) => (i > 0 && p - list[i - 1] > 1 ? ['…', p] : [p]));
});
</script>

<template>
    <nav class="table-pagination" aria-label="Pagination">
        <span class="table-pagination__range">{{ from }}–{{ to }} sur {{ total }}</span>

        <div class="table-pagination__pages">
            <button type="button" class="table-pagination__button" :disabled="page === 1" aria-label="Première page" @click="emit('go', 1)">
                <ChevronsLeft :size="16" aria-hidden="true" />
            </button>
            <button type="button" class="table-pagination__button" :disabled="page === 1" aria-label="Page précédente" @click="emit('go', page - 1)">
                <ChevronLeft :size="16" aria-hidden="true" />
            </button>
            <template v-for="(item, index) in pages" :key="`${item}-${index}`">
                <span v-if="item === '…'" class="table-pagination__gap" aria-hidden="true">…</span>
                <button
                    v-else
                    type="button"
                    :class="['table-pagination__button', { 'table-pagination__button--current': item === page }]"
                    :aria-current="item === page ? 'page' : undefined"
                    :aria-label="`Page ${item}`"
                    @click="emit('go', item)"
                >{{ item }}</button>
            </template>
            <button type="button" class="table-pagination__button" :disabled="page === pageCount" aria-label="Page suivante" @click="emit('go', page + 1)">
                <ChevronRight :size="16" aria-hidden="true" />
            </button>
            <button type="button" class="table-pagination__button" :disabled="page === pageCount" aria-label="Dernière page" @click="emit('go', pageCount)">
                <ChevronsRight :size="16" aria-hidden="true" />
            </button>
        </div>

        <label class="table-pagination__size">
            Par page
            <select v-model.number="pageSize">
                <option v-for="size in PAGE_SIZES" :key="size" :value="size">{{ size }}</option>
            </select>
        </label>
    </nav>
</template>

<style scoped>
.table-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    flex-wrap: wrap;
    padding-top: var(--space-3);
    font-size: 0.9rem;
    color: var(--color-muted);
}

.table-pagination__pages { display: flex; align-items: center; gap: var(--space-1); }

.table-pagination__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 var(--space-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    font-variant-numeric: tabular-nums;
    transition: border-color var(--transition), background var(--transition), color var(--transition);
}

.table-pagination__button:hover:not(:disabled) { border-color: var(--color-ink); }
.table-pagination__button:disabled { opacity: 0.4; cursor: not-allowed; }
.table-pagination__button--current { background: var(--color-ink); border-color: var(--color-ink); color: #fff; }
.table-pagination__gap { padding: 0 var(--space-1); }

.table-pagination__size { display: flex; align-items: center; gap: var(--space-2); }
.table-pagination__size select {
    min-height: 32px;
    padding: 0 var(--space-2);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
}
</style>
