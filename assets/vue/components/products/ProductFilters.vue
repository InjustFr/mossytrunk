<script setup>
import { UNTYPED } from '../../composables/useProductFilters.js';

defineProps({
    types: { type: Array, required: true },
});
const typeId = defineModel('typeId', { type: String, required: true });
const search = defineModel('search', { type: String, required: true });

const chips = (types) => [{ id: '', name: 'Tous' }, ...types, { id: UNTYPED, name: 'Sans type' }];
</script>

<template>
    <div class="product-filters">
        <div class="product-filters__chips" role="group" aria-label="Filtrer par type">
            <button
                v-for="chip in chips(types)"
                :key="chip.id"
                type="button"
                :class="['product-filters__chip', { 'product-filters__chip--active': typeId === chip.id }]"
                :aria-pressed="typeId === chip.id"
                @click="typeId = chip.id"
            >{{ chip.name }}</button>
        </div>
        <input v-model="search" class="product-filters__search" type="search" placeholder="Rechercher…" aria-label="Rechercher un produit">
    </div>
</template>

<style scoped>
.product-filters { display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4); }
.product-filters__chips { display: flex; gap: var(--space-2); flex-wrap: wrap; }

.product-filters__chip {
    padding: var(--space-1) var(--space-3);
    border: 1px solid var(--color-border-strong);
    border-radius: 999px;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.85rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.product-filters__chip:hover { border-color: var(--color-ink); }
.product-filters__chip--active { background: var(--color-ink); border-color: var(--color-ink); color: #fff; }

.product-filters__search {
    min-height: 34px;
    min-width: 220px;
    padding: var(--space-1) var(--space-3);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius);
}
</style>
