<script setup>
import { computed } from 'vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import ResultReceipt from '../reporting/ResultReceipt.vue';
import OrderRecap from './OrderRecap.vue';
import { plural } from '../../composables/usePlural.js';

const props = defineProps({
    report: { type: Object, required: true },
    eventId: { type: String, required: true },
    upcoming: { type: Boolean, default: false },
    typeColors: { type: Map, required: true },
});

const rate = (value) => `${String(value).replace('.', ',')} %`;

const lines = computed(() => [
    { key: 'turnover', label: "Chiffre d'affaires", amount: props.report.total.turnover, open: props.report.orders.count > 0 },
    { key: 'costOfGoods', label: "Coût d'achat", amount: props.report.total.costOfGoods, sign: '−' },
    { key: 'expenses', label: 'Dépenses', amount: props.report.total.expenses, sign: '−' },
    { key: 'urssaf', label: 'URSSAF', hint: rate(props.report.urssaf.rate), amount: props.report.total.urssaf, sign: '−' },
]);
</script>

<template>
    <section v-if="upcoming && report.orders.count === 0" class="event-report event-report--upcoming" aria-label="Dépenses engagées">
        <h2 class="event-report__title">Dépenses engagées</h2>
        <p class="event-report__committed"><MoneyAmount :cents="report.total.expenses" /></p>
        <p class="event-report__summary">Pas encore de ventes. Le bilan se remplira avec les commandes de l'événement.</p>
        <div class="event-report__aside"><slot name="aside" /></div>
    </section>
    <ResultReceipt
        v-else
        class="event-report"
        title="Résultat"
        :turnover="report.total.turnover"
        :lines="lines"
        :result="report.total.result"
        result-test="event-result"
    >
        <template #summary>
            <p class="event-report__summary">
                <template v-if="report.orders.count > 0">{{ plural(report.orders.count, 'commande') }}</template>
                <template v-else>Aucune commande.</template>
            </p>
            <div class="event-report__aside"><slot name="aside" /></div>
        </template>

        <template #detail-turnover>
            <dl class="event-report__figures">
                <div class="event-report__line"><dt>Ventes brutes</dt><dd><MoneyAmount :cents="report.orders.grossSales" /></dd></div>
                <div class="event-report__line"><dt>Remises accordées</dt><dd><MoneyAmount :cents="report.orders.discounts ? -report.orders.discounts : 0" /></dd></div>
            </dl>
            <OrderRecap :groups="report.orders.groups" :type-colors="typeColors" />
            <p class="event-report__more"><a :href="`/commandes?event=${eventId}`">Voir les commandes</a></p>
        </template>
    </ResultReceipt>
</template>

<style scoped>
.event-report__summary { margin: 0; color: var(--color-muted); }
.event-report__aside { margin-top: var(--space-6); }
.event-report__aside:empty { display: none; }

.event-report--upcoming {
    padding: var(--space-6);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
}

.event-report__title { margin: 0; font-size: 1.2rem; color: var(--color-muted); }
.event-report__committed { margin: var(--space-1) 0; font-family: var(--font-display); font-size: 2.5rem; line-height: 1.1; }

.event-report__figures { display: flex; flex-direction: column; gap: var(--space-1); margin: 0 0 var(--space-2); padding: 0; }
.event-report__line { display: flex; justify-content: space-between; gap: var(--space-3); font-size: 0.9rem; }
.event-report__line dd { margin: 0; font-variant-numeric: tabular-nums; }

.event-report__more { margin: var(--space-2) 0 0; font-size: 0.9rem; }
</style>
