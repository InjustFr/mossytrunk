<script setup>
import DataTable from '../ui/DataTable.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

// Rows of figure sets (a month or a year) with a label column; optional total row.
defineProps({
    rows: { type: Array, required: true },
    labelHeader: { type: String, required: true },
    label: { type: Function, required: true },
    total: { type: Object, default: null },
});
</script>

<template>
    <DataTable class="results-table">
        <template #head>
            <tr>
                <th>{{ labelHeader }}</th>
                <th class="data-table__cell--number">Commandes</th>
                <th class="data-table__cell--number">CA</th>
                <th class="data-table__cell--number">Coût d'achat</th>
                <th class="data-table__cell--number">Dépenses</th>
                <th class="data-table__cell--number">URSSAF</th>
                <th class="data-table__cell--number">Résultat</th>
            </tr>
        </template>
        <tr v-for="row in rows" :key="label(row)" :class="{ 'results-table__row--empty': row.orderCount === 0 && row.expenses === 0 }">
            <td>{{ label(row) }}</td>
            <td class="data-table__cell--number">{{ row.orderCount }}</td>
            <td class="data-table__cell--number"><MoneyAmount :cents="row.turnover" /></td>
            <td class="data-table__cell--number"><MoneyAmount :cents="row.costOfGoods" /></td>
            <td class="data-table__cell--number"><MoneyAmount :cents="row.expenses" /></td>
            <td class="data-table__cell--number"><MoneyAmount :cents="row.urssaf" /></td>
            <td class="data-table__cell--number results-table__result"><MoneyAmount :cents="row.result" signed /></td>
        </tr>
        <template v-if="total" #foot>
            <tr>
                <td>Total</td>
                <td class="data-table__cell--number">{{ total.orderCount }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total.turnover" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total.costOfGoods" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total.expenses" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total.urssaf" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total.result" signed /></td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.results-table__row--empty { color: var(--color-subtle); }
.results-table__result { font-weight: 600; }
</style>
