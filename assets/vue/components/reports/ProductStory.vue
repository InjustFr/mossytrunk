<script setup>
import { X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import IconButton from '../ui/IconButton.vue';
import DiscountTimeline from './DiscountTimeline.vue';
import MonthlySalesChart from './MonthlySalesChart.vue';
import StockFlowChart from './StockFlowChart.vue';

defineProps({
    story: { type: Object, required: true },
});
const emit = defineEmits(['close']);
const { t } = useI18n();
</script>

<template>
    <article class="product-story" aria-labelledby="product-story-title">
        <header class="product-story__header">
            <div>
                <p class="product-story__type">{{ story.product.typeName }}</p>
                <h2 id="product-story-title" class="product-story__name">{{ story.product.name }}</h2>
                <p class="product-story__facts">
                    {{ t('reports.story.onHand', { count: story.product.onHand }, story.product.onHand) }}
                    <a :href="`/products/${story.product.id}`" class="product-story__link">{{ t('reports.story.openProduct') }}</a>
                </p>
            </div>
            <IconButton :icon="X" :label="t('reports.story.close')" @click="emit('close')" />
        </header>

        <section class="product-story__part" aria-labelledby="story-sales">
            <h3 id="story-sales" class="product-story__title">{{ t('reports.sales.title') }}</h3>
            <MonthlySalesChart :months="story.months" />
        </section>
        <section class="product-story__part" aria-labelledby="story-flow">
            <h3 id="story-flow" class="product-story__title">{{ t('reports.flow.title') }}</h3>
            <StockFlowChart :months="story.months" />
        </section>
        <section class="product-story__part" aria-labelledby="story-discounts">
            <h3 id="story-discounts" class="product-story__title">{{ t('reports.discounts.title') }}</h3>
            <DiscountTimeline :months="story.months" :discounts="story.discounts" :period="story.period" :discounted-days="story.discountedDays" />
        </section>
    </article>
</template>

<style scoped>
.product-story {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    padding: var(--space-5);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
}

.product-story__header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); }
.product-story__type { margin: 0; color: var(--color-muted); font-size: 0.8125rem; }
.product-story__name { margin: 0; font-size: 1.6rem; line-height: 1.15; }
.product-story__facts { display: flex; flex-wrap: wrap; gap: var(--space-3); margin: var(--space-1) 0 0; font-size: 0.875rem; }
.product-story__link { color: var(--color-accent-strong); }
.product-story__part { display: flex; flex-direction: column; gap: var(--space-2); padding-top: var(--space-4); border-top: 0.0625rem solid var(--color-border); }
.product-story__title { margin: 0; font-family: var(--font-body); font-size: 0.9375rem; font-weight: 600; }
</style>
