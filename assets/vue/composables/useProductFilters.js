import { computed, ref, watch } from 'vue';
import { queryFlag, queryList, queryText } from './useQueryState.js';

export const MISSING_COST_PARAM = 'purchase-price';
export const STOCK_PARAM = 'stock';

export const KINDS = { article: 'article', supply: 'supply' };

export function useProductFilters(allProducts) {
    const kind = queryText('kind', KINDS.article);
    const ofKind = computed(() => allProducts.value.filter((product) => product.kind === kind.value));
    const archived = queryFlag('archived', 'yes');
    const archivedCount = computed(() => ofKind.value.filter((product) => product.archived).length);
    const products = computed(() => ofKind.value.filter((product) => product.archived === archived.value));
    const typeId = queryText('type');
    const variants = queryList('variant');
    const search = queryText('search');
    const missingCost = queryFlag(MISSING_COST_PARAM, 'missing');
    const missingCostCount = computed(() => products.value.filter((product) => product.buyingPrice === 0).length);
    const lowStock = queryFlag(STOCK_PARAM, 'low');
    const lowStockCount = computed(() => products.value.filter((product) => product.lowStock).length);
    const selectedIds = ref([]);

    watch(typeId, () => { variants.value = []; });
    watch([archived, kind], () => {
        typeId.value = '';
        selectedIds.value = [];
    });

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

    return { kind, archived, archivedCount, typeId, variants, search, missingCost, missingCostCount, lowStock, lowStockCount, filtered, selectedIds, allVisibleSelected, toggleAllVisible, clearSelection };
}
