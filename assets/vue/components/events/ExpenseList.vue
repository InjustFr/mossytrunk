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
                <td>
                    {{ expense.label }}
                    <span v-if="expense.sharedBy > 1 || !expense.own" class="expense-list__share">
                        <template v-if="!expense.own">{{ t('events.expenses.sharedFrom') }} <a :href="`/events/${expense.originId}`">{{ expense.originName }}</a> · </template>
                        {{ t('events.expenses.sharedBy', { count: expense.sharedBy }) }} · <MoneyAmount :cents="expense.fullAmount" /> {{ t('events.expenses.inAll') }}
                    </span>
                    <span v-else-if="expense.sharedOverEvents !== null || expense.sharedUntil !== null" class="expense-list__share">{{ t('events.expenses.sharedLater') }}</span>
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="expense.amount" /></td>
                <td class="data-table__cell--actions">
                    <template v-if="expense.own">
                        <IconButton :icon="Pencil" :label="t('events.expenses.edit', { label: expense.label })" @click="emit('edit', expense)" />
                        <IconButton :icon="Trash2" :label="t('events.expenses.remove', { label: expense.label })" variant="danger" @click="emit('remove', expense)" />
                    </template>
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
.expense-list__share { display: block; color: var(--color-muted); font-size: var(--font-size-xs); }
</style>
