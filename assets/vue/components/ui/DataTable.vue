<script setup>
import { computed } from 'vue';
import TablePagination from './TablePagination.vue';
import { PAGE_SIZES, usePagination } from '../../composables/usePagination.js';

// Consistent table styling. Give `items` to paginate: the default slot then receives `{ rows }`, the current page.
// Without `items`, rows come straight from the default slot (short, unpaginated tables).
const props = defineProps({
    items: { type: Array, default: null },
    pageSize: { type: Number, default: 20 },
});

const pagination = usePagination(computed(() => props.items ?? []), props.pageSize);
const paginated = computed(() => props.items !== null);
// Shown once there is more than the smallest page size, so a larger page size can always be switched back.
const showPagination = computed(() => paginated.value && pagination.total.value > PAGE_SIZES[0]);
</script>

<template>
    <div class="data-table">
        <div class="data-table__scroll">
            <table class="data-table__table">
                <thead class="data-table__head"><slot name="head" /></thead>
                <tbody class="data-table__body">
                    <slot v-if="paginated" :rows="pagination.pageItems.value" />
                    <slot v-else />
                </tbody>
                <tfoot v-if="$slots.foot" class="data-table__foot"><slot name="foot" /></tfoot>
            </table>
        </div>
        <TablePagination
            v-if="showPagination"
            v-model:page-size="pagination.pageSize.value"
            :page="pagination.page.value"
            :page-count="pagination.pageCount.value"
            :from="pagination.from.value"
            :to="pagination.to.value"
            :total="pagination.total.value"
            @go="pagination.goTo"
        />
    </div>
</template>

<style scoped>
.data-table__scroll { overflow-x: auto; }

.data-table__table { width: 100%; border-collapse: collapse; }

.data-table :deep(th),
.data-table :deep(td) {
    padding: var(--space-2) var(--space-3);
    text-align: left;
    border-bottom: 0.0625rem solid var(--color-border);
    vertical-align: middle;
}

.data-table :deep(th) {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.084rem;
    color: var(--color-muted);
    border-bottom-color: var(--color-border-strong);
}

.data-table__body :deep(tr) { transition: background var(--transition); }
.data-table__body :deep(tr:hover) { background: #fafaf8; }

.data-table :deep(.data-table__cell--number) { text-align: right; font-variant-numeric: tabular-nums; }

.data-table__foot :deep(td) { font-weight: 700; border-bottom: none; }
</style>
