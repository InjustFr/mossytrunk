<script setup>
import { useI18n } from 'vue-i18n';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    total: { type: Number, required: true },
    costOfGoods: { type: Number, required: true },
    suppliesCost: { type: Number, default: 0 },
    margin: { type: Number, required: true },
});

const { t } = useI18n();
</script>

<template>
    <dl class="order-margin">
        <div class="order-margin__row"><dt>{{ t('orders.margin.collected') }}</dt><dd><MoneyAmount :cents="total" /></dd></div>
        <div class="order-margin__row"><dt>{{ t('orders.margin.buyingCost') }}</dt><dd>− <MoneyAmount :cents="costOfGoods" /></dd></div>
        <div v-if="suppliesCost > 0" class="order-margin__row"><dt>{{ t('orders.margin.supplies') }}</dt><dd>− <MoneyAmount :cents="suppliesCost" /></dd></div>
        <div class="order-margin__row order-margin__row--result"><dt>{{ t('orders.margin.gross') }}</dt><dd><MoneyAmount :cents="margin" signed /></dd></div>
    </dl>
    <p class="order-margin__note">{{ t('orders.margin.note') }}</p>
</template>

<style scoped>
.order-margin { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; }
.order-margin__row { display: flex; justify-content: space-between; }
.order-margin__row dd { margin: 0; }
.order-margin__row--result { padding-top: var(--space-2); border-top: 0.0625rem solid var(--color-border); font-weight: 700; }
.order-margin__note { margin: var(--space-2) 0 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
