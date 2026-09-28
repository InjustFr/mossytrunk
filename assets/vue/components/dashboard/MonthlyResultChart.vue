<script setup>
import { computed, ref } from 'vue';
import { formatCents, formatWholeCents } from '../../composables/useMoney.js';
import { MONTHS } from '../../composables/useDashboard.js';

const props = defineProps({
    months: { type: Array, required: true },
});

const hovered = ref(null);

const scale = computed(() => {
    const values = props.months.map((m) => m.result);
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
    <figure class="monthly-chart" aria-label="Résultat par mois">
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
                >
                    <span class="monthly-chart__value" aria-hidden="true">{{ formatWholeCents(month.result) }}</span>
                </div>
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
.monthly-chart { margin: 0; padding-top: var(--space-5); }

.monthly-chart__plot {
    position: relative;
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: var(--space-2);
    height: 12.5rem;
    margin-bottom: var(--space-5);
}

.monthly-chart__zero { position: absolute; left: 0; right: 0; border-top: 0.0625rem solid var(--color-border-strong); }

.monthly-chart__slot { position: relative; outline: none; border-radius: var(--radius); }
.monthly-chart__slot:hover,
.monthly-chart__slot:focus-visible { background: var(--color-bg); }
.monthly-chart__slot:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }

.monthly-chart__bar { position: absolute; left: 22%; right: 22%; }
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
    grid-template-columns: repeat(12, 1fr);
    gap: var(--space-2);
    text-align: center;
    font-size: 0.75rem;
    color: var(--color-muted);
}
</style>
