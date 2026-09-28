<script setup>
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import ReportSection from './ReportSection.vue';

// Event profitability, in the order Dépenses → Commandes → URSSAF → Total.
defineProps({
    report: { type: Object, required: true },
    eventId: { type: String, required: true },
});

const rate = (value) => `${String(value).replace('.', ',')} %`;
</script>

<template>
    <div class="event-report">
        <ReportSection title="Dépenses" :amount="report.expenses.total" sign="−">
            <EmptyState v-if="report.expenses.items.length === 0">Aucune dépense.</EmptyState>
            <ul v-else class="event-report__list">
                <li v-for="(expense, index) in report.expenses.items" :key="index" class="event-report__line">
                    <span>{{ expense.label }}</span><MoneyAmount :cents="expense.amount" />
                </li>
            </ul>
        </ReportSection>

        <ReportSection title="Commandes" :amount="report.orders.turnover" sign="+" open>
            <dl class="event-report__figures">
                <div class="event-report__line"><dt>{{ report.orders.count }} commande(s) — ventes brutes</dt><dd><MoneyAmount :cents="report.orders.grossSales" /></dd></div>
                <div class="event-report__line"><dt>Remises accordées</dt><dd>− <MoneyAmount :cents="report.orders.discounts" /></dd></div>
                <div class="event-report__line event-report__line--strong"><dt>Chiffre d'affaires</dt><dd><MoneyAmount :cents="report.orders.turnover" /></dd></div>
                <div class="event-report__line"><dt>Coût d'achat des articles vendus</dt><dd><MoneyAmount :cents="report.orders.costOfGoods" /></dd></div>
            </dl>

            <DataTable v-if="report.orders.products.length > 0" class="event-report__products">
                <template #head>
                    <tr>
                        <th>Produit</th>
                        <th class="data-table__cell--number">Qté</th>
                        <th class="data-table__cell--number">Ventes</th>
                        <th class="data-table__cell--number">Coût d'achat</th>
                    </tr>
                </template>
                <tr v-for="product in report.orders.products" :key="product.label">
                    <td>{{ product.label }}</td>
                    <td class="data-table__cell--number">{{ product.quantity }}</td>
                    <td class="data-table__cell--number"><MoneyAmount :cents="product.sales" /></td>
                    <td class="data-table__cell--number">
                        <MoneyAmount :cents="product.cost" />
                        <span v-if="product.unknownCost" class="event-report__warning" title="Prix d'achat non renseigné (0 €)">⚠︎</span>
                    </td>
                </tr>
            </DataTable>
            <p class="event-report__more"><a :href="`/commandes?event=${eventId}`">Voir les commandes</a></p>
        </ReportSection>

        <ReportSection title="URSSAF" :amount="report.urssaf.amount" sign="−">
            <p class="event-report__note">
                {{ rate(report.urssaf.rate) }} du chiffre d'affaires (<MoneyAmount :cents="report.urssaf.base" />).
            </p>
        </ReportSection>

        <div class="event-report__total">
            <dl class="event-report__figures">
                <div class="event-report__line"><dt>Chiffre d'affaires</dt><dd><MoneyAmount :cents="report.total.turnover" /></dd></div>
                <div class="event-report__line"><dt>Coût d'achat</dt><dd>− <MoneyAmount :cents="report.total.costOfGoods" /></dd></div>
                <div class="event-report__line"><dt>Dépenses</dt><dd>− <MoneyAmount :cents="report.total.expenses" /></dd></div>
                <div class="event-report__line"><dt>URSSAF</dt><dd>− <MoneyAmount :cents="report.total.urssaf" /></dd></div>
                <div class="event-report__line event-report__line--result">
                    <dt>Résultat</dt>
                    <dd><MoneyAmount :cents="report.total.result" signed data-test="event-result" /></dd>
                </div>
            </dl>
        </div>
    </div>
</template>

<style scoped>
.event-report { display: flex; flex-direction: column; }

.event-report__list,
.event-report__figures { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }

.event-report__line { display: flex; justify-content: space-between; gap: var(--space-3); }
.event-report__line dd { margin: 0; }
.event-report__line--strong { font-weight: 600; }

.event-report__products { margin-top: var(--space-3); }
.event-report__warning { margin-left: var(--space-1); color: #b7791f; }
.event-report__note { margin: 0; color: var(--color-muted); }
.event-report__more { margin: var(--space-2) 0 0; font-size: 0.9rem; }

.event-report__total { padding-top: var(--space-4); }

.event-report__line--result {
    margin-top: var(--space-2);
    padding: var(--space-3);
    border-radius: var(--radius);
    background: var(--color-accent-soft);
    font-size: 1.2rem;
    font-weight: 700;
}
</style>
