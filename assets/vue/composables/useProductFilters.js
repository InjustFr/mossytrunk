import { computed, ref, watch } from 'vue';

export const MISSING_COST_PARAM = 'purchase-price';
export const STOCK_PARAM = 'stock';

export function useProductFilters(products) {
    const typeId = ref('');
    const variants = ref([]);
    const search = ref('');
    const missingCost = ref(new URLSearchParams(window.location.search).get(MISSING_COST_PARAM) === 'missing');
    const missingCostCount = computed(() => products.value.filter((product) => product.buyingPrice === 0).length);
    const lowStock = ref(new URLSearchParams(window.location.search).get(STOCK_PARAM) === 'low');
    const lowStockCount = computed(() => products.value.filter((product) => product.lowStock).length);
    const selectedIds = ref([]);

    watch(typeId, () => { variants.value = []; });

    const hasSelectedVariant = (product) => variants.value.length === 0 || product.variants.some((variant) => variants.value.includes(variant));

    const filtered = computed(() => {
        const needle = search.value.trim().toLowerCase();
        return products.value.filter((product) => {
            if (typeId.value && product.typeId !== typeId.value) return false;
            if (!hasSelectedVariant(product)) return false;
            if (missingCost.value && product.buyingPrice !== 0) return false;
            if (lowStock.value && !product.lowStock) return false;
            return needle === '' || `${product.displayName} ${product.reference}`.toLowerCase().includes(needle);
        });
    });

    const allVisibleSelected = computed(() => filtered.value.length > 0 && filtered.value.every((p) => selectedIds.value.includes(p.id)));

    function toggleAllVisible() {
        const visible = filtered.value.map((p) => p.id);
        selectedIds.value = allVisibleSelected.value
            ? selectedIds.value.filter((id) => !visible.includes(id))
            : [...new Set([...selectedIds.value, ...visible])];
    }

    const clearSelection = () => { selectedIds.value = []; };

    return { typeId, variants, search, missingCost, missingCostCount, lowStock, lowStockCount, filtered, selectedIds, allVisibleSelected, toggleAllVisible, clearSelection };
}
