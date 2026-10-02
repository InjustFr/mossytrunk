<script setup>
import { computed } from 'vue';
import { formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    cents: { type: Number, required: true },
    signed: { type: Boolean, default: false },
    currency: { type: String, default: 'EUR' },
});

const formatted = computed(() => formatCents(props.cents, props.currency));
</script>

<template>
    <span :class="['money', 'tabular', { 'money--negative': signed && cents < 0, 'money--positive': signed && cents > 0 }]">{{ formatted }}</span>
</template>

<style scoped>
.money { white-space: nowrap; }
.money--negative { color: var(--color-danger); }
.money--positive { color: var(--color-success); }
</style>
