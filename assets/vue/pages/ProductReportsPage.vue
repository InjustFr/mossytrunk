<script setup>
import { computed, nextTick, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseSelect from '../components/ui/BaseSelect.vue';
import ProductPalmares from '../components/reports/ProductPalmares.vue';
import ProductStory from '../components/reports/ProductStory.vue';
import { formatCents } from '../composables/useMoney.js';
import { LAST_TWELVE_MONTHS, SINCE_THE_START, useProductReports } from '../composables/useProductReports.js';

const { t } = useI18n();
const { period, productId, report, story, loadReport, loadStory } = useProductReports();

const periodOptions = computed(() => [
    { value: LAST_TWELVE_MONTHS, label: t('reports.period.last12') },
    ...(report.value?.years ?? []).map((year) => ({ value: String(year), label: t('reports.period.year', { year }) })),
    { value: SINCE_THE_START, label: t('reports.period.all') },
]);
const totals = computed(() => report.value?.totals ?? null);

async function select(product) {
    productId.value = product.id === productId.value ? '' : product.id;
    await loadStory();
    await nextTick();
    if (productId.value && window.matchMedia('(max-width: 64rem)').matches) {
        const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.getElementById('product-story-title')?.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
    }
}

watch(period, () => Promise.all([loadReport(), loadStory()]));
onMounted(() => Promise.all([loadReport(), loadStory()]));
</script>

<template>
    <AppLayout :title="t('reports.title')">
        <template #actions>
            <BaseSelect v-model="period" :options="periodOptions" :aria-label="t('reports.period.label')" class="product-reports__period" />
        </template>

        <div v-if="report" class="product-reports">
            <p class="product-reports__summary">
                <template v-if="totals.units > 0">{{ t('reports.summary', { revenue: formatCents(totals.revenue), units: totals.units, discount: formatCents(totals.discount) }) }}</template>
                <template v-else>{{ t('reports.summaryEmpty') }}</template>
            </p>

            <div v-if="report.products.length" :class="['product-reports__grid', { 'product-reports__grid--story': story }]">
                <ProductPalmares :products="report.products" :selected-id="productId" @select="select" />
                <ProductStory v-if="story" :key="story.product.id" :story="story" class="product-reports__story" @close="productId = ''; loadStory()" />
                <p v-else class="product-reports__hint">{{ t('reports.story.choose') }}</p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.product-reports { display: flex; flex-direction: column; gap: var(--space-5); }
.product-reports__period { min-width: 11rem; }
.product-reports__summary { max-width: 46rem; margin: 0; font-family: var(--font-display); font-size: 1.5rem; line-height: 1.3; color: var(--color-ink); }
.product-reports__grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 0.8fr); gap: var(--space-6); align-items: start; }
.product-reports__story { position: sticky; top: var(--space-4); max-height: calc(100vh - var(--space-6)); overflow-y: auto; }
.product-reports__hint { margin: var(--space-6) 0 0; padding: var(--space-5); border: 0.0625rem dashed var(--color-border-strong); border-radius: var(--radius); color: var(--color-muted); font-size: 0.9375rem; }

@media (max-width: 64rem) {
    .product-reports__grid { grid-template-columns: 1fr; }
    .product-reports__story { position: static; order: -1; max-height: none; overflow: visible; }
    .product-reports__hint { display: none; }
}
</style>
