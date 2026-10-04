<script setup>
import { VisuallyHidden } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import { Pencil, Trash2 } from '@lucide/vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    expenses: { type: Array, required: true },
    total: { type: Number, required: true },
});
const emit = defineEmits(['edit', 'remove']);
const { t } = useI18n();
</script>

<template>
    <EmptyState v-if="expenses.length === 0">{{ t('events.expenses.empty') }}</EmptyState>
    <DataTable v-else fit :items="expenses">
        <template #head>
            <tr>
                <th>{{ t('events.expenses.label') }}</th>
                <th class="data-table__cell--number">{{ t('events.expenses.amount') }}</th>
                <th class="data-table__cell--actions"><VisuallyHidden>{{ t('events.expenses.actions') }}</VisuallyHidden></th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="expense in rows" :key="expense.id">
                <td>{{ expense.label }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="expense.amount" /></td>
                <td class="data-table__cell--actions">
                    <IconButton :icon="Pencil" :label="t('events.expenses.edit', { label: expense.label })" @click="emit('edit', expense)" />
                    <IconButton :icon="Trash2" :label="t('events.expenses.remove', { label: expense.label })" variant="danger" @click="emit('remove', expense)" />
                </td>
            </tr>
        </template>
        <template #foot>
            <tr>
                <td>{{ t('events.expenses.total') }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total" /></td>
                <td />
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
</style>
