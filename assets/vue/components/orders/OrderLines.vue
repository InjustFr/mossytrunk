<script setup>
import DataTable from '../ui/DataTable.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    lines: { type: Array, required: true },
});
</script>

<template>
    <DataTable>
        <template #head>
            <tr>
                <th>Produit</th>
                <th class="data-table__cell--number">Qté</th>
                <th class="data-table__cell--number">Prix unitaire</th>
                <th class="data-table__cell--number">Total</th>
            </tr>
        </template>
        <tr v-for="line in lines" :key="`${line.productId}-${line.label}`">
            <td>{{ line.label }}</td>
            <td class="data-table__cell--number">{{ line.quantity }}</td>
            <td class="data-table__cell--number"><MoneyAmount :cents="line.unitPrice" /></td>
            <td class="data-table__cell--number"><MoneyAmount :cents="line.total" /></td>
        </tr>
    </DataTable>
</template>
