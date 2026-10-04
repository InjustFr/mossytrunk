<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    product: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ variant: props.product.variants[0] ?? '', quantity: 1, totalPaid: null });
const errors = ref({});
const saving = ref(false);

const variantOptions = computed(() => props.product.variants.map((variant) => ({ value: variant, label: variant })));
const unitCost = computed(() => (form.quantity > 0 && form.totalPaid !== null ? Math.round(form.totalPaid / form.quantity) : null));

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            productId: props.product.id,
            variant: form.variant || null,
            quantity: form.quantity ?? 0,
            totalPaid: form.totalPaid ?? -1,
        });
        emit('saved', { quantity: form.quantity, variant: form.variant || null });
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { form: error.message };
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="restock-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>

            <FormSection>
                <FormField v-if="product.variants.length" as="group" :label="t('products.restock.variant')" :error="errors.variant">
                    <BaseSelect v-model="form.variant" :options="variantOptions" :aria-label="t('products.restock.variant')" class="restock-form__narrow" />
                </FormField>
                <FormField as="group" :label="t('products.restock.quantity')" :error="errors.quantity">
                    <BaseNumberField v-model="form.quantity" :min="1" :label="t('products.restock.quantity')" class="restock-form__narrow" />
                </FormField>
                <FormField :label="t('products.restock.totalPaid')" :error="errors.totalPaid">
                    <BaseMoneyField v-model="form.totalPaid" class="restock-form__narrow" />
                </FormField>
                <FormField as="group" :label="t('products.restock.unitCost')">
                    <p class="restock-form__fact">
                        <MoneyAmount v-if="unitCost !== null" :cents="unitCost" />
                        <template v-else>—</template>
                    </p>
                </FormField>
            </FormSection>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.restock.submit') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.restock-form { display: flex; flex-direction: column; gap: var(--space-5); }
.restock-form :deep(.restock-form__narrow) { max-width: 11rem; }
.restock-form__fact { margin: 0; padding-top: 0.5625rem; font-size: var(--font-size-md); font-variant-numeric: tabular-nums; color: var(--color-text); }
</style>
