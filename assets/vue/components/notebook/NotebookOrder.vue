<script setup>
import { useI18n } from 'vue-i18n';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDateTime } from '../../composables/useDate.js';

defineProps({
    order: { type: Object, required: true },
});
const { t } = useI18n();
</script>

<template>
    <div class="notebook-order">
        <p class="notebook-order__title">
            <a class="notebook-order__reference" :href="`/orders/${order.id}`">{{ order.reference }}</a>
            <span>{{ formatDateTime(order.placedAt) }}</span>
            <StatusBadge v-if="order.refunded">{{ t('notebook.report.refunded') }}</StatusBadge>
        </p>
        <ul class="notebook-order__lines">
            <li v-for="(line, index) in order.lines" :key="index">{{ line.quantity }} × {{ line.label }}</li>
        </ul>
    </div>
</template>

<style scoped>
.notebook-order__title { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-2); margin: 0 0 var(--space-1); color: var(--color-muted); font-size: 0.8rem; }
.notebook-order__reference { color: var(--color-ink); font-weight: 600; }
.notebook-order__lines { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }
</style>
