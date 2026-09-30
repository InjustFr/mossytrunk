<script setup>
import { useI18n } from 'vue-i18n';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    name: { type: String, default: '' },
    reference: { type: String, default: '' },
    price: { type: Number, default: null },
    variants: { type: Array, default: () => [] },
    color: { type: String, default: null },
});
const { t } = useI18n();
</script>

<template>
    <figure class="product-tag" :style="color ? { '--tag-color': color } : null" :aria-label="t('products.tag.label')">
        <span class="product-tag__hole" aria-hidden="true" />
        <span :class="['product-tag__name', { 'product-tag__name--empty': !name }]">{{ name || t('products.tag.namePlaceholder') }}</span>
        <span class="product-tag__price">
            <MoneyAmount v-if="price !== null" :cents="price" />
            <template v-else>—</template>
        </span>
        <span class="product-tag__meta">
            <span v-if="reference" class="product-tag__reference">{{ reference }}</span>
            <span v-if="variants.length" class="product-tag__variants">{{ variants.join(' / ') }}</span>
            <span v-if="!reference && !variants.length">{{ t('products.tag.unique') }}</span>
        </span>
    </figure>
</template>

<style scoped>
.product-tag {
    --tag-color: var(--color-border-strong);

    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: baseline;
    gap: var(--space-1) var(--space-4);
    margin: 0;
    padding: var(--space-3) var(--space-4) var(--space-3) calc(var(--space-4) + 1.25rem);
    border: 0.0625rem solid var(--color-border-strong);
    border-left: 0.3125rem solid var(--tag-color);
    border-radius: 0.25rem var(--radius) var(--radius) 0.25rem;
    background: var(--color-surface);
    transition: border-color var(--transition);
}

.product-tag__hole {
    position: absolute;
    top: 50%;
    left: var(--space-3);
    width: 0.625rem;
    height: 0.625rem;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 50%;
    background: var(--color-bg);
    transform: translateY(-50%);
}

.product-tag__name {
    overflow: hidden;
    font-family: var(--font-display);
    font-size: 1.2rem;
    line-height: 1.2;
    color: var(--color-ink);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-tag__name--empty { color: var(--color-subtle); }
.product-tag__price { font-family: var(--font-display); font-size: 1.2rem; color: var(--color-ink); white-space: nowrap; }

.product-tag__meta {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-1) var(--space-3);
    color: var(--color-muted);
    font-size: 0.8125rem;
    font-variant-numeric: tabular-nums;
}

.product-tag__reference { letter-spacing: 0.02em; }
</style>
