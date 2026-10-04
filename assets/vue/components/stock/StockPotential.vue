<script setup>
import { useI18n } from 'vue-i18n';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    potential: { type: Object, required: true },
    detailed: { type: Boolean, default: false },
    withNote: { type: Boolean, default: true },
});
const { t } = useI18n();
</script>

<template>
    <dl :class="['stock-potential', { 'stock-potential--detailed': detailed }]">
        <div class="stock-potential__figure">
            <dt>{{ t('stock.potential.units') }}</dt>
            <dd>{{ potential.units }}</dd>
        </div>
        <div class="stock-potential__figure">
            <dt>{{ t('stock.potential.turnover') }}</dt>
            <dd><MoneyAmount :cents="potential.turnover" /></dd>
        </div>
        <template v-if="detailed">
            <div class="stock-potential__figure">
                <dt>{{ t('stock.potential.urssaf') }}</dt>
                <dd>− <MoneyAmount :cents="potential.urssaf" /></dd>
            </div>
            <div class="stock-potential__figure">
                <dt>{{ t('stock.potential.stockCost') }}</dt>
                <dd>− <MoneyAmount :cents="potential.stockCost" /></dd>
            </div>
        </template>
        <div class="stock-potential__figure stock-potential__figure--result">
            <dt>{{ t('stock.potential.revenue') }}</dt>
            <dd><MoneyAmount :cents="potential.revenue" signed /></dd>
        </div>
    </dl>
    <p v-if="withNote" class="stock-potential__note">{{ t('stock.potential.note') }}</p>
</template>

<style scoped>
.stock-potential { display: flex; flex-wrap: wrap; gap: var(--space-2) var(--space-6); margin: 0; }
.stock-potential__figure { display: flex; flex-direction: column; gap: 0.125rem; }
.stock-potential__figure dt { color: var(--color-muted); font-size: var(--font-size-sm); }
.stock-potential__figure dd { margin: 0; font-weight: 600; font-variant-numeric: tabular-nums; }
.stock-potential__figure--result dd { color: var(--color-accent-strong); }

.stock-potential--detailed { flex-direction: column; flex-wrap: nowrap; gap: var(--space-1); max-width: 24rem; }
.stock-potential--detailed .stock-potential__figure { flex-direction: row; justify-content: space-between; }
.stock-potential--detailed .stock-potential__figure dt { color: var(--color-text); font-size: var(--font-size); }
.stock-potential--detailed .stock-potential__figure dd { font-weight: 400; }
.stock-potential--detailed .stock-potential__figure--result { padding-top: var(--space-2); border-top: 0.0625rem solid var(--color-border); }
.stock-potential--detailed .stock-potential__figure--result dt,
.stock-potential--detailed .stock-potential__figure--result dd { font-weight: 700; }
.stock-potential__note { margin: var(--space-2) 0 0; color: var(--color-muted); font-size: var(--font-size-sm); }
</style>
