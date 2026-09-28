<script setup>
import DataTable from '../ui/DataTable.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    lines: { type: Array, required: true },
});
</script>

<template>
    <DataTable :items="lines">
        <template #head>
            <tr>
                <th>Produit</th>
                <th class="data-table__cell--number">Qté</th>
                <th class="data-table__cell--number">Prix unitaire</th>
                <th class="data-table__cell--number">Total</th>
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
