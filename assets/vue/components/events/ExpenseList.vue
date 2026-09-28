<script setup>
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
</script>

<template>
    <EmptyState v-if="expenses.length === 0">Aucune dépense enregistrée.</EmptyState>
    <DataTable v-else :items="expenses" class="expense-list">
        <template #head>
            <tr>
                <th>Libellé</th>
                <th class="data-table__cell--number">Montant</th>
                <th><span class="visually-hidden">Actions</span></th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="expense in rows" :key="expense.id">
                <td>{{ expense.label }}</td>
                <td class="data-table__cell--number"><MoneyAmount :cents="expense.amount" /></td>
                <td class="expense-list__actions">
                    <IconButton :icon="Pencil" :label="`Modifier ${expense.label}`" @click="emit('edit', expense)" />
                    <IconButton :icon="Trash2" :label="`Supprimer ${expense.label}`" variant="danger" @click="emit('remove', expense)" />
                </td>
            </tr>
        </template>
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
