<script setup>
import { computed } from 'vue';
import { Toggle, ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { PackageMinus, TriangleAlert } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import TypeMark from '../ui/TypeMark.vue';

const props = defineProps({
    types: { type: Array, required: true },
    missingCostCount: { type: Number, default: 0 },
    lowStockCount: { type: Number, default: 0 },
    typeColors: { type: Map, required: true },
    variantOptions: { type: Array, default: () => [] },
});
const typeId = defineModel('typeId', { type: String, required: true });
const variants = defineModel('variants', { type: Array, default: () => [] });
const search = defineModel('search', { type: String, required: true });
const missingCost = defineModel('missingCost', { type: Boolean, default: false });
const lowStock = defineModel('lowStock', { type: Boolean, default: false });
const { t } = useI18n();

const ALL = '__all__';
const chips = computed(() => [{ id: ALL, name: t('products.filters.all'), mark: false }, ...props.types.map((type) => ({ ...type, mark: true }))]);

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
        <input v-model="search" class="product-filters__search" type="search" :placeholder="t('products.filters.searchPlaceholder')" :aria-label="t('products.filters.searchLabel')">
        <ToggleGroupRoot v-model="selectedChip" type="single" class="product-filters__chips" :aria-label="t('products.filters.byType')">
            <ToggleGroupItem v-for="chip in chips" :key="chip.id" :value="chip.id" class="product-filters__chip">
                <TypeMark v-if="chip.mark" :color="typeColors.get(chip.name)" />{{ chip.name }}
            </ToggleGroupItem>
        </ToggleGroupRoot>
        <Toggle v-if="lowStockCount > 0 || lowStock" v-model="lowStock" class="product-filters__missing product-filters__low-stock">
            <PackageMinus size="0.875rem" aria-hidden="true" />
            {{ t('products.filters.lowStock', lowStockCount) }}
        </Toggle>
        <Toggle v-if="missingCostCount > 0 || missingCost" v-model="missingCost" class="product-filters__missing">
            <TriangleAlert size="0.875rem" aria-hidden="true" />
            {{ t('products.filters.missingCost', missingCostCount) }}
        </Toggle>
        <ToggleGroupRoot
            v-if="typeId && variantOptions.length"
            v-model="variants"
            type="multiple"
            class="product-filters__chips product-filters__variants"
            :aria-label="t('products.filters.byVariant')"
        >
            <ToggleGroupItem v-for="variant in variantOptions" :key="variant" :value="variant" class="product-filters__chip product-filters__chip--variant">{{ variant }}</ToggleGroupItem>
        </ToggleGroupRoot>
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
.product-filters__variants { flex-basis: 100%; }
.product-filters__chip--variant { padding: 0.125rem var(--space-3); font-size: 0.8rem; }
.product-filters__chip--variant[data-state="on"] { background: var(--color-accent-soft); border-color: var(--color-accent); color: var(--color-accent-strong); }

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
