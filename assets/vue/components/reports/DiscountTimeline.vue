<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatDate } from '../../composables/useDate.js';
import { formatCents } from '../../composables/useMoney.js';
import { formatShare, longMonthLabel, monthLabel } from '../../composables/useProductReports.js';

const props = defineProps({
    months: { type: Array, required: true },
    discounts: { type: Array, required: true },
    period: { type: Object, required: true },
    discountedDays: { type: Number, required: true },
});
const { t } = useI18n();
const hovered = ref(null);

const DAY = 86_400_000;
const start = computed(() => Date.parse(`${props.period.from}T00:00:00Z`));
const offset = (day) => (Date.parse(`${day}T00:00:00Z`) - start.value) / DAY;
const span = (discount) => ({
    left: `${(offset(discount.from) / props.period.days) * 100}%`,
    width: `max(0.25rem, ${(discount.days / props.period.days) * 100}%)`,
});

const given = computed(() => props.months.reduce((sum, month) => sum + month.gross - month.revenue, 0));
const share = (month) => (month.gross > 0 ? (month.gross - month.revenue) / month.gross : 0);
const deepest = computed(() => Math.max(0.01, ...props.months.map(share)));
const columns = computed(() => ({ gridTemplateColumns: `repeat(${props.months.length}, minmax(0, 1fr))` }));
const labelled = (index) => props.months.length <= 12 || index % 3 === 0;
</script>

<template>
    <div class="discount-timeline">
        <p class="discount-timeline__summary">
            {{ discounts.length || given > 0 ? t('reports.discounts.covered', { days: discountedDays, total: period.days, amount: formatCents(given) }) : t('reports.discounts.none') }}
        </p>

        <ul v-if="discounts.length" class="discount-timeline__rules">
            <li v-for="discount in discounts" :key="discount.id" class="discount-timeline__rule">
                <span class="discount-timeline__name">{{ discount.name }}</span>
                <span class="discount-timeline__lane">
                    <span class="discount-timeline__span" :style="span(discount)" :title="t('reports.discounts.rule', { name: discount.name, from: formatDate(discount.from), to: formatDate(discount.to) })" />
                </span>
            </li>
        </ul>

        <div class="discount-timeline__shares">
            <span class="discount-timeline__name">{{ t('reports.discounts.share') }}</span>
            <div class="discount-timeline__cells" :style="columns">
                <span
                    v-for="month in months"
                    :key="month.month"
                    class="discount-timeline__cell"
                    tabindex="0"
                    :style="{ '--depth': share(month) / deepest }"
                    :aria-label="t('reports.discounts.shareTooltip', { month: longMonthLabel(month.month), share: formatShare(month.gross - month.revenue, month.gross) })"
                    @mouseenter="hovered = month.month"
                    @mouseleave="hovered = null"
                    @focus="hovered = month.month"
                    @blur="hovered = null"
                >
                    <span v-if="hovered === month.month" class="discount-timeline__tooltip" role="tooltip">{{ t('reports.discounts.shareTooltip', { month: longMonthLabel(month.month), share: formatShare(month.gross - month.revenue, month.gross) }) }}</span>
                </span>
            </div>
        </div>
        <div class="discount-timeline__axis">
            <span />
            <div class="discount-timeline__months" :style="columns" aria-hidden="true">
                <span v-for="(month, index) in months" :key="month.month" :class="{ 'month-label--odd': index % 2 === 1 }">{{ labelled(index) ? monthLabel(month.month) : '' }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.discount-timeline { display: flex; flex-direction: column; gap: var(--space-2); }
.discount-timeline__summary { margin: 0 0 var(--space-2); font-size: 0.9375rem; }
.discount-timeline__rules { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }

.discount-timeline__rule,
.discount-timeline__shares,
.discount-timeline__axis { display: grid; grid-template-columns: minmax(0, 9rem) minmax(0, 1fr); align-items: center; gap: var(--space-3); }

.discount-timeline__name { overflow: hidden; color: var(--color-muted); font-size: 0.8125rem; text-overflow: ellipsis; white-space: nowrap; }
.discount-timeline__lane { position: relative; height: 0.75rem; border-radius: 0.25rem; background: var(--color-bg); }

.discount-timeline__span {
    position: absolute;
    top: 0;
    bottom: 0;
    border-radius: 0.25rem;
    background: repeating-linear-gradient(135deg, var(--color-accent) 0 0.125rem, var(--color-accent-soft) 0.125rem 0.3125rem);
    box-shadow: inset 0 0 0 0.0625rem var(--color-accent);
}

.discount-timeline__cells,
.discount-timeline__months { display: grid; gap: 0.125rem; }

.discount-timeline__cell {
    position: relative;
    height: 1.25rem;
    border-radius: 0.1875rem;
    background: color-mix(in srgb, var(--color-accent) calc(var(--depth) * 85%), var(--color-bg));
}

.discount-timeline__tooltip {
    position: absolute;
    bottom: calc(100% + var(--space-1));
    left: 50%;
    z-index: 3;
    padding: var(--space-2) var(--space-3);
    transform: translateX(-50%);
    border-radius: var(--radius);
    background: var(--color-ink);
    color: var(--color-surface);
    font-size: 0.8rem;
    white-space: nowrap;
    pointer-events: none;
}

.discount-timeline__months { color: var(--color-muted); font-size: 0.6875rem; text-align: center; }

@media (max-width: 40rem) {
    .month-label--odd { visibility: hidden; }

    .discount-timeline__rule,
    .discount-timeline__shares,
    .discount-timeline__axis { grid-template-columns: minmax(0, 1fr); gap: var(--space-1); }
}
</style>
