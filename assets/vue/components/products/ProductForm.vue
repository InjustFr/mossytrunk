<script setup>
import { computed, reactive, ref, toRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import FormField from '../ui/FormField.vue';
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
const { types, variantsOf, load: loadTypes } = useProductTypes();
const { suggestReference } = useProducts();
const referenceSuggestion = useSuggestion(
    toRef(form, 'reference'),
    () => [form.name, form.typeId],
    ([name, typeId]) => suggestReference(name, typeId || null),
    { follow: props.product === null },
);

const displayName = computed(() => {
    const type = types.value.find((candidate) => candidate.id === form.typeId);
    return [type?.prefixesNames ? type.name : null, form.name.trim()].filter(Boolean).join(' ');
});

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

            <FormField as="group" :label="t('products.form.type')" :error="errors.typeId">
                <TypeSelect v-model="form.typeId" />
            </FormField>

            <FormField :label="t('products.form.name')" :error="errors.name" :hint="displayName ? t('products.form.displayed', { name: displayName }) : t('products.form.nameExample')">
                <input v-model="form.name" type="text" required>
            </FormField>

            <FormField :label="t('products.form.reference')" :error="errors.reference" :hint="t(isEditing ? 'products.form.referenceUnique' : 'products.form.referenceSuggested')">
                <input v-model="form.reference" type="text" maxlength="64" autocomplete="off" @input="referenceSuggestion.edited()">
            </FormField>

            <div class="product-form__row">
                <FormField :label="t('products.form.sellingPrice')" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" />
                </FormField>
                <div class="product-form__cost">
                    <span class="product-form__cost-label">{{ t('products.form.buyingPrice') }}</span>
                    <strong v-if="isEditing && product.buyingPrice > 0"><MoneyAmount :cents="product.buyingPrice" /></strong>
                    <span v-else class="product-form__cost-unknown">{{ t('products.form.notBoughtYet') }}</span>
                    <span class="product-form__cost-hint">{{ t('products.form.buyingPriceHint') }}</span>
                </div>
            </div>

            <FormField as="group" :label="t('products.form.lowStockThreshold')" :error="errors.lowStockThreshold" :hint="t('products.form.lowStockThresholdHint')">
                <BaseNumberField v-model="form.lowStockThreshold" :min="0" :label="t('products.form.lowStockThreshold')" />
            </FormField>

            <FormField as="group" :label="t('products.form.variants')" :error="errors.variants" :hint="t('products.form.variantsHint')">
                <VariantPicker v-model="form.variants" :options="variantsOf(form.typeId)" />
            </FormField>

            <div class="product-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t(isEditing ? 'products.save' : 'products.form.add') }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-form { display: flex; flex-direction: column; gap: var(--space-3); }
.product-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.product-form__cost { display: flex; flex-direction: column; gap: var(--space-1); }
.product-form__cost-label { font-weight: 500; }
.product-form__cost-unknown { color: var(--color-muted); }
.product-form__cost-hint { color: var(--color-muted); font-size: 0.8rem; }
.product-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.product-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
