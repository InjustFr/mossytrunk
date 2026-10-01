<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import PaymentMethod from './PaymentMethod.vue';
import { formatDay, formatTime } from '../../composables/useDate.js';

const props = defineProps({
    orders: { type: Array, required: true },
    highlightId: { type: String, default: null },
});
const checkedIds = defineModel('checkedIds', { type: Array, required: true });

const { t } = useI18n();

const dayKey = (order) => `${order.placedAt.slice(0, 10)}|${order.eventId ?? order.source}`;

const grouped = computed(() => [...props.orders].sort((a, b) => b.placedAt.slice(0, 10).localeCompare(a.placedAt.slice(0, 10))
    || Number(a.eventId === null) - Number(b.eventId === null)
    || (a.eventName ?? a.sourceLabel).localeCompare(b.eventName ?? b.sourceLabel)
    || b.placedAt.localeCompare(a.placedAt)));

const days = computed(() => {
    const totals = new Map();
    for (const order of props.orders) {
        const key = dayKey(order);
        const day = totals.get(key) ?? { count: 0, total: 0 };
        totals.set(key, { count: day.count + 1, total: day.total + (order.refundedAt ? 0 : order.total) });
    }
    return totals;
});

const allChecked = computed(() => props.orders.length > 0 && props.orders.every((order) => checkedIds.value.includes(order.id)));

function checkAll(checked) {
    checkedIds.value = checked ? props.orders.map((order) => order.id) : [];
}

function setChecked(id, checked) {
    checkedIds.value = checked ? [...checkedIds.value, id] : checkedIds.value.filter((existing) => existing !== id);
}

const withDayHeaders = (rows) => rows.map((order, index) => ({
    order,
    day: index === 0 || dayKey(rows[index - 1]) !== dayKey(order) ? days.value.get(dayKey(order)) : null,
}));
</script>

<template>
    <EmptyState v-if="orders.length === 0">{{ t('orders.list.empty') }}</EmptyState>
    <DataTable remember-page v-else :items="grouped" class="order-list">
        <template #head>
            <tr>
                <th class="order-list__check">
                    <BaseCheckbox :model-value="allChecked" :aria-label="t('orders.list.selectAll')" @update:model-value="checkAll" />
                </th>
                <th>{{ t('orders.list.reference') }}</th>
                <th>{{ t('orders.list.time') }}</th>
                <th class="data-table__cell--number">{{ t('orders.list.items') }}</th>
                <th class="data-table__cell--number">{{ t('orders.list.discounts') }}</th>
                <th class="data-table__cell--number">{{ t('orders.list.total') }}</th>
                <th>{{ t('orders.list.payment') }}</th>
                <th>{{ t('orders.list.source') }}</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <template v-for="{ order, day } in withDayHeaders(rows)" :key="order.id">
                <tr v-if="day" class="order-list__day">
                    <th scope="rowgroup" colspan="5">
                        <span class="order-list__date">{{ formatDay(order.placedAt) }}</span>
                        <a v-if="order.eventId" class="order-list__event" :href="`/events/${order.eventId}`">{{ order.eventName }}</a>
                        <span v-else class="order-list__event order-list__event--online">{{ t('orders.shop', { source: order.sourceLabel }) }}</span>
                    </th>
                    <td class="data-table__cell--number order-list__day-total"><MoneyAmount :cents="day.total" /></td>
                    <td class="order-list__day-count" colspan="2">{{ t('orders.list.count', day.count) }}</td>
                </tr>
                <tr :class="['order-list__row', { 'order-list__row--new': order.id === highlightId }]">
                    <td class="order-list__check">
                        <BaseCheckbox
                            :model-value="checkedIds.includes(order.id)"
                            :aria-label="t('orders.list.select', { reference: order.reference })"
                            @update:model-value="setChecked(order.id, $event)"
                        />
                    </td>
                    <td class="order-list__reference"><a :href="`/orders/${order.id}`">{{ order.reference }}</a></td>
                    <td class="order-list__time">{{ formatTime(order.placedAt) }}</td>
                    <td class="data-table__cell--number">{{ order.itemCount }}</td>
                    <td class="data-table__cell--number">
                        <MoneyAmount v-if="order.discountTotal > 0" :cents="-order.discountTotal" />
                        <span v-else class="order-list__none">—</span>
                    </td>
                    <td :class="['data-table__cell--number', 'order-list__total', { 'order-list__total--refunded': order.refundedAt }]"><MoneyAmount :cents="order.total" /></td>
                    <td class="order-list__payment"><PaymentMethod :method="order.paymentMethod" /></td>
                    <td class="order-list__source">
                        <StatusBadge v-if="order.refundedAt" tone="warning">{{ t('orders.list.refunded') }}</StatusBadge>
                        <span v-if="order.source !== 'manual'" class="order-list__badge">{{ order.sourceLabel }}</span>
                        <span v-if="order.externalReferences.length" class="order-list__external">{{ order.externalReferences.join(', ') }}</span>
                    </td>
                </tr>
            </template>
        </template>
    </DataTable>
</template>

<style scoped>
.order-list :deep(.data-table__table) { table-layout: fixed; }
.order-list :deep(th.order-list__check) { width: 2.5rem; }
.order-list :deep(th:nth-child(2)) { width: 17%; }
.order-list :deep(th:nth-child(3)) { width: 9%; }
.order-list :deep(th:nth-child(7)) { width: 12%; }
.order-list :deep(th:nth-child(4)),
.order-list :deep(th:nth-child(5)),
.order-list :deep(th:nth-child(6)) { width: 11%; }

.order-list__day > * { padding-top: var(--space-4); background: var(--color-bg); border-bottom-color: var(--color-border-strong); }
.order-list__day th { text-align: left; font-size: inherit; color: inherit; white-space: normal; }
.order-list__date { font-weight: 600; color: var(--color-ink); }
.order-list__event { margin-left: var(--space-3); color: var(--color-muted); font-weight: 400; }
.order-list__day-total { font-weight: 700; }
.order-list__day-count { color: var(--color-muted); font-size: 0.875rem; }

.order-list__time { font-variant-numeric: tabular-nums; }
.order-list__payment { color: var(--color-muted); font-size: 0.875rem; }
.order-list__total { font-weight: 600; }
.order-list__total--refunded { color: var(--color-subtle); text-decoration: line-through; }
.order-list__none { color: var(--color-subtle); }

.order-list__reference { font-size: 0.8rem; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.order-list__source { font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.order-list__external { margin-left: var(--space-2); color: var(--color-subtle); font-size: 0.75rem; }

.order-list__badge {
    padding: 0.0625rem var(--space-2);
    border-radius: 62.4375rem;
    border: 0.0625rem solid var(--color-border);
    color: var(--color-muted);
    font-size: 0.75rem;
}

.order-list__row--new { animation: order-list-highlight 2.4s ease-out; }

@keyframes order-list-highlight {
    from { background: var(--color-accent-soft); }
    to { background: transparent; }
}
.order-list__event--online { color: var(--color-muted); }
</style>
