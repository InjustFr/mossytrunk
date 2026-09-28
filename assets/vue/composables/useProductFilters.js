import { computed, ref } from 'vue';

export const UNTYPED = '__untyped__';

/** Type + text filters over a product list, and a selection limited to what is visible. */
export function useProductFilters(products) {
    const typeId = ref('');
    const search = ref('');
    const selectedIds = ref([]);

    const filtered = computed(() => {
        const needle = search.value.trim().toLowerCase();
        return products.value.filter((product) => {
            if (typeId.value === UNTYPED && product.typeId !== null) return false;
            if (typeId.value && typeId.value !== UNTYPED && product.typeId !== typeId.value) return false;
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

    return { typeId, search, filtered, selectedIds, allVisibleSelected, toggleAllVisible, clearSelection };
}
