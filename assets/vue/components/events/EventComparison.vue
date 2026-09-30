<script setup>
import { toRef } from 'vue';
import { PackageSearch } from '@lucide/vue';
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
const keptShare = (event) => (event.turnover > 0 ? Math.max(0, Math.min(1, event.result / event.turnover)) * 100 : 0);

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
                    <a :href="`/events/${event.id}`">{{ event.name }}</a>
                    <PackageSearch
                        v-if="event.unexplainedUnits > 0"
                        class="event-comparison__missing"
                        size="0.875rem"
                        role="img"
                        :aria-label="`Commande manquante probable (${event.unexplainedUnits})`"
                    />
                    <span class="event-comparison__location">{{ event.location }}</span>
                </td>
                <td class="event-comparison__date">{{ formatDate(event.startDate) }}</td>
                <td class="data-table__cell--number">{{ event.days }}</td>
                <td class="data-table__cell--number">{{ event.orderCount }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="event.turnover" /></td>
                <td class="data-table__cell--number"><MoneyAmount :cents="event.expensesTotal" /></td>
                <td class="data-table__cell--number event-comparison__result"><MoneyAmount :cents="event.result" signed :data-test="`event-result-${event.id}`" /></td>
                <td class="data-table__cell--number">
                    <span class="event-comparison__margin">
                        <span :class="['event-comparison__track', { 'event-comparison__track--loss': event.result < 0 }]" aria-hidden="true">
                            <span class="event-comparison__kept" :style="{ width: `${keptShare(event)}%` }" />
                        </span>
                        {{ formatRatio(event.result, event.turnover) }}
                    </span>
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="perDay(event)" /></td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.event-comparison__location { display: block; color: var(--color-muted); font-size: 0.8rem; }
.event-comparison__date { white-space: nowrap; }
.event-comparison__result { font-weight: 600; }
.event-comparison__margin { display: inline-flex; align-items: center; gap: var(--space-2); }
.event-comparison__track { display: flex; width: 5rem; height: 0.375rem; overflow: hidden; border-radius: 0.1875rem; background: var(--color-border); }
.event-comparison__track--loss { background: var(--color-danger-soft); box-shadow: inset 0 0 0 0.0625rem var(--color-danger); }
.event-comparison__kept { background: var(--color-accent); }
.event-comparison__missing { margin-left: var(--space-1); color: var(--color-warning); vertical-align: -0.125rem; }
</style>
