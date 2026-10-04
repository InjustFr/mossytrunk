<script setup>
import { computed } from 'vue';
import { NumberFieldInput, NumberFieldRoot } from 'reka-ui';
import { intlLocale } from '../../i18n/locale.js';

const props = defineProps({
    placeholder: { type: String, default: undefined },
    currency: { type: String, default: 'EUR' },
});
const cents = defineModel({ type: Number, default: null });

const format = computed(() => ({ style: 'currency', currency: props.currency }));

const euros = computed({
    get: () => (cents.value === null ? undefined : cents.value / 100),
    set: (value) => { cents.value = Number.isFinite(value) ? Math.round(value * 100) : null; },
});
</script>

<template>
    <NumberFieldRoot v-model="euros" :min="0" :step="0.01" :format-options="format" :locale="intlLocale()" class="money-field">
        <NumberFieldInput class="control money-field__input" :placeholder="placeholder" />
    </NumberFieldRoot>
</template>

<style scoped>
.money-field { width: 100%; }
.money-field__input { font-variant-numeric: tabular-nums; }
</style>
