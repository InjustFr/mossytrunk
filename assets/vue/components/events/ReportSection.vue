<script setup>
import { ChevronRight } from '@lucide/vue';
import { CollapsibleContent, CollapsibleRoot, CollapsibleTrigger } from 'reka-ui';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    title: { type: String, required: true },
    amount: { type: Number, required: true },
    sign: { type: String, default: '−' },
    open: { type: Boolean, default: false },
});
</script>

<template>
    <CollapsibleRoot class="report-section" :default-open="open">
        <CollapsibleTrigger class="report-section__summary">
            <ChevronRight class="report-section__chevron" size="1rem" aria-hidden="true" />
            <span class="report-section__title">{{ title }}</span>
            <span :class="['report-section__amount', `report-section__amount--${sign === '+' ? 'income' : 'cost'}`]">
                {{ sign }} <MoneyAmount :cents="amount" />
            </span>
        </CollapsibleTrigger>
        <CollapsibleContent class="report-section__body"><slot /></CollapsibleContent>
    </CollapsibleRoot>
</template>

<style scoped>
.report-section { border-bottom: 0.0625rem solid var(--color-border); }

.report-section__summary {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    width: 100%;
    padding: var(--space-3) 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
}

.report-section__summary:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }

.report-section__chevron {
    flex-shrink: 0;
    color: var(--color-muted);
    transition: transform var(--transition);
}

.report-section__summary[data-state="open"] .report-section__chevron { transform: rotate(90deg); }

.report-section__title { flex: 1; }
.report-section__amount { font-variant-numeric: tabular-nums; }
.report-section__amount--income { color: var(--color-success); }
.report-section__amount--cost { color: var(--color-text); }

.report-section__body { padding: 0 0 var(--space-4) var(--space-5); }
</style>
