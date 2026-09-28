<script setup>
import { computed } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { UNTYPED } from '../../composables/useProductFilters.js';

const props = defineProps({
    types: { type: Array, required: true },
});
const typeId = defineModel('typeId', { type: String, required: true });
const search = defineModel('search', { type: String, required: true });

const ALL = '__all__';
const chips = computed(() => [{ id: ALL, name: 'Tous' }, ...props.types, { id: UNTYPED, name: 'Sans type' }]);

const selectedChip = computed({
    get: () => typeId.value || ALL,
    set: (chip) => {
        if (chip) {
            typeId.value = chip === ALL ? '' : chip;
        }
    },
});
</script>

<template>
    <div class="product-filters">
        <ToggleGroupRoot v-model="selectedChip" type="single" class="product-filters__chips" aria-label="Filtrer par type">
            <ToggleGroupItem v-for="chip in chips" :key="chip.id" :value="chip.id" class="product-filters__chip">{{ chip.name }}</ToggleGroupItem>
        </ToggleGroupRoot>
        <input v-model="search" class="product-filters__search" type="search" placeholder="Rechercher…" aria-label="Rechercher un produit">
    </div>
</template>

<style scoped>
.product-filters { display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4); }
.product-filters__chips { display: flex; gap: var(--space-2); flex-wrap: wrap; }

.product-filters__chip {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.85rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.product-filters__chip:hover { border-color: var(--color-ink); }
.product-filters__chip:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.product-filters__chip[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: #fff; }

.product-filters__search {
    min-height: 2.125rem;
    min-width: 13.75rem;
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
}
</style>
