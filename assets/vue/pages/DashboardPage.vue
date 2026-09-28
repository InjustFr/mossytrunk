<script setup>
import { computed, onMounted } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseSelect from '../components/ui/BaseSelect.vue';
import KpiTiles from '../components/dashboard/KpiTiles.vue';
import MonthlyResultChart from '../components/dashboard/MonthlyResultChart.vue';
import ResultsTable from '../components/dashboard/ResultsTable.vue';
import { MONTHS, useDashboard } from '../composables/useDashboard.js';

const { dashboard, load } = useDashboard();
const yearOptions = computed(() => (dashboard.value?.years ?? []).map((year) => ({ value: year, label: String(year) })));

onMounted(() => load());
</script>

<template>
    <AppLayout title="Tableau de bord">
        <template #actions>
            <div v-if="dashboard" class="dashboard-page__year">
                <span class="eyebrow">Année</span>
                <BaseSelect :model-value="dashboard.year" :options="yearOptions" size="small" aria-label="Année" @update:model-value="load" />
            </div>
        </template>

        <template v-if="dashboard">
            <KpiTiles :total="dashboard.total" />

            <div class="dashboard-page__grid">
                <BaseCard>
                    <MonthlyResultChart :months="dashboard.months" />
                </BaseCard>

                <BaseCard :title="`Résultats par mois — ${dashboard.year}`">
                    <ResultsTable
                        :rows="dashboard.months"
                        label-header="Mois"
                        :label="(row) => MONTHS[row.month - 1]"
                        :total="dashboard.total"
                    />
                    <p class="dashboard-page__note">Les dépenses d'un événement comptent dans le mois où il commence.</p>
                </BaseCard>

                <BaseCard v-if="dashboard.byYear.length" title="Résultats par année">
                    <ResultsTable :rows="dashboard.byYear" label-header="Année" :label="(row) => String(row.year)" />
                </BaseCard>
            </div>
        </template>
    </AppLayout>
</template>

<style scoped>
.dashboard-page__year { display: flex; align-items: center; gap: var(--space-2); }

.dashboard-page__grid { display: flex; flex-direction: column; gap: var(--space-5); }
.dashboard-page__note { margin: var(--space-3) 0 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
