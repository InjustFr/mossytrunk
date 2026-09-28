<script setup>
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDateTime } from '../../composables/useDate.js';

defineProps({
    orders: { type: Array, required: true },
    // Id of the order just placed, briefly highlighted.
    highlightId: { type: String, default: null },
});
</script>

<template>
    <EmptyState v-if="orders.length === 0">Aucune commande.</EmptyState>
    <DataTable v-else :items="orders" class="order-list">
        <template #head>
            <tr>
                <th>Référence</th>
                <th>Date</th>
                <th>Événement</th>
                <th class="data-table__cell--number">Articles</th>
                <th class="data-table__cell--number">Remises</th>
                <th class="data-table__cell--number">Total</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr
                v-for="order in rows"
                :key="order.id"
                :class="['order-list__row', { 'order-list__row--new': order.id === highlightId }]"
            >
                <td>
                    <a class="order-list__reference" :href="`/commandes/${order.id}`">{{ order.reference }}</a>
                    <span v-if="order.source === 'sumup'" class="order-list__badge">SumUp</span>
                </td>
                <td>{{ formatDateTime(order.placedAt) }}</td>
                <td>{{ order.eventName }}</td>
                <td class="data-table__cell--number">{{ order.itemCount }}</td>
                <td class="data-table__cell--number">
                    <MoneyAmount v-if="order.discountTotal > 0" :cents="-order.discountTotal" />
                    <span v-else class="order-list__none">—</span>
                </td>
                <td class="data-table__cell--number order-list__total"><MoneyAmount :cents="order.total" /></td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.order-list__reference { font-weight: 600; text-decoration: none; }
.order-list__reference:hover { text-decoration: underline; }

.order-list__badge {
    margin-left: var(--space-2);
    padding: 1px var(--space-2);
    border-radius: 999px;
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    color: var(--color-muted);
    font-size: 0.75rem;
}

.order-list__none { color: var(--color-muted); }
.order-list__total { font-weight: 600; }

.order-list__row--new { animation: order-list-highlight 2.4s ease-out; }

@keyframes order-list-highlight {
    from { background: var(--color-accent-soft); }
    to { background: transparent; }
}
</style>
