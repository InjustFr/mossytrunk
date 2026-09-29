<script setup>
import BaseNumberField from '../ui/BaseNumberField.vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import StatusBadge from '../ui/StatusBadge.vue';

defineProps({
    lines: { type: Array, required: true },
    counts: { type: Object, required: true },
    keyOf: { type: Function, required: true },
});

const difference = (line, count) => (count === null || count === undefined ? null : count - line.onHand);
</script>

<template>
    <EmptyState v-if="lines.length === 0">Aucun article.</EmptyState>
    <DataTable v-else :items="lines" :page-size="50" class="stock-count-table">
        <template #head>
            <tr>
                <th>Article</th>
                <th class="data-table__cell--number">Vendus à l'événement</th>
                <th class="data-table__cell--number">Stock attendu</th>
                <th class="stock-count-table__count">Compté</th>
                <th class="data-table__cell--number">Écart</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="line in rows" :key="keyOf(line)">
                <td>{{ line.label }}</td>
                <td class="data-table__cell--number">{{ line.soldAtEvent || '—' }}</td>
                <td class="data-table__cell--number">{{ line.onHand }}</td>
                <td class="stock-count-table__count">
                    <BaseNumberField v-model="counts[keyOf(line)]" :min="0" :label="`Quantité comptée de ${line.label}`" />
                </td>
                <td class="data-table__cell--number">
                    <template v-if="difference(line, counts[keyOf(line)]) === null">—</template>
                    <StatusBadge v-else-if="difference(line, counts[keyOf(line)]) < 0" tone="danger">{{ difference(line, counts[keyOf(line)]) }}</StatusBadge>
                    <StatusBadge v-else-if="difference(line, counts[keyOf(line)]) > 0" tone="warning">+{{ difference(line, counts[keyOf(line)]) }}</StatusBadge>
                    <StatusBadge v-else tone="success">OK</StatusBadge>
                </td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.stock-count-table__count { width: 10rem; }
.stock-count-table__count :deep(.number-field) { max-width: 10rem; }
</style>
