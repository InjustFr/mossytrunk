<script setup>
import { computed, ref } from 'vue';
import { formatCents } from '../../composables/useMoney.js';
import { MONTHS } from '../../composables/useDashboard.js';

// One series: the monthly result, as bars from a zero baseline (gains up, losses down).
// Hover or focus a month to read its figures; the table below is the accessible view of the same data.
const props = defineProps({
    months: { type: Array, required: true },
});

const hovered = ref(null);

const scale = computed(() => {
    const values = props.months.map((m) => m.result);
    const max = Math.max(0, ...values);
    const min = Math.min(0, ...values);
    const span = max - min || 1;
    return { max, min, span, zero: (max / span) * 100 };
});

const barStyle = (result) => {
    const { span, zero } = scale.value;
    const height = (Math.abs(result) / span) * 100;
    return result >= 0
        ? { top: `${zero - height}%`, height: `${height}%` }
        : { top: `${zero}%`, height: `${height}%` };
};
</script>

<template>
    <figure class="monthly-chart">
        <figcaption class="monthly-chart__caption">Résultat par mois</figcaption>
        <div class="monthly-chart__plot" role="list">
            <div class="monthly-chart__zero" :style="{ top: `${scale.zero}%` }" aria-hidden="true" />
            <div
                v-for="month in months"
                :key="month.month"
                class="monthly-chart__slot"
                role="listitem"
                tabindex="0"
                :aria-label="`${MONTHS[month.month - 1]} : résultat ${formatCents(month.result)}`"
                @mouseenter="hovered = month"
                @mouseleave="hovered = null"
                @focus="hovered = month"
                @blur="hovered = null"
            >
                <div
                    v-if="month.result !== 0"
                    :class="['monthly-chart__bar', month.result > 0 ? 'monthly-chart__bar--gain' : 'monthly-chart__bar--loss']"
                    :style="barStyle(month.result)"
                />
                <div v-if="hovered === month" class="monthly-chart__tooltip" role="tooltip">
                    <strong>{{ MONTHS[month.month - 1] }}</strong>
                    <span>CA {{ formatCents(month.turnover) }}</span>
                    <span>Résultat {{ formatCents(month.result) }}</span>
                </div>
            </div>
        </div>
        <div class="monthly-chart__axis" aria-hidden="true">
            <span v-for="month in months" :key="month.month">{{ MONTHS[month.month - 1] }}</span>
        </div>
    </figure>
</template>

<style scoped>
.monthly-chart { margin: 0; }
.monthly-chart__caption { margin-bottom: var(--space-3); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.09rem; text-transform: uppercase; color: var(--color-muted); }

.monthly-chart__plot {
    position: relative;
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: var(--space-2);
    height: 12.5rem;
}

.monthly-chart__zero { position: absolute; left: 0; right: 0; border-top: 0.0625rem solid var(--color-border-strong); }

.monthly-chart__slot { position: relative; outline: none; border-radius: var(--radius); }
.monthly-chart__slot:hover,
.monthly-chart__slot:focus-visible { background: #f4f4f1; }

.monthly-chart__bar {
    position: absolute;
    left: 22%;
    right: 22%;
    transition: top var(--transition), height var(--transition);
}

.monthly-chart__bar--gain { background: var(--color-accent); border-radius: 0.25rem 0.25rem 0 0; }
.monthly-chart__bar--loss { background: var(--color-danger); border-radius: 0 0 0.25rem 0.25rem; }

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
    color: #fff;
    font-size: 0.8rem;
    border-radius: var(--radius);
    pointer-events: none;
}

.monthly-chart__axis {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: var(--space-2);
    margin-top: var(--space-2);
    text-align: center;
    font-size: 0.75rem;
    color: var(--color-muted);
}
</style>
