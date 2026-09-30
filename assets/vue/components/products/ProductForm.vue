<script setup>
import { computed, reactive, ref, toRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import FormActions from '../ui/FormActions.vue';
import FormDisclosure from '../ui/FormDisclosure.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import ProductTag from './ProductTag.vue';
import TypeSelect from './TypeSelect.vue';
import VariantPicker from './VariantPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { useProducts } from '../../composables/useProducts.js';
import { useSuggestion } from '../../composables/useSuggestion.js';

const props = defineProps({
    product: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const emptyForm = () => ({ typeId: '', name: '', reference: '', sellingPrice: null, variants: [], lowStockThreshold: 10 });
const form = reactive(emptyForm());
const errors = ref({});
const saving = ref(false);

const isEditing = computed(() => props.product !== null);
const { types, variantsOf, allVariantsOf, load: loadTypes } = useProductTypes();
const { suggestReference } = useProducts();
const referenceSuggestion = useSuggestion(
    toRef(form, 'reference'),
    () => [form.name, form.typeId],
    ([name, typeId]) => suggestReference(name, typeId || null),
    { follow: props.product === null },
);

const type = computed(() => types.value.find((candidate) => candidate.id === form.typeId) ?? null);
const displayName = computed(() => [type.value?.prefixesNames ? type.value.name : null, form.name.trim()].filter(Boolean).join(' '));
const moreOpen = ref(false);
const moreSummary = computed(() => [form.reference, t('products.form.lowStockSummary', { threshold: form.lowStockThreshold ?? 0 })].filter(Boolean).join(', '));

watch(() => props.product, (product) => {
    Object.assign(form, product
        ? {
            typeId: product.typeId ?? '',
            name: product.name,
            reference: product.reference,
            sellingPrice: product.sellingPrice,
            variants: [...product.variants],
            lowStockThreshold: product.lowStockThreshold,
        }
        : emptyForm());
    errors.value = {};
    referenceSuggestion.reset(!product);
}, { immediate: true });

const offeredBy = (typeId, variant) => allVariantsOf(typeId).some((offered) => offered.toLowerCase() === variant.toLowerCase());

watch(() => form.typeId, (typeId, previous) => {
    if (previous) {
        form.variants = form.variants.filter((variant) => offeredBy(typeId, variant));
    }
});

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            typeId: form.typeId || null,
            name: form.name,
            reference: form.reference.trim() === '' && !isEditing.value ? null : form.reference,
            sellingPrice: form.sellingPrice ?? -1,
            variants: form.variants,
            lowStockThreshold: form.lowStockThreshold ?? 0,
        });
        emit('saved', displayName.value);
        await loadTypes();
        if (!isEditing.value) {
            Object.assign(form, emptyForm());
            referenceSuggestion.reset(true);
        }
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
    <form class="product-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="product-form__error" role="alert">{{ errors.form }}</p>

            <ProductTag :name="displayName" :reference="form.reference" :price="form.sellingPrice" :variants="form.variants" :color="type?.color" />

            <FormSection>
                <FormField as="group" :label="t('products.form.type')" :error="errors.typeId">
                    <TypeSelect v-model="form.typeId" />
                </FormField>
                <FormField :label="t('products.form.name')" :error="errors.name">
                    <input v-model="form.name" type="text" required :placeholder="t('products.form.namePlaceholder')">
                </FormField>
                <FormField :label="t('products.form.sellingPrice')" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" class="product-form__price" />
                </FormField>
                <FormField as="group" :label="t('products.form.variants')" :error="errors.variants">
                    <VariantPicker v-model="form.variants" :options="variantsOf(form.typeId)" required :empty="form.typeId ? null : t('products.variantPicker.chooseTypeFirst')" />
                </FormField>
            </FormSection>

            <FormDisclosure v-model:open="moreOpen" :title="t('products.form.more')" :summary="moreSummary">
                <FormField :label="t('products.form.reference')" :error="errors.reference" :hint="isEditing ? null : t('products.form.referenceSuggested')">
                    <input v-model="form.reference" type="text" maxlength="64" autocomplete="off" @input="referenceSuggestion.edited()">
                </FormField>
                <FormField as="group" :label="t('products.form.lowStockThreshold')" :error="errors.lowStockThreshold" :hint="t('products.form.lowStockThresholdHint')">
                    <BaseNumberField v-model="form.lowStockThreshold" :min="0" :label="t('products.form.lowStockThreshold')" class="product-form__threshold" />
                </FormField>
                <FormField as="group" :label="t('products.form.buyingPrice')" :hint="t('products.form.buyingPriceHint')">
                    <p class="product-form__fact">
                        <MoneyAmount v-if="isEditing && product.buyingPrice > 0" :cents="product.buyingPrice" />
                        <template v-else>{{ t('products.form.notBoughtYet') }}</template>
                    </p>
                </FormField>
            </FormDisclosure>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t(isEditing ? 'products.save' : 'products.form.add') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.product-form { display: flex; flex-direction: column; gap: var(--space-5); }
.product-form__price,
.product-form__threshold { max-width: 11rem; }
.product-form__fact { margin: 0; padding-top: 0.5625rem; font-size: 0.875rem; color: var(--color-text); }
.product-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
