<script setup>
import { computed } from 'vue';
import { ScrollAreaRoot, ScrollAreaScrollbar, ScrollAreaThumb, ScrollAreaViewport } from 'reka-ui';
import TablePagination from './TablePagination.vue';
import { PAGE_SIZES, usePagination } from '../../composables/usePagination.js';
import { queryNumber } from '../../composables/useQueryState.js';

const props = defineProps({
    items: { type: Array, default: null },
    pageSize: { type: Number, default: 20 },
    rememberPage: { type: Boolean, default: false },
    dense: { type: Boolean, default: false },
    fit: { type: Boolean, default: false },
    fixed: { type: Boolean, default: false },
});

const pagination = usePagination(
    computed(() => props.items ?? []),
    props.pageSize,
    props.rememberPage ? { page: queryNumber('page', 1), pageSize: queryNumber('perPage', props.pageSize) } : undefined,
);
const paginated = computed(() => props.items !== null);
const showPagination = computed(() => paginated.value && pagination.total.value > PAGE_SIZES[0]);
</script>

<template>
    <div :class="['data-table', { 'data-table--dense': dense, 'data-table--fit': fit, 'data-table--fixed': fixed }]">
        <ScrollAreaRoot class="data-table__scroll" type="hover">
            <ScrollAreaViewport class="data-table__viewport">
                <table class="data-table__table">
                    <thead class="data-table__head"><slot name="head" /></thead>
                    <tbody class="data-table__body">
                        <slot v-if="paginated" :rows="pagination.pageItems.value" />
                        <slot v-else />
                    </tbody>
                    <tfoot v-if="$slots.foot" class="data-table__foot"><slot name="foot" /></tfoot>
                </table>
            </ScrollAreaViewport>
            <ScrollAreaScrollbar class="data-table__scrollbar" orientation="horizontal">
                <ScrollAreaThumb class="data-table__thumb" />
            </ScrollAreaScrollbar>
        </ScrollAreaRoot>
        <TablePagination
            v-if="showPagination"
            v-model:page-size="pagination.pageSize.value"
            :page="pagination.page.value"
            :from="pagination.from.value"
            :to="pagination.to.value"
            :total="pagination.total.value"
            @go="pagination.goTo"
        />
    </div>
</template>

<style scoped>
.data-table__scroll { position: relative; overflow: hidden; }
.data-table__viewport { width: 100%; }

.data-table__scrollbar {
    display: flex;
    height: 0.5rem;
    padding: 0.0625rem;
    user-select: none;
    touch-action: none;
}

.data-table__thumb {
    position: relative;
    flex: 1;
    border-radius: var(--radius-pill);
    background: var(--color-border-strong);
}

.data-table__table { width: 100%; min-width: 40rem; border-collapse: collapse; }
.data-table--fit .data-table__table { min-width: 0; }
.data-table--fixed .data-table__table { table-layout: fixed; }

.data-table :deep(th),
.data-table :deep(td) {
    padding: 0.4375rem var(--space-3);
    text-align: left;
    border-bottom: 0.0625rem solid var(--color-border);
    vertical-align: middle;
}

.data-table :deep(th) {
    font-size: var(--font-size-sm);
    font-weight: 500;
    color: var(--color-muted);
    white-space: nowrap;
    border-bottom-color: var(--color-border-strong);
}

.data-table--dense :deep(th),
.data-table--dense :deep(td) { padding-inline: var(--space-2); }

.data-table__body :deep(tr) { transition: background var(--transition); }
.data-table__body :deep(tr:hover) { background: var(--color-hover); }

.data-table :deep(.data-table__cell--number) { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.data-table :deep(.data-table__cell--actions) { text-align: right; white-space: nowrap; }

.data-table__foot :deep(td) { font-weight: 700; border-bottom: none; }
</style>
