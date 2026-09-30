<script setup>
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';

const props = defineProps({
    expense: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

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
            <FormSection>
                <FormField :label="t('events.expenseForm.label')" :error="errors.label">
                    <input v-model="form.label" type="text" :placeholder="t('events.expenseForm.labelPlaceholder')">
                </FormField>
                <FormField :label="t('events.expenseForm.amount')" :error="errors.amount">
                    <BaseMoneyField v-model="form.amount" class="expense-form__amount" />
                </FormField>
            </FormSection>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('events.expenseForm.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ expense ? t('events.expenseForm.save') : t('events.expenseForm.add') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.expense-form { display: flex; flex-direction: column; gap: var(--space-5); }
.expense-form__amount { max-width: 11rem; }
</style>
