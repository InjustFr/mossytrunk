<script setup>
import { computed } from 'vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import PaymentMethod from './PaymentMethod.vue';
import { formatDay, formatTime } from '../../composables/useDate.js';
import { plural } from '../../composables/usePlural.js';

const props = defineProps({
    orders: { type: Array, required: true },
    highlightId: { type: String, default: null },
});

const dayKey = (order) => `${order.placedAt.slice(0, 10)}|${order.eventId}`;

const days = computed(() => {
    const totals = new Map();
    for (const order of props.orders) {
        const key = dayKey(order);
        const day = totals.get(key) ?? { count: 0, total: 0 };
        totals.set(key, { count: day.count + 1, total: day.total + order.total });
    }
    return totals;
});

const withDayHeaders = (rows) => rows.map((order, index) => ({
    order,
    day: index === 0 || dayKey(rows[index - 1]) !== dayKey(order) ? days.value.get(dayKey(order)) : null,
}));
</script>

<template>
    <EmptyState v-if="orders.length === 0">Aucune commande.</EmptyState>
    <DataTable v-else :items="orders" class="order-list">
        <template #head>
            <tr>
                <th>Heure</th>
                <th class="data-table__cell--number">Articles</th>
                <th class="data-table__cell--number">Remises</th>
                <th class="data-table__cell--number">Total</th>
                <th>Paiement</th>
                <th>Référence</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <template v-for="{ order, day } in withDayHeaders(rows)" :key="order.id">
                <tr v-if="day" class="order-list__day">
                    <th scope="rowgroup" colspan="3">
                        <span class="order-list__date">{{ formatDay(order.placedAt) }}</span>
                        <a class="order-list__event" :href="`/evenements/${order.eventId}`">{{ order.eventName }}</a>
                    </th>
                    <td class="data-table__cell--number order-list__day-total"><MoneyAmount :cents="day.total" /></td>
                    <td class="order-list__day-count" colspan="2">{{ plural(day.count, 'commande') }}</td>
                </tr>
                <tr :class="['order-list__row', { 'order-list__row--new': order.id === highlightId }]">
                    <td class="order-list__time">{{ formatTime(order.placedAt) }}</td>
                    <td class="data-table__cell--number">{{ order.itemCount }}</td>
                    <td class="data-table__cell--number">
                        <MoneyAmount v-if="order.discountTotal > 0" :cents="-order.discountTotal" />
                        <span v-else class="order-list__none">—</span>
                    </td>
                    <td class="data-table__cell--number order-list__total"><MoneyAmount :cents="order.total" /></td>
                    <td class="order-list__payment"><PaymentMethod :method="order.paymentMethod" /></td>
                    <td class="order-list__reference">
                        <a :href="`/commandes/${order.id}`">{{ order.reference }}</a>
                        <span v-if="order.source === 'sumup'" class="order-list__badge">SumUp</span>
                    </td>
                </tr>
            </template>
        </template>
    </DataTable>
</template>

<style scoped>
.order-list :deep(.data-table__table) { table-layout: fixed; }
.order-list :deep(th:nth-child(1)) { width: 20%; }
.order-list :deep(th:nth-child(5)) { width: 12%; }
.order-list :deep(th:nth-child(2)),
.order-list :deep(th:nth-child(3)),
.order-list :deep(th:nth-child(4)) { width: 13%; }

.order-list__day > * { padding-top: var(--space-4); background: var(--color-bg); border-bottom-color: var(--color-border-strong); }
.order-list__day th { text-align: left; font-size: inherit; color: inherit; white-space: normal; }
.order-list__date { font-weight: 600; color: var(--color-ink); }
.order-list__event { margin-left: var(--space-3); color: var(--color-muted); font-weight: 400; }
.order-list__day-total { font-weight: 700; }
.order-list__day-count { color: var(--color-muted); font-size: 0.875rem; }

.order-list__time { font-variant-numeric: tabular-nums; }
.order-list__payment { color: var(--color-muted); font-size: 0.875rem; }
.order-list__total { font-weight: 600; }
.order-list__none { color: var(--color-subtle); }

.order-list__reference { font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.order-list__reference a { color: var(--color-muted); }

.order-list__badge {
    margin-left: var(--space-2);
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
</style>
