<script setup>
import { computed, reactive } from 'vue';
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
import { useFormSubmit } from '../../composables/useFormSubmit.js';

const props = defineProps({
    product: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ variant: props.product.variants[0] ?? '', quantity: 1, totalPaid: null });
const { saving, errors, run } = useFormSubmit();

const variantOptions = computed(() => props.product.variants.map((variant) => ({ value: variant, label: variant })));
const unitCost = computed(() => (form.quantity > 0 && form.totalPaid !== null ? Math.round(form.totalPaid / form.quantity) : null));

function onSubmit() {
    return run(async () => {
        await props.submit({
            productId: props.product.id,
            variant: form.variant || null,
            quantity: form.quantity ?? 0,
            totalPaid: form.totalPaid ?? -1,
        });
        emit('saved', { quantity: form.quantity, variant: form.variant || null });
    });
}
</script>

<template>
    <form class="restock-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>

            <FormSection>
                <FormField v-if="product.variants.length" as="group" :label="t('products.restock.variant')" :error="errors.variant">
                    <BaseSelect v-model="form.variant" :options="variantOptions" :aria-label="t('products.restock.variant')" class="control--short" />
                </FormField>
                <FormField as="group" :label="t('products.restock.quantity')" :error="errors.quantity">
                    <BaseNumberField v-model="form.quantity" :min="1" :label="t('products.restock.quantity')" class="control--short" />
                </FormField>
                <FormField :label="t('products.restock.totalPaid')" :error="errors.totalPaid">
                    <BaseMoneyField v-model="form.totalPaid" class="control--short" />
                </FormField>
                <FormField as="group" :label="t('products.restock.unitCost')">
                    <p class="restock-form__fact">
                        <MoneyAmount v-if="unitCost !== null" :cents="unitCost" />
                        <template v-else>—</template>
                    </p>
                </FormField>
            </FormSection>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('common.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.restock.submit') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.restock-form { display: flex; flex-direction: column; gap: var(--space-5); }
.restock-form__fact { margin: 0; padding-top: 0.5625rem; font-size: var(--font-size-md); font-variant-numeric: tabular-nums; color: var(--color-text); }
</style>
