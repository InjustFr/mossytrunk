<script setup>
import { computed } from 'vue';
import { NumberFieldInput, NumberFieldRoot } from 'reka-ui';
import { intlLocale } from '../../i18n/locale.js';

defineProps({
    placeholder: { type: String, default: undefined },
});
const cents = defineModel({ type: Number, default: null });

const EUROS = { style: 'currency', currency: 'EUR' };

const euros = computed({
    get: () => (cents.value === null ? undefined : cents.value / 100),
    set: (value) => { cents.value = Number.isFinite(value) ? Math.round(value * 100) : null; },
});
</script>

<template>
    <NumberFieldRoot v-model="euros" :min="0" :step="0.01" :format-options="EUROS" :locale="intlLocale()" class="money-field">
        <NumberFieldInput class="money-field__input" :placeholder="placeholder" />
    </NumberFieldRoot>
</template>

<style scoped>
.money-field { width: 100%; }
.money-field__input { font-variant-numeric: tabular-nums; }
</style>
