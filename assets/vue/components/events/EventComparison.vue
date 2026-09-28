<script setup>
import { toRef } from 'vue';
import DataTable from '../ui/DataTable.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import SortableHeader from '../ui/SortableHeader.vue';
import { formatDate } from '../../composables/useDate.js';
import { formatRatio } from '../../composables/useMoney.js';
import { useSort } from '../../composables/useSort.js';

const props = defineProps({
    events: { type: Array, required: true },
});

const margin = (event) => (event.turnover ? event.result / event.turnover : null);
const perDay = (event) => Math.round(event.result / event.days);

const columns = {
    name: (event) => event.name,
    startDate: (event) => event.startDate,
    days: (event) => event.days,
    orderCount: (event) => event.orderCount,
    turnover: (event) => event.turnover,
    expensesTotal: (event) => event.expensesTotal,
    result: (event) => event.result,
    margin,
    perDay,
};

const { sorted, sortBy, ariaSort } = useSort(toRef(props, 'events'), columns, 'startDate');

const headers = [
    { key: 'orderCount', label: 'Commandes' },
    { key: 'turnover', label: 'CA' },
    { key: 'expensesTotal', label: 'Dépenses' },
    { key: 'result', label: 'Résultat' },
    { key: 'margin', label: 'Marge' },
    { key: 'perDay', label: 'Par jour' },
];
</script>

<template>
    <DataTable :items="sorted" class="event-comparison">
        <template #head>
            <tr>
                <SortableHeader :sort="ariaSort('name')" @sort="sortBy('name')">Événement</SortableHeader>
                <SortableHeader :sort="ariaSort('startDate')" @sort="sortBy('startDate')">Date</SortableHeader>
                <SortableHeader :sort="ariaSort('days')" numeric @sort="sortBy('days')">Jours</SortableHeader>
                <SortableHeader v-for="header in headers" :key="header.key" :sort="ariaSort(header.key)" numeric @sort="sortBy(header.key)">{{ header.label }}</SortableHeader>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="event in rows" :key="event.id">
                <td>
                    <a :href="`/evenements/${event.id}`">{{ event.name }}</a>
                    <span class="event-comparison__location">{{ event.location }}</span>
                </td>
                <td class="event-comparison__date">{{ formatDate(event.startDate) }}</td>
                <td class="data-table__cell--number">{{ event.days }}</td>
                <td class="data-table__cell--number">{{ event.orderCount }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="event.turnover" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="event.expensesTotal" /></td>
                <td class="data-table__cell--number event-comparison__result"><MoneyAmount :cents="event.result" signed :data-test="`event-result-${event.id}`" /></td>
                <td class="data-table__cell--number">{{ formatRatio(event.result, event.turnover) }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="perDay(event)" /></td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.event-comparison__location { display: block; color: var(--color-muted); font-size: 0.8rem; }
.event-comparison__date { white-space: nowrap; }
.event-comparison__result { font-weight: 600; }
</style>
