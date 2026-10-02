<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';

const props = defineProps({
    postage: { type: Number, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const amount = ref(props.postage);
const errors = ref({});
const saving = ref(false);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ postage: amount.value ?? 0 });
        emit('saved');
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="postage-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="postage-form__error" role="alert">{{ errors.form }}</p>
            <FormSection>
                <FormField :label="t('orders.postage.amount')" :error="errors.postage" :hint="t('orders.postage.hint')">
                    <BaseMoneyField v-model="amount" class="postage-form__amount" />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('orders.postage.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('orders.postage.save') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.postage-form { display: flex; flex-direction: column; gap: var(--space-5); }
.postage-form__amount { max-width: 11rem; }
.postage-form__error { margin: 0; padding: var(--space-2) var(--space-3); background: var(--color-danger-soft); color: var(--color-danger); border-radius: var(--radius); }
</style>
