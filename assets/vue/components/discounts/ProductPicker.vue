<script setup>
import { computed, ref } from 'vue';
import { Check } from '@lucide/vue';
import { ListboxContent, ListboxFilter, ListboxItem, ListboxItemIndicator, ListboxRoot } from 'reka-ui';
import { formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    products: { type: Array, required: true },
    coveredTypeIds: { type: Array, default: () => [] },
});
const isCovered = (product) => product.typeId !== null && props.coveredTypeIds.includes(product.typeId);
const selected = defineModel({ type: Array, required: true });
const filter = ref('');

const visible = computed(() => {
    const needle = filter.value.trim().toLowerCase();
    return needle === ''
        ? props.products
        : props.products.filter((p) => `${p.displayName} ${p.reference}`.toLowerCase().includes(needle));
});
</script>

<template>
    <ListboxRoot v-model="selected" multiple selection-behavior="toggle" class="product-picker">
        <ListboxFilter v-model="filter" class="product-picker__filter" placeholder="Filtrer les produits" aria-label="Filtrer les produits" />
        <ListboxContent class="product-picker__list" aria-label="Produits">
            <ListboxItem
                v-for="product in visible"
                :key="product.id"
                :value="product.id"
                :disabled="isCovered(product)"
                class="product-picker__option"
            >
                <span class="product-picker__box" aria-hidden="true">
                    <ListboxItemIndicator><Check size="0.75rem" :stroke-width="3" /></ListboxItemIndicator>
                </span>
                <span class="product-picker__name">{{ product.displayName }}</span>
                <span v-if="isCovered(product)" class="product-picker__covered">via son type</span>
                <span class="product-picker__price">{{ formatCents(product.sellingPrice) }}</span>
            </ListboxItem>
        </ListboxContent>
        <p class="product-picker__count">{{ selected.length }} produit(s) choisi(s) en plus des types</p>
    </ListboxRoot>
</template>

<style scoped>
.product-picker { display: flex; flex-direction: column; gap: var(--space-2); }

.product-picker__list {
    max-height: 13.75rem;
    overflow-y: auto;
    padding: var(--space-1);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    outline: none;
}

.product-picker__list:focus-visible { border-color: var(--color-accent); }

.product-picker__option {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-2);
    border-radius: calc(var(--radius) - 0.125rem);
    cursor: pointer;
    outline: none;
    transition: background var(--transition);
}

.product-picker__option[data-highlighted] { background: var(--color-bg); }
.product-picker__option[data-state="checked"] { background: var(--color-accent-soft); }
.product-picker__option[data-disabled] { cursor: default; }

.product-picker__box {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 1.125rem;
    height: 1.125rem;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 0.25rem;
    background: var(--color-surface);
    color: #fff;
}

.product-picker__option[data-state="checked"] .product-picker__box { background: var(--color-accent); border-color: var(--color-accent); }
.product-picker__option[data-disabled] { color: var(--color-muted); }

.product-picker__name { flex: 1; }
.product-picker__covered { color: var(--color-accent-strong); font-size: 0.8rem; }
.product-picker__price { color: var(--color-muted); font-variant-numeric: tabular-nums; }
.product-picker__count { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
