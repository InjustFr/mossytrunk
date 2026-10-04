<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import BatchChannelPrice from './BatchChannelPrice.vue';
import VariantPicker from './VariantPicker.vue';
import { channelPriceChangePayload, emptyChannelPriceChange, mainChannelOf } from '../../composables/useChannelPrices.js';
import { useProductTypes } from '../../composables/useProductTypes.js';

const props = defineProps({
    count: { type: Number, required: true },
    products: { type: Array, required: true },
    channels: { type: Array, default: () => [] },
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

const form = reactive({ sellingPrice: null, typeId: UNCHANGED, addVariants: [], removeVariants: [], lowStockThreshold: null, channelPrice: emptyChannelPriceChange(), priceSince: null });
const errors = ref({});
const saving = ref(false);

const mainChannel = computed(() => mainChannelOf(props.channels));
const priced = computed(() => props.products.some((product) => product.kind !== 'supply'));
const changeType = computed(() => form.typeId !== UNCHANGED);
const union = (lists) => [...new Set(lists.flat())];
const same = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();
const everyProductHas = (variant) => props.products.every((product) => product.variants.some((existing) => same(existing, variant)));
const addable = computed(() => (changeType.value
    ? variantsOf(form.typeId)
    : union(props.products.map((product) => variantsOf(product.typeId)))).filter((variant) => !everyProductHas(variant)));
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
            lowStockThreshold: Number.isFinite(form.lowStockThreshold) ? form.lowStockThreshold : null,
            channelPrice: channelPriceChangePayload(form.channelPrice),
            priceSince: form.priceSince,
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
            <FormError v-if="errors.form">{{ errors.form }}</FormError>

            <FormSection :description="t('products.batch.intro', count)">
                <FormField v-if="priced" :label="mainChannel ? t('products.form.mainPrice', { channel: mainChannel.name }) : t('products.form.sellingPrice')" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" class="product-batch-form__price" />
                </FormField>
                <FormField v-if="priced" as="group" :label="t('products.batch.priceSince')" :error="errors.priceSince" :hint="t('products.batch.priceSinceHint')">
                    <BaseDatePicker v-model="form.priceSince" :aria-label="t('products.batch.priceSince')" class="product-batch-form__since" />
                </FormField>
                <FormField as="group" :label="t('products.form.lowStockThreshold')" :error="errors.lowStockThreshold" :hint="t('products.batch.lowStockThresholdHint')">
                    <BaseNumberField v-model="form.lowStockThreshold" :min="0" :label="t('products.form.lowStockThreshold')" class="product-batch-form__threshold" />
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

            <BatchChannelPrice v-if="priced && channels.length" v-model="form.channelPrice" :channels="channels" :products="products" :errors="errors" />

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.batch.apply', count) }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.product-batch-form { display: flex; flex-direction: column; gap: var(--space-5); }
.product-batch-form__price,
.product-batch-form__since,
.product-batch-form__threshold { max-width: 11rem; }
.product-batch-form :deep(.product-batch-form__type) { max-width: 16rem; }
.product-batch-form :deep(.product-batch-form__type--unchanged) { color: var(--color-muted); }
</style>
