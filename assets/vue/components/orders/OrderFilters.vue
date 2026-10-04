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
        <input v-model="search" class="control control--compact order-filters__search" type="search" :placeholder="t('orders.filters.searchPlaceholder')" :aria-label="t('orders.filters.searchLabel')">
        <Toggle v-if="unassignedCount > 0 || unassigned" v-model="unassigned" class="chip chip--warning">
            <TriangleAlert size="0.875rem" aria-hidden="true" />
            {{ t('orders.filters.unassigned', unassignedCount) }}
        </Toggle>
    </div>
</template>

<style scoped>
.order-filters { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); }

.order-filters__search { min-width: 16rem; }
</style>
