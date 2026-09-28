<script setup>
import { computed, ref } from 'vue';
import { formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    products: { type: Array, required: true },
    // Products already covered through a selected type: shown ticked and disabled.
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

function toggle(id) {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((existing) => existing !== id)
        : [...selected.value, id];
}
</script>

<template>
    <div class="product-picker">
        <input v-model="filter" class="product-picker__filter" type="search" placeholder="Filtrer les produits" aria-label="Filtrer les produits">
        <ul class="product-picker__list">
            <li v-for="product in visible" :key="product.id">
                <label :class="['product-picker__option', { 'product-picker__option--checked': selected.includes(product.id) || isCovered(product) }]">
                    <input type="checkbox" :checked="selected.includes(product.id) || isCovered(product)" :disabled="isCovered(product)" @change="toggle(product.id)">
                    <span class="product-picker__name">{{ product.displayName }}</span>
                    <span class="product-picker__price">{{ formatCents(product.sellingPrice) }}</span>
                </label>
            </li>
        </ul>
        <p class="product-picker__count">{{ selected.length }} produit(s) choisi(s) en plus des types</p>
    </div>
</template>

<style scoped>
.product-picker { display: flex; flex-direction: column; gap: var(--space-2); }

.product-picker__list {
    max-height: 220px;
    overflow-y: auto;
    margin: 0;
    padding: var(--space-1);
    list-style: none;
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
}

.product-picker__option {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-2);
    border-radius: calc(var(--radius) - 2px);
    cursor: pointer;
    transition: background var(--transition);
}

.product-picker__option--checked { background: var(--color-accent-soft); }
.product-picker__option input { width: auto; accent-color: var(--color-accent); }
.product-picker__name { flex: 1; }
.product-picker__price { color: var(--color-muted); font-variant-numeric: tabular-nums; }
.product-picker__count { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
