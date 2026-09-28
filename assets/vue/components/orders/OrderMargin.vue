<script setup>
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    total: { type: Number, required: true },
    costOfGoods: { type: Number, required: true },
    margin: { type: Number, required: true },
});
</script>

<template>
    <dl class="order-margin">
        <div class="order-margin__row"><dt>Encaissé</dt><dd><MoneyAmount :cents="total" /></dd></div>
        <div class="order-margin__row"><dt>Coût d'achat</dt><dd>− <MoneyAmount :cents="costOfGoods" /></dd></div>
        <div class="order-margin__row order-margin__row--result"><dt>Marge brute</dt><dd><MoneyAmount :cents="margin" signed /></dd></div>
    </dl>
    <p class="order-margin__note">Avant cotisations URSSAF et dépenses de l'événement.</p>
</template>

<style scoped>
.order-margin { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; }
.order-margin__row { display: flex; justify-content: space-between; }
.order-margin__row dd { margin: 0; }
.order-margin__row--result { padding-top: var(--space-2); border-top: 1px solid var(--color-border); font-weight: 700; }
.order-margin__note { margin: var(--space-2) 0 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
