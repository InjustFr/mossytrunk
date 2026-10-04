<script setup>
import { computed, useSlots } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronRight } from '@lucide/vue';
import { CollapsibleContent, CollapsibleRoot, CollapsibleTrigger } from 'reka-ui';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatRatio } from '../../composables/useMoney.js';

const props = defineProps({
    title: { type: String, required: true },
    turnover: { type: Number, required: true },
    lines: { type: Array, required: true },
    result: { type: Number, required: true },
    resultTest: { type: String, default: null },
});

const { t } = useI18n();
const slots = useSlots();
const hasDetail = (key) => Boolean(slots[`detail-${key}`]);
const expandable = computed(() => props.lines.some((line) => hasDetail(line.key)));
</script>

<template>
    <section :class="['receipt', { 'receipt--expandable': expandable }]" :aria-label="title">
        <div class="receipt__summary">
            <h2 class="receipt__title">{{ title }}</h2>
            <p class="receipt__result" :data-test="resultTest"><MoneyAmount :cents="result" signed /></p>
            <p v-if="turnover > 0" class="receipt__ratio">{{ t('reporting.receipt.ratio', { ratio: formatRatio(result, turnover) }) }}</p>
            <slot name="summary" />
        </div>

        <div class="receipt__ledger">
            <template v-for="line in lines" :key="line.key">
                <component :is="hasDetail(line.key) ? CollapsibleRoot : 'div'" class="receipt__entry" v-bind="hasDetail(line.key) ? { defaultOpen: line.open ?? false } : {}">
                    <component :is="hasDetail(line.key) ? CollapsibleTrigger : 'div'" :class="['receipt__line', { 'receipt__line--toggle': hasDetail(line.key) }]">
                        <ChevronRight v-if="hasDetail(line.key)" class="receipt__chevron" size="1rem" aria-hidden="true" />
                        <span v-else class="receipt__chevron" aria-hidden="true" />
                        <span class="receipt__label">{{ line.label }}<span v-if="line.hint" class="receipt__hint">{{ line.hint }}</span></span>
                        <span class="receipt__sign">{{ line.sign ?? '' }}</span>
                        <MoneyAmount :cents="line.amount" class="receipt__amount" />
                    </component>
                    <CollapsibleContent v-if="hasDetail(line.key)" class="receipt__detail"><slot :name="`detail-${line.key}`" /></CollapsibleContent>
                </component>
            </template>
            <div class="receipt__line receipt__line--result">
                <span class="receipt__chevron" aria-hidden="true" />
                <span class="receipt__label">{{ t('reporting.receipt.result') }}</span>
                <span class="receipt__sign">=</span>
                <MoneyAmount :cents="result" signed class="receipt__amount" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.receipt {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
    gap: var(--space-6);
    padding: var(--space-6);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
}

.receipt__summary { display: flex; flex-direction: column; gap: var(--space-1); }
.receipt__title { margin: 0; font-size: 1.2rem; color: var(--color-muted); }

.receipt__result {
    margin: 0;
    font-family: var(--font-display);
    font-size: 3.25rem;
    line-height: 1.05;
    color: var(--color-ink);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.receipt__ratio { margin: 0 0 var(--space-2); color: var(--color-muted); }
.receipt__summary :deep(p) { margin: 0; }

.receipt__ledger { display: flex; flex-direction: column; align-self: center; }

.receipt__line {
    display: grid;
    grid-template-columns: 0 minmax(0, 1fr) 1.25rem 7.5rem;
    align-items: baseline;
    column-gap: var(--space-2);
    width: 100%;
    padding: var(--space-2) 0;
    border: none;
    border-bottom: 0.0625rem dashed var(--color-border-strong);
    background: none;
    color: inherit;
    font: inherit;
    text-align: left;
}

.receipt--expandable .receipt__line { grid-template-columns: 1rem minmax(0, 1fr) 1.25rem 7.5rem; }

.receipt__line--toggle { cursor: pointer; }
.receipt__line--toggle:hover .receipt__label { text-decoration: underline; text-decoration-color: var(--color-border-strong); text-underline-offset: 0.1875rem; }

.receipt__chevron { align-self: center; color: var(--color-muted); transition: transform var(--transition); }
.receipt__line--toggle[data-state="open"] .receipt__chevron { transform: rotate(90deg); }

.receipt__hint { margin-left: var(--space-2); color: var(--color-muted); font-size: var(--font-size-md); }
.receipt__sign { color: var(--color-muted); text-align: center; }
.receipt__amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

.receipt__detail { padding: var(--space-2) 0 var(--space-4) var(--space-5); border-bottom: 0.0625rem dashed var(--color-border-strong); }

.receipt__line--result {
    margin-top: 0.1875rem;
    padding-top: var(--space-3);
    border-top: 0.1875rem double var(--color-ink);
    border-bottom: none;
    font-size: 1.1rem;
    font-weight: 700;
}

@media (max-width: 56rem) {
    .receipt { grid-template-columns: 1fr; gap: var(--space-4); padding: var(--space-5); }
    .receipt__result { font-size: 2.5rem; }
}
</style>
