<script setup>
import { computed } from 'vue';
import { Toggle, ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { PackageMinus, TriangleAlert } from '@lucide/vue';
import TypeMark from '../ui/TypeMark.vue';
import { UNTYPED } from '../../composables/useProductFilters.js';
import { plural } from '../../composables/usePlural.js';

const props = defineProps({
    types: { type: Array, required: true },
    missingCostCount: { type: Number, default: 0 },
    lowStockCount: { type: Number, default: 0 },
    typeColors: { type: Map, required: true },
});
const typeId = defineModel('typeId', { type: String, required: true });
const search = defineModel('search', { type: String, required: true });
const missingCost = defineModel('missingCost', { type: Boolean, default: false });
const lowStock = defineModel('lowStock', { type: Boolean, default: false });

const ALL = '__all__';
const chips = computed(() => [{ id: ALL, name: 'Tous', mark: false }, ...props.types.map((type) => ({ ...type, mark: true })), { id: UNTYPED, name: 'Sans type', mark: false }]);

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
        <input v-model="search" class="product-filters__search" type="search" placeholder="Rechercher…" aria-label="Rechercher un produit">
        <ToggleGroupRoot v-model="selectedChip" type="single" class="product-filters__chips" aria-label="Filtrer par type">
            <ToggleGroupItem v-for="chip in chips" :key="chip.id" :value="chip.id" class="product-filters__chip">
                <TypeMark v-if="chip.mark" :color="typeColors.get(chip.name)" />{{ chip.name }}
            </ToggleGroupItem>
        </ToggleGroupRoot>
        <Toggle v-if="lowStockCount > 0 || lowStock" v-model="lowStock" class="product-filters__missing product-filters__low-stock">
            <PackageMinus size="0.875rem" aria-hidden="true" />
            {{ plural(lowStockCount, 'produit en stock bas', 'produits en stock bas') }}
        </Toggle>
        <Toggle v-if="missingCostCount > 0 || missingCost" v-model="missingCost" class="product-filters__missing">
            <TriangleAlert size="0.875rem" aria-hidden="true" />
            {{ plural(missingCostCount, 'produit sans coût d\'achat', 'produits sans coût d\'achat') }}
        </Toggle>
    </div>
</template>

<style scoped>
.product-filters { display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4); }
.product-filters__chips { display: flex; flex: 1 1 auto; gap: var(--space-2); flex-wrap: wrap; }

.product-filters__chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
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
.product-filters__chip[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }

.product-filters__missing {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    margin-left: auto;
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-warning);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    color: var(--color-warning);
    font-size: 0.85rem;
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.product-filters__low-stock + .product-filters__missing { margin-left: 0; }
.product-filters__missing[data-state="on"] { background: var(--color-warning); color: var(--color-surface); }

.product-filters__search {
    min-height: 2.125rem;
    min-width: 13.75rem;
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
}
</style>
