<script setup>
import { computed, onMounted } from 'vue';
import { TriangleAlert } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseSelect from '../components/ui/BaseSelect.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import BestSellers from '../components/dashboard/BestSellers.vue';
import MonthlyResultChart from '../components/dashboard/MonthlyResultChart.vue';
import ResultsTable from '../components/dashboard/ResultsTable.vue';
import ResultBars from '../components/reporting/ResultBars.vue';
import ResultReceipt from '../components/reporting/ResultReceipt.vue';
import { MONTHS, useDashboard } from '../composables/useDashboard.js';
import { formatDate } from '../composables/useDate.js';
import { plural } from '../composables/usePlural.js';

const { dashboard, load } = useDashboard();
const yearOptions = computed(() => (dashboard.value?.years ?? []).map((year) => ({ value: year, label: String(year) })));

const receiptLines = computed(() => {
    const total = dashboard.value.total;
    return [
        { key: 'turnover', label: "Chiffre d'affaires", amount: total.turnover },
        { key: 'costOfGoods', label: "Coût d'achat", amount: total.costOfGoods, sign: '−' },
        { key: 'expenses', label: 'Dépenses', amount: total.expenses, sign: '−' },
        { key: 'urssaf', label: 'URSSAF', hint: '12,8 %', amount: total.urssaf, sign: '−' },
    ];
});

const eventBars = computed(() => dashboard.value.events.map((event) => ({
    id: event.id,
    label: event.name,
    meta: formatDate(event.startDate),
    value: event.result,
    turnover: event.turnover,
    href: `/evenements/${event.id}`,
})));

onMounted(() => load());
</script>

<template>
    <AppLayout title="Tableau de bord">
        <template #actions>
            <BaseSelect v-if="dashboard" :model-value="dashboard.year" :options="yearOptions" size="small" aria-label="Année" @update:model-value="load" />
        </template>

        <div v-if="dashboard" class="dashboard-page">
            <p v-if="dashboard.productsWithoutCost > 0" class="dashboard-page__check" role="status">
                <TriangleAlert size="1rem" aria-hidden="true" />
                <span>
                    {{ plural(dashboard.productsWithoutCost, 'produit n\'a', 'produits n\'ont') }} pas de prix d'achat : leur coût compte pour 0 €, le résultat est donc surestimé.
                    <a href="/produits?prix-achat=manquant">Renseigner les prix d'achat</a>
                </span>
            </p>
            <ResultReceipt :title="`Résultat ${dashboard.year}`" :turnover="dashboard.total.turnover" :lines="receiptLines" :result="dashboard.total.result" result-test="year-result">
                <template #summary>
                    <p class="dashboard-page__summary">
                        {{ plural(dashboard.total.orderCount, 'commande') }} sur {{ plural(dashboard.events.length, 'événement') }}
                    </p>
                </template>
            </ResultReceipt>

            <BaseCard title="Résultat par mois">
                <MonthlyResultChart :months="dashboard.months" />
            </BaseCard>

            <div class="dashboard-page__pair">
                <BaseCard title="Événements de l'année">
                    <ResultBars v-if="eventBars.length" :items="eventBars" label="Résultat par événement, du meilleur au moins bon" />
                    <EmptyState v-else>Aucun événement en {{ dashboard.year }}.</EmptyState>
                </BaseCard>
                <BaseCard title="Meilleures ventes">
                    <BestSellers v-if="dashboard.products.length" :products="dashboard.products" :types="dashboard.types" />
                    <EmptyState v-else>Aucune vente en {{ dashboard.year }}.</EmptyState>
                </BaseCard>
            </div>

            <BaseCard title="Détail par mois">
                <ResultsTable
                    :rows="dashboard.months"
                    label-header="Mois"
                    :label="(row) => MONTHS[row.month - 1]"
                    :total="dashboard.total"
                    collapsible
                />
                <p class="dashboard-page__note">Les dépenses d'un événement comptent dans le mois où il commence.</p>
            </BaseCard>

            <BaseCard v-if="dashboard.byYear.length > 1" title="Comparaison des années">
                <ResultsTable :rows="dashboard.byYear" label-header="Année" :label="(row) => String(row.year)" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard-page { display: flex; flex-direction: column; gap: var(--space-5); }
.dashboard-page__pair { display: grid; grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr)); gap: var(--space-5); align-items: start; }
.dashboard-page__check {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    margin: 0;
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-warning);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-text);
}

.dashboard-page__check svg { flex-shrink: 0; margin-top: 0.1875rem; color: var(--color-warning); }
.dashboard-page__summary { color: var(--color-muted); }
.dashboard-page__note { margin: var(--space-3) 0 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
