<script setup>
import { useI18n } from 'vue-i18n';
import { Check } from '@lucide/vue';
import { formatWholeCents } from '../../composables/useMoney.js';
import { periodLabel, periodShortLabel, periodStatusLabel } from '../../composables/useAccounting.js';

defineProps({
    periods: { type: Array, required: true },
    selectedKey: { type: String, default: null },
});
const emit = defineEmits(['select']);
const { t } = useI18n();
</script>

<template>
    <ol :class="['period-ledger', `period-ledger--${periods.length}`]" :aria-label="t('accounting.ledger.label')">
        <li v-for="period in periods" :key="period.key">
            <button
                type="button"
                :class="['period-ledger__cell', `period-ledger__cell--${period.status}`, { 'period-ledger__cell--selected': period.key === selectedKey }]"
                :aria-pressed="period.key === selectedKey"
                :aria-label="t('accounting.ledger.cell', { period: periodLabel(period), amount: formatWholeCents(period.turnover), status: periodStatusLabel(period.status) })"
                @click="emit('select', period)"
            >
                <span class="period-ledger__label">{{ periodShortLabel(period) }}</span>
                <span class="period-ledger__amount">{{ ['upcoming', 'inactive'].includes(period.status) ? '—' : formatWholeCents(period.turnover) }}</span>
                <span class="period-ledger__status">
                    <Check v-if="period.status === 'declared'" size="0.75rem" :stroke-width="3" aria-hidden="true" />
                    {{ periodStatusLabel(period.status) }}
                </span>
            </button>
        </li>
    </ol>
</template>

<style scoped>
.period-ledger { display: grid; gap: 0.125rem; margin: 0; padding: 0; list-style: none; }
.period-ledger--12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
.period-ledger--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

.period-ledger__cell {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    width: 100%;
    height: 100%;
    padding: var(--space-2);
    border: 0.0625rem solid var(--color-border);
    border-top: 0.25rem solid var(--color-border-strong);
    background: var(--color-surface);
    color: var(--color-text);
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.period-ledger li:first-child .period-ledger__cell { border-radius: var(--radius) 0 0 var(--radius); }
.period-ledger li:last-child .period-ledger__cell { border-radius: 0 var(--radius) var(--radius) 0; }
.period-ledger__cell:hover { background: var(--color-bg); }
.period-ledger__cell:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.period-ledger__cell--selected { border-color: var(--color-ink); background: var(--color-bg); }

.period-ledger__cell--declared { border-top-color: var(--color-accent); }
.period-ledger__cell--due,
.period-ledger__cell--changed { border-top-color: var(--color-warning); }
.period-ledger__cell--late { border-top-color: var(--color-danger); }
.period-ledger__cell--current { border-top-color: var(--color-ink); }
.period-ledger__cell--upcoming,
.period-ledger__cell--inactive { color: var(--color-subtle); }

.period-ledger__label { font-size: 0.8rem; color: var(--color-muted); text-transform: capitalize; }
.period-ledger__amount { font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
.period-ledger__status { display: inline-flex; align-items: center; gap: 0.125rem; font-size: 0.72rem; color: var(--color-muted); }
.period-ledger__cell--declared .period-ledger__status { color: var(--color-accent-strong); }
.period-ledger__cell--late .period-ledger__status { color: var(--color-danger); }
.period-ledger__cell--due .period-ledger__status,
.period-ledger__cell--changed .period-ledger__status { color: var(--color-warning); }

@media (max-width: 60rem) {
    .period-ledger--12 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .period-ledger__cell { border-radius: 0 !important; }
}

@media (max-width: 36rem) {
    .period-ledger--12 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .period-ledger--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
