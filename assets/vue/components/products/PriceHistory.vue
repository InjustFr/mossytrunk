<script setup>
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDate } from '../../composables/useDate.js';
import { formatSignedCents } from '../../composables/useMoney.js';

defineProps({
    history: { type: Array, required: true },
});
</script>

<template>
    <ol class="price-history">
        <li v-for="(change, index) in history" :key="change.since" :class="['price-history__change', { 'price-history__change--current': index === 0 }]">
            <span class="price-history__price"><MoneyAmount :cents="change.price" /></span>
            <span v-if="history[index + 1]" :class="['price-history__delta', change.price > history[index + 1].price ? 'price-history__delta--up' : 'price-history__delta--down']">
                {{ formatSignedCents(change.price - history[index + 1].price) }}
            </span>
            <span class="price-history__since">{{ index === 0 ? 'depuis le' : 'le' }} {{ formatDate(change.since) }}</span>
        </li>
    </ol>
</template>

<style scoped>
.price-history { display: flex; flex-direction: column; margin: 0; padding: 0 0 0 var(--space-3); border-left: 0.125rem solid var(--color-border); list-style: none; }
.price-history__change { position: relative; display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--space-2); padding: var(--space-2) 0; color: var(--color-muted); }
.price-history__change::before { content: ""; position: absolute; top: 1rem; left: calc(-1 * var(--space-3) - 0.3125rem); width: 0.5rem; height: 0.5rem; border-radius: 50%; background: var(--color-border-strong); }
.price-history__change--current { color: var(--color-ink); }
.price-history__change--current::before { background: var(--color-accent); }
.price-history__change--current .price-history__price { font-family: var(--font-display); font-size: 1.6rem; }
.price-history__price { font-variant-numeric: tabular-nums; }
.price-history__delta { font-size: 0.8rem; font-variant-numeric: tabular-nums; }
.price-history__delta--up { color: var(--color-accent-strong); }
.price-history__delta--down { color: var(--color-danger); }
.price-history__since { width: 100%; font-size: 0.8rem; }
</style>
