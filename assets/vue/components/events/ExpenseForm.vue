<script setup>
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSwitch from '../ui/BaseSwitch.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';

const props = defineProps({
    expense: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const blank = () => ({ label: '', amount: null, shared: false, sharedOverEvents: null, sharedUntil: '' });
const form = reactive(blank());
const errors = ref({});
const saving = ref(false);

watch(() => props.expense, (expense) => {
    Object.assign(form, expense
        ? {
            label: expense.label,
            amount: expense.fullAmount,
            shared: expense.sharedOverEvents !== null || expense.sharedUntil !== null,
            sharedOverEvents: expense.sharedOverEvents,
            sharedUntil: expense.sharedUntil ?? '',
        }
        : blank());
    errors.value = {};
}, { immediate: true });

function payload() {
    return {
        label: form.label,
        amount: form.amount ?? 0,
        sharedOverEvents: form.shared ? form.sharedOverEvents ?? null : null,
        sharedUntil: form.shared && form.sharedUntil ? form.sharedUntil : null,
    };
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit(payload());
        emit('saved', form.label);
        Object.assign(form, blank());
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
                <label class="expense-form__switch"><BaseSwitch v-model="form.shared" /> {{ t('events.expenseForm.shared') }}</label>
                <template v-if="form.shared">
                    <p class="expense-form__hint">{{ t('events.expenseForm.sharedHint') }}</p>
                    <FormField as="group" :label="t('events.expenseForm.sharedOverEvents')" :error="errors.sharedOverEvents">
                        <BaseNumberField v-model="form.sharedOverEvents" :min="2" :label="t('events.expenseForm.sharedOverEvents')" class="expense-form__amount" />
                    </FormField>
                    <FormField as="group" :label="t('events.expenseForm.sharedUntil')" :error="errors.sharedUntil">
                        <BaseDatePicker v-model="form.sharedUntil" :aria-label="t('events.expenseForm.sharedUntil')" class="expense-form__amount" />
                    </FormField>
                </template>
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
.expense-form__switch { display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-size-md); cursor: pointer; }
.expense-form__hint { margin: 0; color: var(--color-muted); font-size: var(--font-size-sm); }
</style>
