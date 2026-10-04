<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from '@lucide/vue';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseSelect from './BaseSelect.vue';
import { PAGE_SIZES } from '../../composables/usePagination.js';

const { t } = useI18n();

const props = defineProps({
    page: { type: Number, required: true },
    from: { type: Number, required: true },
    to: { type: Number, required: true },
    total: { type: Number, required: true },
});
const pageSize = defineModel('pageSize', { type: Number, required: true });
const emit = defineEmits(['go']);

const sizeOptions = PAGE_SIZES.map((size) => ({ value: size, label: String(size) }));

const current = computed({
    get: () => props.page,
    set: (page) => emit('go', page),
});
</script>

<template>
    <nav class="table-pagination" :aria-label="t('ui.pagination.label')">
        <span class="table-pagination__range">{{ t('ui.pagination.range', { from, to, total }) }}</span>

        <PaginationRoot
            v-model:page="current"
            :total="total"
            :items-per-page="pageSize"
            :sibling-count="1"
            show-edges
            as="div"
        >
            <PaginationList v-slot="{ items }" class="table-pagination__pages">
                <PaginationFirst class="table-pagination__button" :aria-label="t('ui.pagination.first')">
                    <ChevronsLeft size="1rem" aria-hidden="true" />
                </PaginationFirst>
                <PaginationPrev class="table-pagination__button" :aria-label="t('ui.pagination.previous')">
                    <ChevronLeft size="1rem" aria-hidden="true" />
                </PaginationPrev>
                <template v-for="(item, index) in items" :key="`${item.type}-${index}`">
                    <PaginationListItem
                        v-if="item.type === 'page'"
                        :value="item.value"
                        class="table-pagination__button"
                    >{{ item.value }}</PaginationListItem>
                    <PaginationEllipsis v-else :index="index" class="table-pagination__gap">…</PaginationEllipsis>
                </template>
                <PaginationNext class="table-pagination__button" :aria-label="t('ui.pagination.next')">
                    <ChevronRight size="1rem" aria-hidden="true" />
                </PaginationNext>
                <PaginationLast class="table-pagination__button" :aria-label="t('ui.pagination.last')">
                    <ChevronsRight size="1rem" aria-hidden="true" />
                </PaginationLast>
            </PaginationList>
        </PaginationRoot>

        <div class="table-pagination__size">
            {{ t('ui.pagination.perPage') }}
            <BaseSelect v-model="pageSize" :options="sizeOptions" size="small" :aria-label="t('ui.pagination.perPage')" />
        </div>
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
    font-size: var(--font-size-md);
    color: var(--color-muted);
}

.table-pagination__pages { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-1); }

.table-pagination__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 var(--space-2);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    font-variant-numeric: tabular-nums;
    transition: border-color var(--transition), background var(--transition), color var(--transition);
}

.table-pagination__button:hover:not(:disabled) { border-color: var(--color-ink); }
.table-pagination__button:disabled { opacity: 0.4; cursor: not-allowed; }
.table-pagination__button[data-selected] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }
.table-pagination__gap { padding: 0 var(--space-1); }

.table-pagination__size { display: flex; align-items: center; gap: var(--space-2); }
</style>
