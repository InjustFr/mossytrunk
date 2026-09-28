<script setup>
import { reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    // Expense being edited, or null to add one.
    expense: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const form = reactive({ label: '', amount: null });
const errors = ref({});
const saving = ref(false);

watch(() => props.expense, (expense) => {
    Object.assign(form, expense ? { label: expense.label, amount: expense.amount } : { label: '', amount: null });
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ label: form.label, amount: form.amount ?? 0 });
        emit('saved', form.label);
        Object.assign(form, { label: '', amount: null });
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { label: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="expense-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormField label="Libellé" :error="errors.label">
                <input v-model="form.label" type="text" placeholder="Stand, train, hôtel…">
            </FormField>
            <FormField label="Montant (€)" :error="errors.amount">
                <BaseMoneyField v-model="form.amount" />
            </FormField>
            <div class="expense-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ expense ? 'Enregistrer' : 'Ajouter la dépense' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.expense-form { display: flex; flex-direction: column; gap: var(--space-3); }
.expense-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
