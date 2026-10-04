<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { RadioGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import RadioCard from '../ui/RadioCard.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { sameVariant } from '../../composables/useVariantStock.js';

const props = defineProps({
    product: { type: Object, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['moved', 'cancel']);
const { t } = useI18n();
const { variantsOf } = useProductTypes();

const form = reactive({ variant: '', mode: 'new', targetProductId: '', newProductName: '', targetVariant: '' });
const errors = ref({});
const saving = ref(false);

const hasVariants = computed(() => props.product.variants.length > 0);
const variantOptions = computed(() => props.product.variants.map((variant) => ({ value: variant, label: variant })));
const productOptions = computed(() => props.products
    .filter((product) => product.id !== props.product.id)
    .map((product) => ({ value: product.id, label: product.displayName })));
const modes = ['new', 'existing'];
const target = computed(() => props.products.find((product) => product.id === form.targetProductId) ?? null);
const targetName = computed(() => (form.mode === 'new' ? form.newProductName.trim() : target.value?.displayName ?? ''));

const lastWord = (name) => name.trim().split(/\s+/).at(-1) ?? '';
const withoutLastWord = (name) => name.trim().split(/\s+/).slice(0, -1).join(' ');

const targetTypeId = computed(() => (form.mode === 'new' ? props.product.typeId : target.value?.typeId) ?? null);
const targetVariants = computed(() => [...new Set([...variantsOf(targetTypeId.value), ...(form.mode === 'existing' ? target.value?.variants ?? [] : [])])]);
const targetVariantOptions = computed(() => [
    { value: '', label: t('products.move.noVariant') },
    ...targetVariants.value.map((variant) => ({ value: variant, label: variant })),
]);
const targetChosen = computed(() => form.mode === 'new' || target.value !== null);

const suggestedVariant = computed(() => {
    if (hasVariants.value) return form.variant;
    const name = props.product.displayName;
    if (form.mode === 'existing' && target.value && name.startsWith(`${target.value.displayName} `)) {
        return name.slice(target.value.displayName.length + 1);
    }
    return lastWord(props.product.name);
});

watch(() => props.product, (product) => {
    const whole = product.variants.length === 0;
    Object.assign(form, {
        variant: product.variants[0] ?? '',
        mode: 'new',
        targetProductId: '',
        newProductName: whole ? withoutLastWord(product.name) : product.name,
    });
    errors.value = {};
}, { immediate: true });

watch([suggestedVariant, targetVariants], ([suggestion, variants]) => {
    form.targetVariant = variants.find((variant) => sameVariant(variant, suggestion)) ?? '';
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            variant: hasVariants.value ? form.variant : null,
            targetProductId: form.mode === 'existing' ? form.targetProductId || null : null,
            newProductName: form.mode === 'new' ? form.newProductName : null,
            targetVariant: form.targetVariant,
        });
        emit('moved', { variant: form.targetVariant, target: targetName.value });
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
    <form class="move-variant-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>

            <FormSection>
                <FormField v-if="hasVariants" as="group" :label="t('products.move.variant')">
                    <BaseSelect v-model="form.variant" :options="variantOptions" :aria-label="t('products.move.variant')" class="move-variant-form__narrow" />
                </FormField>
                <FormField as="group" :label="t('products.move.destination')">
                    <RadioGroupRoot v-model="form.mode" class="move-variant-form__modes" :aria-label="t('products.move.destination')">
                        <RadioCard v-for="mode in modes" :key="mode" :value="mode">
                            <span class="move-variant-form__choice">
                                <span class="move-variant-form__name">{{ t(`products.move.modes.${mode}.label`) }}</span>
                                <span class="move-variant-form__description">{{ t(`products.move.modes.${mode}.description`) }}</span>
                            </span>
                        </RadioCard>
                    </RadioGroupRoot>
                </FormField>
                <FormField v-if="form.mode === 'new'" :label="t('products.move.newProductName')" :error="errors.newProductName">
                    <input v-model="form.newProductName" type="text">
                </FormField>
                <FormField v-else as="group" :label="t('products.move.targetProduct')" :error="errors.targetProductId">
                    <BaseCombobox v-model="form.targetProductId" :options="productOptions" :aria-label="t('products.move.targetProduct')" :placeholder="t('products.move.searchProduct')" />
                </FormField>
                <FormField as="group" :label="t('products.move.targetVariant')" :error="errors.targetVariant" :hint="targetChosen && targetVariants.length === 0 ? t('products.move.noTypeVariants') : null">
                    <BaseSelect v-model="form.targetVariant" :options="targetVariantOptions" :aria-label="t('products.move.targetVariant')" class="move-variant-form__narrow" />
                </FormField>
            </FormSection>

            <FormActions>
                <template #note>
                    <i18n-t :keypath="hasVariants ? 'products.move.summary' : 'products.move.wholeSummary'" scope="global">
                        <template #name>{{ product.displayName }}</template>
                        <template #target><strong>{{ targetName || '…' }}<template v-if="form.targetVariant"> — {{ form.targetVariant }}</template></strong></template>
                    </i18n-t>
                </template>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.move.submit') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.move-variant-form { display: flex; flex-direction: column; gap: var(--space-5); }
.move-variant-form :deep(.move-variant-form__narrow) { max-width: 16rem; }
.move-variant-form strong { color: var(--color-ink); font-weight: 600; }

.move-variant-form__modes { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: var(--space-2); }

.move-variant-form__choice { display: flex; flex-direction: column; gap: 0.125rem; }
.move-variant-form__name { font-size: var(--font-size-md); font-weight: 500; color: var(--color-ink); }
.move-variant-form__description { color: var(--color-muted); font-size: var(--font-size-sm); line-height: 1.35; }
</style>
