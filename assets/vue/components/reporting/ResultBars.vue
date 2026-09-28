<script setup>
import { computed } from 'vue';
import { formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    items: { type: Array, required: true },
    label: { type: String, required: true },
});

const scale = computed(() => {
    const values = props.items.map((item) => item.value);
    const max = Math.max(0, ...values);
    const min = Math.min(0, ...values);
    const span = max - min || 1;
    return { span, zero: (-min / span) * 100 };
});

const barStyle = (value) => {
    const width = (Math.abs(value) / scale.value.span) * 100;
    return value >= 0
        ? { left: `${scale.value.zero}%`, width: `${width}%` }
        : { left: `${scale.value.zero - width}%`, width: `${width}%` };
};
</script>

<template>
    <ul class="result-bars" :aria-label="label">
        <li v-for="item in items" :key="item.id" class="result-bars__row">
            <span class="result-bars__name">
                <a v-if="item.href" :href="item.href">{{ item.label }}</a>
                <template v-else>{{ item.label }}</template>
                <span v-if="item.meta" class="result-bars__meta">{{ item.meta }}</span>
            </span>
            <span class="result-bars__track" aria-hidden="true">
                <span class="result-bars__zero" :style="{ left: `${scale.zero}%` }" />
                <span :class="['result-bars__bar', item.value < 0 ? 'result-bars__bar--loss' : 'result-bars__bar--gain']" :style="barStyle(item.value)" />
            </span>
            <span :class="['result-bars__value', { 'result-bars__value--loss': item.value < 0 }]">{{ formatCents(item.value) }}</span>
        </li>
    </ul>
</template>

<style scoped>
.result-bars { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }

.result-bars__row {
    display: grid;
    grid-template-columns: minmax(0, 13rem) minmax(0, 1fr) 6.5rem;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    border-bottom: 0.0625rem solid var(--color-border);
}

.result-bars__row:last-child { border-bottom: none; }

.result-bars__name { display: flex; flex-direction: column; min-width: 0; }
.result-bars__name a { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.result-bars__meta { color: var(--color-muted); font-size: 0.8rem; }

.result-bars__track { position: relative; height: 0.75rem; }
.result-bars__zero { position: absolute; top: -0.25rem; bottom: -0.25rem; border-left: 0.0625rem solid var(--color-border-strong); }
.result-bars__bar { position: absolute; top: 0; bottom: 0; min-width: 0.125rem; }
.result-bars__bar--gain { background: var(--color-accent); border-radius: 0 0.125rem 0.125rem 0; }
.result-bars__bar--loss { background: var(--color-danger); border-radius: 0.125rem 0 0 0.125rem; }

.result-bars__value { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
.result-bars__value--loss { color: var(--color-danger); }
</style>
