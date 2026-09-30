<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import LotStrip from '../stock/LotStrip.vue';
import { formatDate } from '../../composables/useDate.js';
import { lotOriginLabel, useStock } from '../../composables/useStock.js';

const props = defineProps({
    product: { type: Object, required: true },
});

const items = ref(null);
const { productStock } = useStock();
const { t } = useI18n();

onMounted(async () => {
    items.value = await productStock(props.product.id);
});
</script>

<template>
    <div class="stock-history">
        <section v-for="item in items ?? []" :key="item.variant ?? ''" class="stock-history__item">
            <header class="stock-history__header">
                <h3 class="stock-history__title">{{ item.variant ?? t('products.stockHistory.onHand') }}</h3>
                <StatusBadge v-if="item.negative" tone="danger">{{ t('products.stockHistory.negative') }}</StatusBadge>
                <StatusBadge v-else-if="item.low" tone="warning">{{ t('products.lowStock') }}</StatusBadge>
                <span class="stock-history__on-hand">{{ t('products.stockHistory.onHandCount', { count: item.onHand }) }}</span>
            </header>
            <EmptyState v-if="item.lots.length === 0">{{ t('products.stockHistory.empty') }}</EmptyState>
            <LotStrip v-else :lots="item.lots" />
            <table v-if="item.lots.length" class="stock-history__lots">
                <thead>
                    <tr>
                        <th>{{ t('products.stockHistory.date') }}</th>
                        <th>{{ t('products.stockHistory.origin') }}</th>
                        <th class="stock-history__number">{{ t('products.stockHistory.received') }}</th>
                        <th class="stock-history__number">{{ t('products.stockHistory.remaining') }}</th>
                        <th class="stock-history__number">{{ t('products.stockHistory.unitCost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lot in item.lots" :key="lot.id" :class="{ 'stock-history__lot--exhausted': lot.remaining === 0 }">
                        <td>{{ formatDate(lot.receivedAt) }}</td>
                        <td>
                            <a v-if="lot.origin === 'supplier_order' && lot.sourceId" :href="`/supplier-orders/${lot.sourceId}`">{{ lotOriginLabel(lot.origin) }}</a>
                            <template v-else>{{ lotOriginLabel(lot.origin) }}</template>
                        </td>
                        <td class="stock-history__number">{{ lot.quantity }}</td>
                        <td class="stock-history__number">{{ lot.remaining }}</td>
                        <td class="stock-history__number"><MoneyAmount :cents="lot.unitCost" /></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<style scoped>
.stock-history { display: flex; flex-direction: column; gap: var(--space-4); }
.stock-history__header { display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-2); }
.stock-history__title { margin: 0; font-size: 0.95rem; }
.stock-history__on-hand { margin-left: auto; color: var(--color-muted); font-size: 0.85rem; }
.stock-history__lots { width: 100%; margin-top: var(--space-3); border-collapse: collapse; font-size: 0.85rem; }
.stock-history__lots th { padding: var(--space-1) var(--space-2); border-bottom: 0.0625rem solid var(--color-border); color: var(--color-muted); font-size: 0.7rem; font-weight: 600; letter-spacing: 0.04rem; text-align: left; text-transform: uppercase; }
.stock-history__lots td { padding: var(--space-1) var(--space-2); border-bottom: 0.0625rem solid var(--color-border); }
.stock-history__number { text-align: right; font-variant-numeric: tabular-nums; }
.stock-history__lots th.stock-history__number { text-align: right; }
.stock-history__lot--exhausted { color: var(--color-subtle); }
</style>
