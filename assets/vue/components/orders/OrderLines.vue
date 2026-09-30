<script setup>
import { useI18n } from 'vue-i18n';
import DataTable from '../ui/DataTable.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    lines: { type: Array, required: true },
});

const { t } = useI18n();
</script>

<template>
    <DataTable :items="lines">
        <template #head>
            <tr>
                <th>{{ t('orders.lines.product') }}</th>
                <th class="data-table__cell--number">{{ t('orders.lines.quantity') }}</th>
                <th class="data-table__cell--number">{{ t('orders.lines.unitPrice') }}</th>
                <th class="data-table__cell--number">{{ t('orders.lines.total') }}</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="line in rows" :key="`${line.productId}-${line.label}`">
                <td>{{ line.label }}</td>
                <td class="data-table__cell--number">{{ line.quantity }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="line.unitPrice" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="line.total" /></td>
            </tr>
        </template>
    </DataTable>
</template>
