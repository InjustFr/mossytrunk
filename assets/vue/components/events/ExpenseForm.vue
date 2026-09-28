<script setup>
import { reactive, ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import { eurosToCents } from '../../composables/useMoney.js';

const props = defineProps({
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const form = reactive({ label: '', amount: '' });
const errors = ref({});
const saving = ref(false);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ label: form.label, amount: eurosToCents(form.amount) ?? 0 });
        emit('saved', form.label);
        Object.assign(form, { label: '', amount: '' });
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
                <input v-model="form.amount" type="text" inputmode="decimal">
            </FormField>
            <div class="expense-form__actions">
                <BaseButton type="submit" :loading="saving">Ajouter la dépense</BaseButton>
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.expense-form { display: flex; flex-direction: column; gap: var(--space-3); }
.expense-form__actions { display: flex; gap: var(--space-2); }
</style>
