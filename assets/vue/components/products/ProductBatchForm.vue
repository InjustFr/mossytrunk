<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import VariantPicker from './VariantPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';

const props = defineProps({
    count: { type: Number, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();
const { activeTypes, variantsOf } = useProductTypes();

const UNCHANGED = '__unchanged__';
const typeOptions = computed(() => [
    { value: UNCHANGED, label: t('products.batch.unchanged') },
    ...activeTypes.value.map((type) => ({ value: type.id, label: type.name })),
]);

const form = reactive({ sellingPrice: null, typeId: UNCHANGED, addVariants: [], removeVariants: [] });
const errors = ref({});
const saving = ref(false);

const changeType = computed(() => form.typeId !== UNCHANGED);
const union = (lists) => [...new Set(lists.flat())];
const addable = computed(() => (changeType.value
    ? variantsOf(form.typeId)
    : union(props.products.map((product) => variantsOf(product.typeId)))));
const removable = computed(() => union(props.products.map((product) => product.variants)));

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        const result = await props.submit({
            sellingPrice: form.sellingPrice,
            changeType: changeType.value,
            typeId: changeType.value ? form.typeId : null,
            addVariants: form.addVariants,
            removeVariants: form.removeVariants,
        });
        emit('saved', result.updated);
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="product-batch-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="product-batch-form__error" role="alert">{{ errors.form }}</p>

            <FormSection :description="t('products.batch.intro', count)">
                <FormField :label="t('products.form.sellingPrice')" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" class="product-batch-form__price" />
                </FormField>
                <FormField as="group" :label="t('products.form.type')" :error="errors.typeId">
                    <BaseSelect v-model="form.typeId" :options="typeOptions" :aria-label="t('products.batch.newType')" :class="['product-batch-form__type', { 'product-batch-form__type--unchanged': !changeType }]" />
                </FormField>
                <FormField as="group" :label="t('products.batch.addVariants')" :error="errors.addVariants">
                    <VariantPicker v-model="form.addVariants" :options="addable" :empty="t('products.batch.noTypeVariants')" />
                </FormField>
                <FormField as="group" :label="t('products.batch.removeVariants')" :error="errors.removeVariants">
                    <VariantPicker v-model="form.removeVariants" :options="removable" :empty="t('products.batch.noProductVariants')" />
                </FormField>
            </FormSection>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.batch.apply', count) }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.product-batch-form { display: flex; flex-direction: column; gap: var(--space-5); }
.product-batch-form__price { max-width: 11rem; }
.product-batch-form :deep(.product-batch-form__type) { max-width: 16rem; }
.product-batch-form :deep(.product-batch-form__type--unchanged) { color: var(--color-muted); }
.product-batch-form__error { margin: 0; padding: var(--space-2) var(--space-3); background: var(--color-danger-soft); color: var(--color-danger); border-radius: var(--radius); }
</style>
