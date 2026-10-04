<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatCents, formatWholeCents } from '../../composables/useMoney.js';
import { monthName } from '../../composables/useDashboard.js';

const props = defineProps({
    months: { type: Array, required: true },
});

const { t } = useI18n();

const hovered = ref(null);

const active = (month) => month.result !== 0 || month.turnover !== 0;

const visibleMonths = computed(() => {
    const first = props.months.findIndex(active);
    if (first === -1) {
        return props.months;
    }
    const last = props.months.findLastIndex(active);
    return props.months.slice(first, last + 1);
});

const columns = computed(() => ({ gridTemplateColumns: `repeat(${visibleMonths.value.length}, minmax(0, 1fr))` }));

const scale = computed(() => {
    const values = visibleMonths.value.map((m) => m.result);
    const max = Math.max(0, ...values);
    const min = Math.min(0, ...values);
    const span = max - min || 1;
    return { span, zero: (max / span) * 100 };
});

const barStyle = (result) => {
    const { span, zero } = scale.value;
    const height = `max(0.1875rem, ${(Math.abs(result) / span) * 100}%)`;
    return result >= 0
        ? { bottom: `${100 - zero}%`, height }
        : { top: `${zero}%`, height };
};
</script>

<template>
    <figure class="monthly-chart" :aria-label="t('dashboard.chart.label')">
        <div class="monthly-chart__plot" role="list" :style="columns">
            <div class="monthly-chart__zero" :style="{ top: `${scale.zero}%` }" aria-hidden="true" />
            <div
                v-for="month in visibleMonths"
                :key="month.month"
                class="monthly-chart__slot"
                role="listitem"
                tabindex="0"
                :aria-label="t('dashboard.chart.slot', { month: monthName(month.month), result: formatCents(month.result) })"
                @mouseenter="hovered = month"
                @mouseleave="hovered = null"
                @focus="hovered = month"
                @blur="hovered = null"
            >
                <div
                    v-if="month.result !== 0"
                    :class="['monthly-chart__bar', month.result > 0 ? 'monthly-chart__bar--gain' : 'monthly-chart__bar--loss']"
                    :style="barStyle(month.result)"
                >
                    <span class="monthly-chart__value" aria-hidden="true">{{ formatWholeCents(month.result) }}</span>
                </div>
                <div v-if="hovered === month" class="monthly-chart__tooltip" role="tooltip">
                    <strong>{{ monthName(month.month) }}</strong>
                    <span>{{ t('dashboard.chart.turnover', { amount: formatCents(month.turnover) }) }}</span>
                    <span>{{ t('dashboard.chart.result', { amount: formatCents(month.result) }) }}</span>
                </div>
            </div>
        </div>
        <div class="monthly-chart__axis" aria-hidden="true" :style="columns">
            <span v-for="month in visibleMonths" :key="month.month">{{ monthName(month.month) }}</span>
        </div>
    </figure>
</template>

<style scoped>
.monthly-chart { margin: 0; padding-top: var(--space-5); }

.monthly-chart__plot {
    position: relative;
    display: grid;
    gap: var(--space-2);
    height: 12.5rem;
    margin-bottom: var(--space-5);
}

.monthly-chart__zero { position: absolute; left: 0; right: 0; border-top: 0.0625rem solid var(--color-border-strong); }

.monthly-chart__slot { position: relative; border-radius: var(--radius); }
.monthly-chart__slot:hover,
.monthly-chart__slot:focus-visible { background: var(--color-bg); }

.monthly-chart__bar { position: absolute; left: 50%; width: min(56%, 3.5rem); transform: translateX(-50%); }
.monthly-chart__bar--gain { background: var(--color-accent); border-radius: 0.25rem 0.25rem 0 0; }
.monthly-chart__bar--loss { background: var(--color-danger); border-radius: 0 0 0.25rem 0.25rem; }

.monthly-chart__value {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.monthly-chart__bar--gain .monthly-chart__value { bottom: calc(100% + 0.25rem); color: var(--color-ink); }
.monthly-chart__bar--loss .monthly-chart__value { top: calc(100% + 0.25rem); color: var(--color-danger); }

.monthly-chart__tooltip {
    position: absolute;
    bottom: calc(100% + var(--space-1));
    left: 50%;
    z-index: 2;
    display: flex;
    flex-direction: column;
    padding: var(--space-2) var(--space-3);
    transform: translateX(-50%);
    white-space: nowrap;
    background: var(--color-ink);
    color: var(--color-surface);
    font-size: 0.8rem;
    border-radius: var(--radius);
    pointer-events: none;
}

.monthly-chart__axis {
    display: grid;
    gap: var(--space-2);
    text-align: center;
    font-size: 0.75rem;
    color: var(--color-muted);
}
</style>
