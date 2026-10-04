<script setup>
import { useI18n } from 'vue-i18n';
import { formatCents, formatRatio } from '../../composables/useMoney.js';

defineProps({
    items: { type: Array, required: true },
    label: { type: String, required: true },
});

const { t } = useI18n();

const keptShare = (item) => (item.turnover > 0 ? Math.max(0, Math.min(1, item.value / item.turnover)) * 100 : 0);
const isLoss = (item) => item.value < 0;
</script>

<template>
    <div class="result-bars">
        <p class="result-bars__legend" aria-hidden="true">
            <span class="result-bars__key result-bars__key--kept">{{ t('reporting.bars.result') }}</span>
            <span class="result-bars__key result-bars__key--costs">{{ t('reporting.bars.costs') }}</span>
            <span class="result-bars__key-note">{{ t('reporting.bars.note') }}</span>
        </p>
        <ul class="result-bars__list" :aria-label="label">
            <li v-for="item in items" :key="item.id" class="result-bars__row">
                <span class="result-bars__name">
                    <a v-if="item.href" :href="item.href">{{ item.label }}</a>
                    <template v-else>{{ item.label }}</template>
                    <span v-if="item.meta" class="result-bars__meta">{{ item.meta }}</span>
                </span>
                <span :class="['track result-bars__track', { 'track--loss': isLoss(item) }]" aria-hidden="true">
                    <span class="result-bars__kept" :style="{ width: `${keptShare(item)}%` }" />
                </span>
                <span class="result-bars__figures">
                    <span :class="['result-bars__value', { 'result-bars__value--loss': isLoss(item) }]">{{ formatCents(item.value) }}</span>
                    <span class="result-bars__ratio">{{ t('reporting.bars.ratio', { ratio: formatRatio(item.value, item.turnover), turnover: formatCents(item.turnover) }) }}</span>
                </span>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.result-bars__legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-1) var(--space-3);
    margin: 0 0 var(--space-2);
    color: var(--color-muted);
    font-size: var(--font-size-sm);
}

.result-bars__key { display: inline-flex; align-items: center; gap: var(--space-1); color: var(--color-text); }
.result-bars__key::before { content: ''; width: 0.625rem; height: 0.625rem; border-radius: 0.125rem; }
.result-bars__key--kept::before { background: var(--color-accent); }
.result-bars__key--costs::before { background: var(--color-border); }

.result-bars__list { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }

.result-bars__row {
    display: grid;
    grid-template-columns: minmax(0, 13rem) minmax(0, 1fr) 8.5rem;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    border-bottom: 0.0625rem solid var(--color-border);
}

.result-bars__row:last-child { border-bottom: none; }

.result-bars__name { display: flex; flex-direction: column; min-width: 0; }
.result-bars__name a { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.result-bars__meta { color: var(--color-muted); font-size: var(--font-size-sm); }

.result-bars__track { height: 0.5rem; }
.result-bars__kept { background: var(--color-accent); }

.result-bars__figures { display: flex; flex-direction: column; align-items: flex-end; font-variant-numeric: tabular-nums; white-space: nowrap; }
.result-bars__value { font-weight: 600; }
.result-bars__value--loss { color: var(--color-danger); }
.result-bars__ratio { color: var(--color-muted); font-size: var(--font-size-sm); }

@media (max-width: 40rem) {
    .result-bars__row { grid-template-columns: minmax(0, 1fr) auto; }
    .result-bars__track { grid-column: 1 / -1; grid-row: 2; }
}
</style>
