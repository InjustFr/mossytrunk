<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import DataTable from '../ui/DataTable.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    rows: { type: Array, required: true },
    labelHeader: { type: String, required: true },
    label: { type: Function, required: true },
    total: { type: Object, default: null },
    collapsible: { type: Boolean, default: false },
});

const { t } = useI18n();

const isEmpty = (row) => row.orderCount === 0 && row.expenses === 0;
const showAll = ref(false);
const emptyCount = computed(() => props.rows.filter(isEmpty).length);
const visibleRows = computed(() => (props.collapsible && !showAll.value ? props.rows.filter((row) => !isEmpty(row)) : props.rows));
</script>

<template>
    <div class="results-table">
        <DataTable :items="visibleRows">
            <template #head>
                <tr>
                    <th>{{ labelHeader }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.orders') }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.turnover') }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.costOfGoods') }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.sellingCosts') }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.expenses') }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.urssaf') }}</th>
                    <th class="data-table__cell--number">{{ t('dashboard.table.result') }}</th>
                </tr>
            </template>
            <template #default="{ rows: pageRows }">
                <tr v-for="row in pageRows" :key="label(row)" :class="{ 'results-table__row--empty': isEmpty(row) }">
                    <td>{{ label(row) }}</td>
                    <td class="data-table__cell--number">{{ row.orderCount }}</td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="row.turnover" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="row.costOfGoods" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="row.supplies + row.consumedSupplies + row.channelCosts" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="row.expenses" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="row.urssaf" /></td>
                    <td class="data-table__cell--number results-table__result"><MoneyAmount :cents="row.result" signed /></td>
                </tr>
            </template>
            <template v-if="total" #foot>
                <tr>
                    <td>{{ t('dashboard.table.total') }}</td>
                    <td class="data-table__cell--number">{{ total.orderCount }}</td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="total.turnover" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="total.costOfGoods" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="total.supplies + total.consumedSupplies + total.channelCosts" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="total.expenses" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="total.urssaf" /></td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="total.result" signed /></td>
                </tr>
            </template>
        </DataTable>
        <BaseButton v-if="collapsible && emptyCount > 0" variant="ghost" class="results-table__toggle" @click="showAll = !showAll">
            {{ showAll ? t('dashboard.table.hideEmpty') : t('dashboard.table.showAll', { count: rows.length }) }}
        </BaseButton>
    </div>
</template>

<style scoped>
.results-table__row--empty { color: var(--color-subtle); }
.results-table__result { font-weight: 600; }
.results-table__toggle { margin-top: var(--space-2); }
</style>
