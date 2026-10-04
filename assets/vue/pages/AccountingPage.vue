<script setup>
import { computed, onMounted, ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseSelect from '../components/ui/BaseSelect.vue';
import MoneyAmount from '../components/ui/MoneyAmount.vue';
import DeclarationSlip from '../components/accounting/DeclarationSlip.vue';
import OrdersExport from '../components/accounting/OrdersExport.vue';
import PeriodLedger from '../components/accounting/PeriodLedger.vue';
import StockPotential from '../components/stock/StockPotential.vue';
import { periodLabel, useAccounting } from '../composables/useAccounting.js';
import { useToast } from '../composables/useToast.js';

const { t } = useI18n();
const { overview, potential, load, loadPotential, setPeriodicity, declare, withdraw, exportUrl } = useAccounting();
const toast = useToast();

const selectedKey = ref(null);
const saving = ref(false);

const yearOptions = computed(() => (overview.value?.years ?? []).map((year) => ({ value: String(year), label: String(year) })));
const allPeriods = computed(() => [...(overview.value?.pending ?? []), ...(overview.value?.periods ?? [])]);
const defaultPeriod = computed(() => overview.value?.pending[0] ?? overview.value?.periods.find((period) => period.status === 'current') ?? overview.value?.periods[0] ?? null);
const selected = computed(() => allPeriods.value.find((period) => period.key === selectedKey.value) ?? defaultPeriod.value);
const otherPending = computed(() => (overview.value?.pending ?? []).filter((period) => period.key !== selected.value?.key));

async function reload(year = overview.value?.year) {
    await load(year);
}

async function onYear(year) {
    selectedKey.value = null;
    await reload(Number(year));
}

async function onPeriodicity(periodicity) {
    if (!periodicity || periodicity === overview.value.periodicity) return;
    await setPeriodicity(periodicity);
    selectedKey.value = null;
    toast.success(periodicity === 'monthly' ? t('accounting.toast.monthly') : t('accounting.toast.quarterly'));
    await reload();
}

async function onDeclare(period) {
    saving.value = true;
    try {
        await declare(period.key);
        toast.success(t('accounting.toast.declared', { period: periodLabel(period) }));
        selectedKey.value = null;
        await reload();
    } catch (error) {
        toast.error(error.message);
    } finally {
        saving.value = false;
    }
}

async function onWithdraw(period) {
    await withdraw(period.key);
    toast.success(t('accounting.toast.withdrawn', { period: periodLabel(period), periodLower: periodLabel(period).toLowerCase() }));
    await reload();
}

onMounted(() => Promise.all([load(), loadPotential()]));
</script>

<template>
    <AppLayout :title="t('accounting.title')">
        <template #actions>
            <template v-if="overview">
                <ToggleGroupRoot :model-value="overview.periodicity" type="single" class="accounting-page__periodicity" :aria-label="t('accounting.periodicity.label')" @update:model-value="onPeriodicity">
                    <ToggleGroupItem value="monthly" class="accounting-page__periodicity-item">{{ t('accounting.periodicity.monthly') }}</ToggleGroupItem>
                    <ToggleGroupItem value="quarterly" class="accounting-page__periodicity-item">{{ t('accounting.periodicity.quarterly') }}</ToggleGroupItem>
                </ToggleGroupRoot>
                <BaseSelect :model-value="String(overview.year)" :options="yearOptions" :aria-label="t('accounting.year')" size="small" @update:model-value="onYear" />
            </template>
        </template>

        <div v-if="overview" class="accounting-page">
            <section class="accounting-page__urssaf" aria-labelledby="urssaf-title">
                <h2 id="urssaf-title" class="accounting-page__heading">{{ t('accounting.urssaf') }}</h2>
                <p v-if="otherPending.length" class="accounting-page__pending" role="status">
                    {{ t('accounting.pending', otherPending.length) }}
                    <button v-for="period in otherPending" :key="period.key" type="button" class="accounting-page__pending-link" @click="selectedKey = period.key">{{ periodLabel(period) }}</button>
                </p>
                <DeclarationSlip v-if="selected" :period="selected" :rate="overview.rate" :saving="saving" @declare="onDeclare" @withdraw="onWithdraw" />

                <div class="accounting-page__year">
                    <PeriodLedger :periods="overview.periods" :selected-key="selected?.key ?? null" @select="selectedKey = $event.key" />
                    <I18nT keypath="accounting.yearTotal" tag="p" class="accounting-page__year-total">
                        <template #year>{{ overview.year }}</template>
                        <template #turnover><MoneyAmount :cents="overview.yearTurnover" /></template>
                        <template #contribution><MoneyAmount :cents="overview.yearContribution" /></template>
                    </I18nT>
                </div>
            </section>

            <BaseCard v-if="potential" :title="t('stock.potential.title')">
                <StockPotential :potential="potential" detailed />
            </BaseCard>
            <BaseCard :title="t('accounting.export.title')">
                <OrdersExport :url-for="exportUrl" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.accounting-page { display: flex; flex-direction: column; gap: var(--space-6); }
.accounting-page__urssaf { display: flex; flex-direction: column; gap: var(--space-4); }
.accounting-page__heading { margin: 0; font-size: 1.35rem; }
.accounting-page__pending { display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap; margin: 0; color: var(--color-warning); font-size: var(--font-size-md); }
.accounting-page__pending-link { padding: 0; border: none; border-bottom: 0.0625rem dotted currentColor; background: none; color: inherit; font: inherit; cursor: pointer; }
.accounting-page__year { display: flex; flex-direction: column; gap: var(--space-2); }
.accounting-page__year-total { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }

.accounting-page__periodicity { display: inline-flex; padding: 0.125rem; border: 0.0625rem solid var(--color-border-strong); border-radius: var(--radius); background: var(--color-surface); }

.accounting-page__periodicity-item {
    padding: var(--space-1) var(--space-3);
    border: none;
    border-radius: var(--radius-inner);
    background: none;
    color: var(--color-muted);
    font: inherit;
    font-size: var(--font-size-md);
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.accounting-page__periodicity-item:focus-visible { outline-offset: 0.0625rem; }
.accounting-page__periodicity-item[data-state="on"] { background: var(--color-ink); color: var(--color-surface); }
</style>
