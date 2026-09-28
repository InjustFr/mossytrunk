<script setup>
import { reactive, ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import FormField from '../ui/FormField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { eurosToCents } from '../../composables/useMoney.js';

const props = defineProps({
    expenses: { type: Array, required: true },
    total: { type: Number, required: true },
    add: { type: Function, required: true },
    remove: { type: Function, required: true },
});
const emit = defineEmits(['changed']);

const form = reactive({ label: '', amount: '' });
const errors = ref({});
const saving = ref(false);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.add({ label: form.label, amount: eurosToCents(form.amount) ?? 0 });
        emit('changed', `Dépense « ${form.label} » ajoutée.`);
        Object.assign(form, { label: '', amount: '' });
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { label: error.message };
    } finally {
        saving.value = false;
    }
}

async function onRemove(expense) {
    await props.remove(expense.id);
    emit('changed', `Dépense « ${expense.label} » supprimée.`);
}
</script>

<template>
    <div class="expense-list">
        <EmptyState v-if="expenses.length === 0">Aucune dépense enregistrée.</EmptyState>
        <DataTable v-else>
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
                    <BaseButton variant="ghost" :aria-label="`Supprimer ${expense.label}`" @click="onRemove(expense)">Supprimer</BaseButton>
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

        <form class="expense-list__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <FormField label="Libellé" :error="errors.label">
                    <input v-model="form.label" type="text" placeholder="Stand, train, hôtel…">
                </FormField>
                <FormField label="Montant (€)" :error="errors.amount">
                    <input v-model="form.amount" type="text" inputmode="decimal">
                </FormField>
                <BaseButton type="submit" :loading="saving" class="expense-list__submit">Ajouter la dépense</BaseButton>
            </fieldset>
        </form>
    </div>
</template>

<style scoped>
.expense-list { display: flex; flex-direction: column; gap: var(--space-4); }
.expense-list__actions { text-align: right; }

.expense-list__form {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: var(--space-3);
    align-items: end;
}

@media (max-width: 700px) {
    .expense-list__form { grid-template-columns: 1fr; }
}
</style>
