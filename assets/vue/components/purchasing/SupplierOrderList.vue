<script setup>
import { useI18n } from 'vue-i18n';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDate } from '../../composables/useDate.js';
import { SUPPLIER_ORDER_STATUSES } from '../../composables/usePurchasing.js';

const { t } = useI18n();

defineProps({
    orders: { type: Array, required: true },
});
</script>

<template>
    <EmptyState v-if="orders.length === 0">{{ t('purchasing.list.empty') }}</EmptyState>
    <DataTable remember-page v-else :items="orders" class="supplier-order-list">
        <template #head>
            <tr>
                <th>{{ t('purchasing.list.order') }}</th>
                <th>{{ t('purchasing.list.supplier') }}</th>
                <th>{{ t('purchasing.list.orderedOn') }}</th>
                <th class="data-table__cell--number">{{ t('purchasing.list.items') }}</th>
                <th class="data-table__cell--number">{{ t('purchasing.list.total') }}</th>
                <th>{{ t('purchasing.list.status') }}</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="order in rows" :key="order.id">
                <td>
                    <a :href="`/supplier-orders/${order.id}`">{{ order.reference }}</a>
                    <span v-if="order.supplierReference" class="supplier-order-list__supplier-reference">{{ t('purchasing.list.supplierReference', { reference: order.supplierReference }) }}</span>
                </td>
                <td>{{ order.supplier.name }}</td>
                <td>{{ formatDate(order.orderedOn) }}</td>
                <td class="data-table__cell--number">
                    {{ order.receivedUnits ?? order.orderedUnits }}
                    <span v-if="order.receivedUnits !== null && order.receivedUnits !== order.orderedUnits" class="supplier-order-list__planned">{{ t('purchasing.list.ofOrdered', { count: order.orderedUnits }) }}</span>
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="order.total" /></td>
                <td>
                    <StatusBadge :tone="SUPPLIER_ORDER_STATUSES[order.status].tone">{{ t(SUPPLIER_ORDER_STATUSES[order.status].label) }}</StatusBadge>
                    <span class="supplier-order-list__lines">{{ t('purchasing.list.lines', order.lines.length) }}</span>
                </td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.supplier-order-list__supplier-reference { display: block; color: var(--color-muted); font-size: 0.75rem; }
.supplier-order-list__planned { display: block; color: var(--color-muted); font-size: 0.75rem; }
.supplier-order-list__lines { margin-left: var(--space-2); color: var(--color-muted); font-size: 0.8rem; }
</style>
