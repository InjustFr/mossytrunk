<script setup>
import { formatCents } from '../../composables/useMoney.js';
import { plural } from '../../composables/usePlural.js';

const props = defineProps({
    products: { type: Array, required: true },
    types: { type: Array, required: true },
});

const groups = [
    { key: 'products', title: 'Produits', rows: () => props.products.map((product) => ({ key: product.id, name: product.name, ...product })) },
    { key: 'types', title: 'Types', rows: () => props.types.map((type) => ({ key: type.name ?? '', ...type, name: type.name ?? 'Sans type' })) },
];

const share = (sales, rows) => `${(sales / Math.max(1, ...rows.map((row) => row.sales))) * 100}%`;
</script>

<template>
    <div class="best-sellers">
        <section v-for="group in groups" :key="group.key" :aria-labelledby="`best-sellers-${group.key}`">
            <h3 :id="`best-sellers-${group.key}`" class="best-sellers__heading">{{ group.title }}</h3>
            <ol class="best-sellers__list">
                <li v-for="row in group.rows()" :key="row.key" class="best-sellers__item">
                    <span class="best-sellers__name">{{ row.name }}</span>
                    <span class="best-sellers__quantity">{{ plural(row.quantity, 'vendu', 'vendus') }}</span>
                    <span class="best-sellers__sales">{{ formatCents(row.sales) }}</span>
                    <span class="best-sellers__bar" :style="{ width: share(row.sales, group.rows()) }" aria-hidden="true" />
                </li>
            </ol>
        </section>
    </div>
</template>

<style scoped>
.best-sellers { display: flex; flex-direction: column; gap: var(--space-5); }

.best-sellers__heading { margin: 0 0 var(--space-2); font-size: 0.875rem; font-weight: 500; color: var(--color-muted); }
.best-sellers__list { display: flex; flex-direction: column; gap: var(--space-2); margin: 0; padding: 0; list-style: none; }

.best-sellers__item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto 6.5rem;
    align-items: baseline;
    column-gap: var(--space-3);
    row-gap: 0.1875rem;
}

.best-sellers__name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.best-sellers__quantity { color: var(--color-muted); font-size: 0.85rem; font-variant-numeric: tabular-nums; }
.best-sellers__sales { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
.best-sellers__bar { grid-column: 1 / -1; height: 0.25rem; border-radius: 0.125rem; background: var(--color-accent); opacity: 0.55; }
</style>
