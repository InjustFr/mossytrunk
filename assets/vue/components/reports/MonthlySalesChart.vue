<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import EmptyState from '../ui/EmptyState.vue';
import { formatCents } from '../../composables/useMoney.js';
import { longMonthLabel, monthLabel } from '../../composables/useProductReports.js';

const props = defineProps({
    months: { type: Array, required: true },
});
const { t } = useI18n();
const hovered = ref(null);

const highest = computed(() => Math.max(1, ...props.months.map((month) => month.units)));
const best = computed(() => props.months.reduce((top, month) => (month.units > (top?.units ?? 0) ? month : top), null));
const columns = computed(() => ({ gridTemplateColumns: `repeat(${props.months.length}, minmax(0, 1fr))` }));
const labelled = (index) => props.months.length <= 12 || index % 3 === 0;
</script>

<template>
    <EmptyState v-if="!best">{{ t('reports.sales.none') }}</EmptyState>
    <figure v-else class="sales-chart" :aria-label="t('reports.sales.title')">
        <div class="sales-chart__plot" :style="columns">
            <div
                v-for="month in months"
                :key="month.month"
                class="sales-chart__slot"
                tabindex="0"
                :aria-label="`${longMonthLabel(month.month)} : ${t('reports.sales.tooltip', { units: month.units, revenue: formatCents(month.revenue) })}`"
                @mouseenter="hovered = month.month"
                @mouseleave="hovered = null"
                @focus="hovered = month.month"
                @blur="hovered = null"
            >
                <span v-if="month.units > 0" class="sales-chart__bar" :style="{ height: `max(0.25rem, ${(month.units / highest) * 100}%)` }">
                    <span v-if="month === best" class="sales-chart__value" aria-hidden="true">{{ month.units }}</span>
                </span>
                <span v-if="hovered === month.month" class="sales-chart__tooltip" role="tooltip">
                    <strong>{{ longMonthLabel(month.month) }}</strong>
                    {{ t('reports.sales.tooltip', { units: month.units, revenue: formatCents(month.revenue) }) }}
                </span>
            </div>
        </div>
        <div class="sales-chart__axis" :style="columns" aria-hidden="true">
            <span v-for="(month, index) in months" :key="month.month" :class="{ 'month-label--odd': index % 2 === 1 }">{{ labelled(index) ? monthLabel(month.month) : '' }}</span>
        </div>
    </figure>
</template>

<style scoped>
.sales-chart { margin: 0; padding-top: var(--space-4); }
.sales-chart__plot { position: relative; display: grid; gap: 0.125rem; height: 7.5rem; border-bottom: 0.0625rem solid var(--color-border-strong); }
.sales-chart__slot { position: relative; display: flex; align-items: flex-end; justify-content: center; border-radius: var(--radius) var(--radius) 0 0; outline: none; }
.sales-chart__slot:hover,
.sales-chart__slot:focus-visible { background: var(--color-bg); }
.sales-chart__slot:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.sales-chart__bar { position: relative; width: min(70%, 1.75rem); background: var(--color-accent); border-radius: 0.25rem 0.25rem 0 0; }
.sales-chart__value { position: absolute; bottom: calc(100% + 0.125rem); left: 50%; transform: translateX(-50%); font-size: 0.75rem; font-weight: 600; font-variant-numeric: tabular-nums; }

.sales-chart__tooltip {
    position: absolute;
    bottom: calc(100% + var(--space-1));
    left: 50%;
    z-index: 3;
    display: flex;
    flex-direction: column;
    padding: var(--space-2) var(--space-3);
    transform: translateX(-50%);
    border-radius: var(--radius);
    background: var(--color-ink);
    color: var(--color-surface);
    font-size: 0.8rem;
    white-space: nowrap;
    pointer-events: none;
}

.sales-chart__axis { display: grid; gap: 0.125rem; padding-top: var(--space-1); color: var(--color-muted); font-size: 0.6875rem; text-align: center; }

@media (max-width: 40rem) {
    .month-label--odd { visibility: hidden; }
}
</style>
