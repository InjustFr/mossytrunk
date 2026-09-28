<script setup>
import { ChevronRight } from '@lucide/vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

// One collapsible section of the event report: title + signed amount, details in the slot.
defineProps({
    title: { type: String, required: true },
    amount: { type: Number, required: true },
    // Effect on the result: '+' income, '−' cost.
    sign: { type: String, default: '−' },
    open: { type: Boolean, default: false },
});
</script>

<template>
    <details class="report-section" :open="open">
        <summary class="report-section__summary">
            <ChevronRight class="report-section__chevron" size="1rem" aria-hidden="true" />
            <span class="report-section__title">{{ title }}</span>
            <span :class="['report-section__amount', `report-section__amount--${sign === '+' ? 'income' : 'cost'}`]">
                {{ sign }} <MoneyAmount :cents="amount" />
            </span>
        </summary>
        <div class="report-section__body"><slot /></div>
    </details>
</template>

<style scoped>
.report-section { border-bottom: 0.0625rem solid var(--color-border); }

.report-section__summary {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) 0;
    cursor: pointer;
    list-style: none;
    font-weight: 600;
}

.report-section__summary::-webkit-details-marker { display: none; }

.report-section__chevron {
    flex-shrink: 0;
    color: var(--color-muted);
    transition: transform var(--transition);
}

.report-section[open] .report-section__chevron { transform: rotate(90deg); }

.report-section__title { flex: 1; }
.report-section__amount { font-variant-numeric: tabular-nums; }
.report-section__amount--income { color: var(--color-success); }
.report-section__amount--cost { color: var(--color-text); }

.report-section__body { padding: 0 0 var(--space-4) var(--space-5); }
</style>
