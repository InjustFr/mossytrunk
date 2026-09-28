<script setup>
import MoneyAmount from '../ui/MoneyAmount.vue';

// Headline figures of the selected year.
defineProps({
    total: { type: Object, required: true },
});
</script>

<template>
    <dl class="kpi-tiles">
        <div class="kpi-tiles__tile">
            <dt class="kpi-tiles__label">Chiffre d'affaires</dt>
            <dd class="kpi-tiles__value"><MoneyAmount :cents="total.turnover" /></dd>
            <dd class="kpi-tiles__detail">{{ total.orderCount }} commande(s)</dd>
        </div>
        <div class="kpi-tiles__tile">
            <dt class="kpi-tiles__label">Coûts</dt>
            <dd class="kpi-tiles__value"><MoneyAmount :cents="total.costOfGoods + total.expenses" /></dd>
            <dd class="kpi-tiles__detail">Achats <MoneyAmount :cents="total.costOfGoods" /> · Dépenses <MoneyAmount :cents="total.expenses" /></dd>
        </div>
        <div class="kpi-tiles__tile">
            <dt class="kpi-tiles__label">URSSAF</dt>
            <dd class="kpi-tiles__value"><MoneyAmount :cents="total.urssaf" /></dd>
            <dd class="kpi-tiles__detail">12,8 % du CA</dd>
        </div>
        <div class="kpi-tiles__tile kpi-tiles__tile--result">
            <dt class="kpi-tiles__label">Résultat</dt>
            <dd class="kpi-tiles__value"><MoneyAmount :cents="total.result" signed data-test="year-result" /></dd>
        </div>
    </dl>
</template>

<style scoped>
.kpi-tiles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(12.5rem, 1fr));
    gap: var(--space-4);
    margin: 0 0 var(--space-5);
}

.kpi-tiles__tile {
    padding: var(--space-4) var(--space-5);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
}

.kpi-tiles__tile--result { border-left: 0.1875rem solid var(--color-accent); }

.kpi-tiles__label { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.084rem; text-transform: uppercase; color: var(--color-muted); }
.kpi-tiles__value { margin: var(--space-1) 0 0; font-family: var(--font-display); font-size: 1.8rem; line-height: 1.2; }
.kpi-tiles__detail { margin: var(--space-1) 0 0; font-size: 0.85rem; color: var(--color-muted); }
</style>
