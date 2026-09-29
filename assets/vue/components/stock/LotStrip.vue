<script setup>
import { computed } from 'vue';
import { formatDate } from '../../composables/useDate.js';
import { formatCents } from '../../composables/useMoney.js';
import { LOT_ORIGINS } from '../../composables/useStock.js';

const props = defineProps({
    lots: { type: Array, required: true },
});

const oldestFirst = computed(() => [...props.lots].reverse().filter((lot) => lot.remaining > 0));
const total = computed(() => oldestFirst.value.reduce((sum, lot) => sum + lot.remaining, 0));
const describe = (lot) => `${lot.remaining} × ${formatCents(lot.unitCost)} — ${LOT_ORIGINS[lot.origin]} du ${formatDate(lot.receivedAt)}`;
</script>

<template>
    <div v-if="total > 0" class="lot-strip">
        <ol class="lot-strip__bar" aria-label="Stock restant, du plus ancien au plus récent">
            <li
                v-for="(lot, index) in oldestFirst"
                :key="lot.id"
                :class="['lot-strip__lot', { 'lot-strip__lot--next': index === 0 }]"
                :style="{ flexGrow: lot.remaining }"
                :title="describe(lot)"
            >
                <span class="lot-strip__quantity">{{ lot.remaining }}</span>
                <span class="lot-strip__cost">{{ formatCents(lot.unitCost) }}</span>
            </li>
        </ol>
        <p class="lot-strip__legend"><span>Vendu en premier</span><span>Reçu en dernier</span></p>
    </div>
</template>

<style scoped>
.lot-strip__bar { display: flex; gap: 0.125rem; margin: 0; padding: 0; list-style: none; }

.lot-strip__lot {
    display: flex;
    flex-basis: 0;
    flex-direction: column;
    justify-content: center;
    min-width: 3.5rem;
    padding: var(--space-1) var(--space-2);
    border: 0.0625rem solid var(--color-border-strong);
    background: var(--color-surface);
    font-variant-numeric: tabular-nums;
}

.lot-strip__lot:first-child { border-radius: var(--radius) 0 0 var(--radius); }
.lot-strip__lot:last-child { border-radius: 0 var(--radius) var(--radius) 0; }
.lot-strip__lot:only-child { border-radius: var(--radius); }
.lot-strip__lot--next { border-color: var(--color-accent); background: var(--color-accent-soft); }
.lot-strip__quantity { font-weight: 600; }
.lot-strip__cost { color: var(--color-muted); font-size: 0.75rem; }
.lot-strip__legend { display: flex; justify-content: space-between; margin: var(--space-1) 0 0; color: var(--color-subtle); font-size: 0.72rem; }
</style>
