<script setup>
import { computed, watch } from 'vue';
import { ToggleGroupItem } from 'reka-ui';
import { ArrowRight } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import ChoiceGroup from '../ui/ChoiceGroup.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { ADJUSTMENT_UNITS, CHANNEL_PRICE_MODES, UNCHANGED, mainChannelOf, previewChannelPrice, priceOn } from '../../composables/useChannelPrices.js';

const props = defineProps({
    channels: { type: Array, required: true },
    products: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
    fixedTarget: { type: Boolean, default: false },
});
const change = defineModel({ type: Object, required: true });
const { t } = useI18n();

const priceLists = computed(() => props.channels.map((channel) => ({ value: channel.id, label: channel.name })));
const channelOf = (id) => props.channels.find((channel) => channel.id === id) ?? null;
const target = computed(() => channelOf(change.value.target));
const mainChannel = computed(() => mainChannelOf(props.channels));
const targetOptions = computed(() => [{ value: UNCHANGED, label: t('products.batch.unchanged') }, ...priceLists.value]);
const sourceOptions = computed(() => priceLists.value.filter((option) => option.value !== change.value.target));
const modeOptions = computed(() => [
    { value: CHANNEL_PRICE_MODES.fixed, label: t('products.channelPrice.modes.fixed') },
    { value: CHANNEL_PRICE_MODES.derived, label: t('products.channelPrice.modes.derived') },
    ...(target.value?.main || !mainChannel.value ? [] : [{ value: CHANNEL_PRICE_MODES.sellingPrice, label: t('products.channelPrice.modes.sellingPrice', { channel: mainChannel.value.name }) }]),
]);

const active = computed(() => change.value.target !== UNCHANGED);
const derived = computed(() => change.value.mode === CHANNEL_PRICE_MODES.derived);
const adjustmentFormat = computed(() => (change.value.unit === ADJUSTMENT_UNITS.percent
    ? { style: 'unit', unit: 'percent', signDisplay: 'exceptZero', maximumFractionDigits: 2 }
    : { style: 'currency', currency: 'EUR', signDisplay: 'exceptZero' }));
const previews = computed(() => props.products.slice(0, 3).map((product) => ({
    id: product.id,
    name: product.displayName,
    before: priceOn(product, target.value),
    after: previewChannelPrice(product, change.value, channelOf(change.value.source)),
})));

watch(() => change.value.target, (targetId) => {
    if (target.value?.main && change.value.mode === CHANNEL_PRICE_MODES.sellingPrice) change.value.mode = CHANNEL_PRICE_MODES.fixed;
    if (change.value.source === null || change.value.source === targetId) change.value.source = sourceOptions.value[0]?.value ?? null;
}, { immediate: true });
</script>

<template>
    <FormSection :title="fixedTarget ? null : t('products.channelPrice.title')" :description="fixedTarget ? null : t('products.channelPrice.intro')">
        <FormField v-if="!fixedTarget" as="group" :label="t('products.channelPrice.target')" :error="errors['channelPrice.channelId']">
            <BaseSelect v-model="change.target" :options="targetOptions" :aria-label="t('products.channelPrice.target')" class="control--medium" />
        </FormField>
        <template v-if="active">
            <FormField as="group" :label="t('products.channelPrice.mode')" :error="errors['channelPrice.mode']">
                <BaseSelect v-model="change.mode" :options="modeOptions" :aria-label="t('products.channelPrice.mode')" class="control--medium" />
            </FormField>
            <FormField v-if="change.mode === CHANNEL_PRICE_MODES.fixed" :label="t('products.channelPrice.newPrice')" :error="errors['channelPrice.price']">
                <BaseMoneyField v-model="change.price" class="control--short" />
            </FormField>
            <template v-if="derived">
                <FormField as="group" :label="t('products.channelPrice.source')" :error="errors['channelPrice.sourceChannelId']">
                    <BaseSelect v-model="change.source" :options="sourceOptions" :aria-label="t('products.channelPrice.source')" class="control--medium" />
                </FormField>
                <FormField as="group" :label="t('products.channelPrice.adjustment')" :hint="t('products.channelPrice.adjustmentHint')">
                    <div class="batch-channel-price__adjustment">
                        <BaseNumberField
                            v-model="change.adjustment"
                            :step="change.unit === ADJUSTMENT_UNITS.percent ? 1 : 0.5"
                            :format-options="adjustmentFormat"
                            :label="t('products.channelPrice.adjustment')"
                            class="control--short"
                        />
                        <ChoiceGroup v-model="change.unit" class="segmented" :aria-label="t('products.channelPrice.unit')">
                            <ToggleGroupItem :value="ADJUSTMENT_UNITS.percent" class="segmented__item">%</ToggleGroupItem>
                            <ToggleGroupItem :value="ADJUSTMENT_UNITS.cents" class="segmented__item">€</ToggleGroupItem>
                        </ChoiceGroup>
                    </div>
                </FormField>
            </template>
            <ul v-if="previews.length" class="batch-channel-price__previews" :aria-label="t('products.channelPrice.preview')">
                <li v-for="preview in previews" :key="preview.id" class="batch-channel-price__preview">
                    <span class="batch-channel-price__name">{{ preview.name }}</span>
                    <MoneyAmount :cents="preview.before" />
                    <ArrowRight size="0.875rem" aria-hidden="true" />
                    <MoneyAmount v-if="preview.after !== null" :cents="preview.after" />
                    <span v-else>—</span>
                </li>
            </ul>
        </template>
    </FormSection>
</template>

<style scoped>
.batch-channel-price__adjustment { display: flex; align-items: center; gap: var(--space-2); }

.batch-channel-price__previews { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: var(--space-2) var(--space-3); list-style: none; border-radius: var(--radius); background: var(--color-bg); font-size: var(--font-size-sm); }
.batch-channel-price__preview { display: flex; align-items: center; gap: var(--space-2); font-variant-numeric: tabular-nums; }
.batch-channel-price__name { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--color-muted); }
</style>
