<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { longMonthLabel, monthLabel } from '../../composables/useProductReports.js';

const props = defineProps({
    months: { type: Array, required: true },
});
const { t } = useI18n();
const hovered = ref(null);

const out = (month) => month.sold + month.lost + month.used;
const widest = computed(() => Math.max(1, ...props.months.flatMap((month) => [month.received, out(month)])));
const columns = computed(() => ({ gridTemplateColumns: `repeat(${props.months.length}, minmax(0, 1fr))` }));
const labelled = (index) => props.months.length <= 12 || index % 3 === 0;

const levels = computed(() => props.months.map((month) => month.onHand));
const top = computed(() => Math.max(1, ...levels.value));
const bottom = computed(() => Math.min(0, ...levels.value));
const point = (level, index) => {
    const x = ((index + 0.5) / props.months.length) * 100;
    const y = 100 - ((level - bottom.value) / (top.value - bottom.value || 1)) * 100;
    return `${x},${y}`;
};
const line = computed(() => levels.value.map(point).join(' '));
const zero = computed(() => 100 - ((0 - bottom.value) / (top.value - bottom.value || 1)) * 100);
</script>

<template>
    <figure class="stock-flow" :aria-label="t('reports.flow.title')">
        <p class="stock-flow__legend">
            <span class="stock-flow__swatch stock-flow__swatch--in" aria-hidden="true" />{{ t('reports.flow.in') }}
            <span class="stock-flow__swatch stock-flow__swatch--out" aria-hidden="true" />{{ t('reports.flow.out') }}
        </p>
        <div class="stock-flow__plot" :style="columns">
            <div
                v-for="month in months"
                :key="month.month"
                class="stock-flow__slot"
                tabindex="0"
                :aria-label="`${longMonthLabel(month.month)} : ${t('reports.flow.tooltipIn', { count: month.received })}, ${t('reports.flow.tooltipOut', { sold: month.sold, lost: month.lost, used: month.used })}, ${t('reports.flow.tooltipStock', { month: longMonthLabel(month.month), count: month.onHand })}`"
                @mouseenter="hovered = month.month"
                @mouseleave="hovered = null"
                @focus="hovered = month.month"
                @blur="hovered = null"
            >
                <span class="stock-flow__half stock-flow__half--in">
                    <span v-if="month.received > 0" class="stock-flow__bar stock-flow__bar--in" :style="{ height: `max(0.1875rem, ${(month.received / widest) * 100}%)` }" />
                </span>
                <span class="stock-flow__half stock-flow__half--out">
                    <span v-if="out(month) > 0" class="stock-flow__bar stock-flow__bar--out" :style="{ height: `max(0.1875rem, ${(out(month) / widest) * 100}%)` }" />
                </span>
                <span v-if="hovered === month.month" class="stock-flow__tooltip" role="tooltip">
                    <strong>{{ longMonthLabel(month.month) }}</strong>
                    <span>{{ t('reports.flow.tooltipIn', { count: month.received }) }}</span>
                    <span>{{ t('reports.flow.tooltipOut', { sold: month.sold, lost: month.lost, used: month.used }) }}</span>
                    <span>{{ t('reports.flow.tooltipStock', { month: longMonthLabel(month.month), count: month.onHand }) }}</span>
                </span>
            </div>
        </div>

        <figcaption class="stock-flow__caption">{{ t('reports.flow.stock') }}</figcaption>
        <svg class="stock-flow__level" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <line v-if="bottom < 0" x1="0" x2="100" :y1="zero" :y2="zero" class="stock-flow__zero" vector-effect="non-scaling-stroke" />
            <polyline :points="line" class="stock-flow__line" vector-effect="non-scaling-stroke" />
        </svg>
        <div class="stock-flow__levels" :style="columns" aria-hidden="true">
            <span v-for="(month, index) in months" :key="month.month" :class="{ 'stock-flow__level-value--hovered': hovered === month.month }">{{ hovered === month.month || index === months.length - 1 ? month.onHand : '' }}</span>
        </div>
        <div class="stock-flow__axis" :style="columns" aria-hidden="true">
            <span v-for="(month, index) in months" :key="month.month" :class="{ 'month-label--odd': index % 2 === 1 }">{{ labelled(index) ? monthLabel(month.month) : '' }}</span>
        </div>
    </figure>
</template>

<style scoped>
.stock-flow { display: flex; flex-direction: column; margin: 0; }
.stock-flow__legend { display: flex; align-items: center; gap: var(--space-1) var(--space-2); margin: 0 0 var(--space-2); color: var(--color-muted); font-size: var(--font-size-sm); }
.stock-flow__swatch { display: inline-block; width: 0.75rem; height: 0.75rem; border-radius: 0.125rem; }
.stock-flow__swatch + .stock-flow__swatch { margin-left: var(--space-2); }
.stock-flow__swatch--in { background: var(--color-accent); }
.stock-flow__swatch--out { background: var(--color-chart-out); }
.stock-flow__plot { display: grid; gap: 0.125rem; height: 8rem; }
.stock-flow__slot { position: relative; display: flex; flex-direction: column; border-radius: var(--radius); }
.stock-flow__slot:hover,
.stock-flow__slot:focus-visible { background: var(--color-bg); }
.stock-flow__half { display: flex; flex: 1 1 50%; justify-content: center; }
.stock-flow__half--in { align-items: flex-end; border-bottom: 0.0625rem solid var(--color-border-strong); padding-bottom: 0.0625rem; }
.stock-flow__half--out { align-items: flex-start; padding-top: 0.0625rem; }
.stock-flow__bar { width: min(70%, 1.75rem); }
.stock-flow__bar--in { background: var(--color-accent); border-radius: var(--radius-sm) var(--radius-sm) 0 0; }
.stock-flow__bar--out { background: var(--color-chart-out); border-radius: 0 0 var(--radius-sm) var(--radius-sm); }

.stock-flow__tooltip {
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
    font-size: var(--font-size-sm);
    white-space: nowrap;
    pointer-events: none;
}

.stock-flow__caption { margin: var(--space-4) 0 var(--space-1); color: var(--color-muted); font-size: var(--font-size-sm); }
.stock-flow__level { width: 100%; height: 3rem; overflow: visible; }
.stock-flow__line { fill: none; stroke: var(--color-ink); stroke-width: 2; stroke-linejoin: round; }
.stock-flow__zero { stroke: var(--color-danger); stroke-width: 1; stroke-dasharray: 3 3; }
.stock-flow__levels { display: grid; gap: 0.125rem; min-height: 1rem; font-size: var(--font-size-xs); font-variant-numeric: tabular-nums; text-align: center; }
.stock-flow__level-value--hovered { font-weight: 600; }
.stock-flow__axis { display: grid; gap: 0.125rem; padding-top: var(--space-1); color: var(--color-muted); font-size: var(--font-size-2xs); text-align: center; }

@media (max-width: 40rem) {
    .month-label--odd { visibility: hidden; }
}
</style>
