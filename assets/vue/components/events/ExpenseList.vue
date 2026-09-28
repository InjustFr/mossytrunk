<script setup>
import BaseButton from '../ui/BaseButton.vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    expenses: { type: Array, required: true },
    total: { type: Number, required: true },
});
const emit = defineEmits(['edit', 'remove']);
</script>

<template>
    <EmptyState v-if="expenses.length === 0">Aucune dépense enregistrée.</EmptyState>
    <DataTable v-else class="expense-list">
        <template #head>
            <tr>
                <th>Libellé</th>
                <th class="data-table__cell--number">Montant</th>
                <th><span class="visually-hidden">Actions</span></th>
            </tr>
        </template>
        <tr v-for="expense in expenses" :key="expense.id">
            <td>{{ expense.label }}</td>
            <td class="data-table__cell--number"><MoneyAmount :cents="expense.amount" /></td>
            <td class="expense-list__actions">
                <BaseButton variant="ghost" :aria-label="`Modifier ${expense.label}`" @click="emit('edit', expense)">Modifier</BaseButton>
                <BaseButton variant="ghost" :aria-label="`Supprimer ${expense.label}`" @click="emit('remove', expense)">Supprimer</BaseButton>
            </td>
        </tr>
        <template #foot>
            <tr>
                <td>Total</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="total" /></td>
                <td />
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.expense-list__actions { text-align: right; white-space: nowrap; }
</style>
