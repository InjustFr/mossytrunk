<script setup>
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDate } from '../../composables/useDate.js';
import { plural } from '../../composables/usePlural.js';
import { SUPPLIER_ORDER_STATUSES } from '../../composables/usePurchasing.js';

defineProps({
    orders: { type: Array, required: true },
});
</script>

<template>
    <EmptyState v-if="orders.length === 0">Aucune commande fournisseur. Passez-en une pour suivre ce que vous attendez.</EmptyState>
    <DataTable v-else :items="orders" class="supplier-order-list">
        <template #head>
            <tr>
                <th>Commande</th>
                <th>Fournisseur</th>
                <th>Commandée le</th>
                <th class="data-table__cell--number">Articles</th>
                <th class="data-table__cell--number">Total</th>
                <th>Statut</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="order in rows" :key="order.id">
                <td><a :href="`/supplier-orders/${order.id}`">{{ order.reference }}</a></td>
                <td>{{ order.supplier.name }}</td>
                <td>{{ formatDate(order.orderedOn) }}</td>
                <td class="data-table__cell--number">
                    {{ order.receivedUnits ?? order.orderedUnits }}
                    <span v-if="order.receivedUnits !== null && order.receivedUnits !== order.orderedUnits" class="supplier-order-list__planned">sur {{ order.orderedUnits }} commandés</span>
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="order.total" /></td>
                <td>
                    <StatusBadge :tone="SUPPLIER_ORDER_STATUSES[order.status].tone">{{ SUPPLIER_ORDER_STATUSES[order.status].label }}</StatusBadge>
                    <span class="supplier-order-list__lines">{{ plural(order.lines.length, 'ligne') }}</span>
                </td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.supplier-order-list__planned { display: block; color: var(--color-muted); font-size: 0.75rem; }
.supplier-order-list__lines { margin-left: var(--space-2); color: var(--color-muted); font-size: 0.8rem; }
</style>
