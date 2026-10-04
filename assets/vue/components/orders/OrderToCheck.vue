<script setup>
import { TriangleAlert } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatTime } from '../../composables/useDate.js';

const props = defineProps({
    order: { type: Object, required: true },
    saving: { type: Boolean, default: false },
});
const emit = defineEmits(['toggle']);
const { t, te } = useI18n();

const payment = (method) => (method && te(`orders.payment.${method}`) ? t(`orders.payment.${method}`) : null);
</script>

<template>
    <article :class="['order-to-check', { 'order-to-check--checked': order.checked }]">
        <BaseCheckbox
            class="order-to-check__tick"
            :model-value="order.checked"
            :disabled="saving"
            :aria-label="t('check.tick', { reference: order.reference })"
            @update:model-value="emit('toggle', props.order)"
        />
        <div class="order-to-check__body">
            <header class="order-to-check__header">
                <a class="order-to-check__reference" :href="`/orders/${order.id}`">{{ order.reference }}</a>
                <span class="order-to-check__meta">{{ formatTime(order.placedAt) }} · {{ [order.sourceLabel, payment(order.paymentMethod)].filter(Boolean).join(' · ') }}</span>
                <StatusBadge v-if="order.refunded">{{ t('check.refunded') }}</StatusBadge>
                <MoneyAmount class="order-to-check__total" :cents="order.total" />
            </header>
            <ul class="order-to-check__lines">
                <li v-for="(line, index) in order.lines" :key="index" class="order-to-check__line">
                    <span class="tabular">{{ line.quantity }} ×</span> {{ line.label }}
                    <StatusBadge v-if="line.unidentified" tone="warning"><TriangleAlert size="0.75rem" aria-hidden="true" /> {{ t('check.unidentified') }}</StatusBadge>
                </li>
            </ul>
        </div>
    </article>
</template>

<style scoped>
.order-to-check { display: flex; gap: var(--space-3); align-items: flex-start; padding: var(--space-3) var(--space-4); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-surface); transition: background var(--transition), border-color var(--transition); }
.order-to-check--checked { border-color: var(--color-accent-soft); background: var(--color-accent-soft); }
.order-to-check__tick { margin-top: 0.125rem; }
.order-to-check__body { flex: 1; min-width: 0; }
.order-to-check__header { display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--space-2) var(--space-3); }
.order-to-check__reference { color: var(--color-ink); font-weight: 600; }
.order-to-check__meta { color: var(--color-muted); font-size: var(--font-size-md); }
.order-to-check__total { margin-left: auto; font-weight: 600; }
.order-to-check__lines { display: flex; flex-direction: column; gap: 0.125rem; margin: var(--space-2) 0 0; padding: 0; list-style: none; font-size: var(--font-size-md); }
.order-to-check__line { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-2); }
.order-to-check--checked .order-to-check__lines { color: var(--color-muted); }
</style>
