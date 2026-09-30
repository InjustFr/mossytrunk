<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    line: { type: Object, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const productId = ref('');
const variant = ref('');
const error = ref(null);
const saving = ref(false);

const product = computed(() => props.products.find((candidate) => candidate.id === productId.value) ?? null);
const productOptions = computed(() => props.products.filter((candidate) => !candidate.archived).map((candidate) => ({ value: candidate.id, label: candidate.displayName })));
const variantOptions = computed(() => (product.value?.activeVariants ?? []).map((option) => ({ value: option, label: option })));

watch(productId, () => {
    variant.value = variantOptions.value[0]?.value ?? '';
    error.value = null;
});

async function onSubmit() {
    if (!product.value) {
        error.value = t('orders.identify.chooseProduct');
        return;
    }
    saving.value = true;
    error.value = null;
    try {
        await props.submit({ productId: product.value.id, variant: variantOptions.value.length ? variant.value : null });
        emit('saved', product.value.displayName);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="identify-line-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormSection :description="t('orders.identify.intro')">
                <p class="identify-line-form__line">
                    {{ t('orders.identify.line', { quantity: line.quantity }) }} <MoneyAmount :cents="line.unitPrice" />
                </p>
                <FormField as="group" :label="t('orders.identify.product')" :error="error">
                    <BaseCombobox v-model="productId" :options="productOptions" :placeholder="t('orders.picker.search')" :aria-label="t('orders.identify.product')" />
                </FormField>
                <FormField v-if="variantOptions.length" as="group" :label="t('orders.identify.variant')">
                    <BaseSelect v-model="variant" :options="variantOptions" :aria-label="t('orders.identify.variant')" />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('orders.identify.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('orders.identify.save') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.identify-line-form { display: flex; flex-direction: column; gap: var(--space-5); }
.identify-line-form__line { margin: 0; color: var(--color-ink); font-weight: 500; }
</style>
