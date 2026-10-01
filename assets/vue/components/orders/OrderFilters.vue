<script setup>
import { Toggle } from 'reka-ui';
import { TriangleAlert } from '@lucide/vue';
import { useI18n } from 'vue-i18n';

defineProps({
    unassignedCount: { type: Number, default: 0 },
});
const search = defineModel('search', { type: String, required: true });
const unassigned = defineModel('unassigned', { type: Boolean, default: false });
const { t } = useI18n();
</script>

<template>
    <div class="order-filters">
        <input v-model="search" class="order-filters__search" type="search" :placeholder="t('orders.filters.searchPlaceholder')" :aria-label="t('orders.filters.searchLabel')">
        <Toggle v-if="unassignedCount > 0 || unassigned" v-model="unassigned" class="order-filters__unassigned">
            <TriangleAlert size="0.875rem" aria-hidden="true" />
            {{ t('orders.filters.unassigned', unassignedCount) }}
        </Toggle>
    </div>
</template>

<style scoped>
.order-filters { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); }

.order-filters__search {
    min-height: 2.125rem;
    min-width: 16rem;
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
}

.order-filters__unassigned {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-warning);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    color: var(--color-warning);
    font-size: 0.85rem;
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.order-filters__unassigned[data-state="on"] { background: var(--color-warning); color: var(--color-surface); }
</style>
