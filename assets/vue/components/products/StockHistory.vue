<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StockBadge from './StockBadge.vue';
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
    await productStock(props.product.id, items);
});
</script>

<template>
    <div class="stock-history">
        <section v-for="item in items ?? []" :key="item.variant ?? ''" class="stock-history__item">
            <header class="stock-history__header">
                <h3 class="stock-history__title">{{ item.variant ?? t('products.stockHistory.onHand') }}</h3>
                <StockBadge :units="item.onHand" :low="item.low" :negative="item.negative" />
                <span class="stock-history__on-hand">{{ t('products.stockHistory.onHandCount', { count: item.onHand }) }}</span>
            </header>
            <EmptyState v-if="item.lots.length === 0">{{ t('products.stockHistory.empty') }}</EmptyState>
            <LotStrip v-else :lots="item.lots" />
            <table v-if="item.lots.length" class="compact-table stock-history__lots">
                <thead>
                    <tr>
                        <th>{{ t('products.stockHistory.date') }}</th>
                        <th>{{ t('products.stockHistory.origin') }}</th>
                        <th class="compact-table__number">{{ t('products.stockHistory.received') }}</th>
                        <th class="compact-table__number">{{ t('products.stockHistory.remaining') }}</th>
                        <th class="compact-table__number">{{ t('products.stockHistory.unitCost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lot in item.lots" :key="lot.id" :class="{ 'stock-history__lot--exhausted': lot.remaining === 0 }">
                        <td>{{ formatDate(lot.receivedAt) }}</td>
                        <td>
                            <a v-if="lot.origin === 'supplier_order' && lot.sourceId" :href="`/supplier-orders/${lot.sourceId}`">{{ lotOriginLabel(lot.origin) }}</a>
                            <a v-else-if="lot.origin === 'return' && lot.sourceId" :href="`/orders/${lot.sourceId}`">{{ lotOriginLabel(lot.origin) }}</a>
                            <template v-else>{{ lotOriginLabel(lot.origin) }}</template>
                        </td>
                        <td class="compact-table__number">{{ lot.quantity }}</td>
                        <td class="compact-table__number">{{ lot.remaining }}</td>
                        <td class="compact-table__number"><MoneyAmount :cents="lot.unitCost" /></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<style scoped>
.stock-history { display: flex; flex-direction: column; gap: var(--space-4); }
.stock-history__header { display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-2); }
.stock-history__title { margin: 0; font-size: var(--font-size); }
.stock-history__on-hand { margin-left: auto; color: var(--color-muted); font-size: var(--font-size-md); }
.stock-history__lots { margin-top: var(--space-3); }
.stock-history__lot--exhausted { color: var(--color-subtle); }
</style>
