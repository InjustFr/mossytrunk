<script setup>
import { computed, toRef } from 'vue';
import { VisuallyHidden } from 'reka-ui';
import { FolderInput, PackagePlus, Pencil, Trash2, TriangleAlert } from '@lucide/vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import SortableHeader from '../ui/SortableHeader.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import TypeMark from '../ui/TypeMark.vue';
import { formatRatio } from '../../composables/useMoney.js';
import { useSort } from '../../composables/useSort.js';
import { plural } from '../../composables/usePlural.js';

const props = defineProps({
    products: { type: Array, required: true },
    selectedId: { type: String, default: null },
    allSelected: { type: Boolean, default: false },
    typeColors: { type: Map, required: true },
});
const emit = defineEmits(['edit', 'move', 'remove', 'toggle-all', 'restock', 'history']);
const checkedIds = defineModel('checkedIds', { type: Array, required: true });

const knownCost = (product) => product.stockUnitCost > 0;
const margin = (product) => (knownCost(product) ? product.sellingPrice - product.stockUnitCost : null);
const stockDetail = (product) => product.stock.map((item) => `${item.variant ?? 'Réserve'} : ${item.onHand}`).join(', ');

const columns = {
    type: (product) => `${product.typeName ?? '￿'} ${product.displayName}`,
    name: (product) => product.displayName,
    stock: (product) => product.onHand,
    stockUnitCost: (product) => product.stockUnitCost,
    sellingPrice: (product) => product.sellingPrice,
    margin,
    sales: (product) => product.sales,
};

const { sorted, sortBy, ariaSort } = useSort(toRef(props, 'products'), columns, 'type', 'ascending');
const salesYear = computed(() => props.products[0]?.salesYear ?? new Date().getFullYear());

function setChecked(id, checked) {
    checkedIds.value = checked ? [...checkedIds.value, id] : checkedIds.value.filter((existing) => existing !== id);
}
</script>

<template>
    <EmptyState v-if="products.length === 0">Aucun produit.</EmptyState>
    <DataTable v-else :items="sorted" class="product-list">
        <template #head>
            <tr>
                <th class="product-list__check">
                    <BaseCheckbox :model-value="allSelected" aria-label="Tout sélectionner" @update:model-value="emit('toggle-all')" />
                </th>
                <SortableHeader :sort="ariaSort('name')" @sort="sortBy('name')">Nom</SortableHeader>
                <SortableHeader :sort="ariaSort('type')" @sort="sortBy('type')">Type</SortableHeader>
                <th>Variantes</th>
                <SortableHeader :sort="ariaSort('stock')" numeric @sort="sortBy('stock')">Réserve</SortableHeader>
                <SortableHeader :sort="ariaSort('stockUnitCost')" numeric @sort="sortBy('stockUnitCost')">Coût</SortableHeader>
                <SortableHeader :sort="ariaSort('sellingPrice')" numeric @sort="sortBy('sellingPrice')">Vente</SortableHeader>
                <SortableHeader :sort="ariaSort('margin')" numeric @sort="sortBy('margin')">Marge</SortableHeader>
                <SortableHeader :sort="ariaSort('sales')" numeric @sort="sortBy('sales')">Ventes {{ salesYear }}</SortableHeader>
                <th class="data-table__cell--actions"><VisuallyHidden>Actions</VisuallyHidden></th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr
                v-for="product in rows"
                :key="product.id"
                :class="['product-list__row', { 'product-list__row--selected': product.id === selectedId }]"
            >
                <td class="product-list__check">
                    <BaseCheckbox
                        :model-value="checkedIds.includes(product.id)"
                        :aria-label="`Sélectionner ${product.displayName}`"
                        @update:model-value="setChecked(product.id, $event)"
                    />
                </td>
                <td class="product-list__name">
                    <a :href="`/products/${product.id}`">{{ product.displayName }}</a>
                    <span class="product-list__reference">{{ product.reference }}</span>
                </td>
                <td>
                    <span class="product-list__type"><TypeMark :color="typeColors.get(product.typeName)" />{{ product.typeName ?? 'Sans type' }}</span>
                </td>
                <td>
                    <span v-if="product.variants.length === 0" class="product-list__muted">Unique</span>
                    <span v-else class="product-list__variants" :title="product.variants.join(', ')">{{ product.variants.join(', ') }}</span>
                </td>
                <td class="data-table__cell--number product-list__stock" :title="product.variants.length ? stockDetail(product) : null">
                    <button type="button" class="product-list__on-hand" :aria-label="`Historique de la réserve de ${product.displayName}`" @click="emit('history', product)">{{ product.onHand }}</button>
                    <StatusBadge v-if="product.negativeStock" tone="danger">Négatif</StatusBadge>
                    <StatusBadge v-else-if="product.lowStock" tone="warning">Stock bas</StatusBadge>
                </td>
                <td class="data-table__cell--number">
                    <TriangleAlert v-if="!knownCost(product)" class="product-list__warning" size="0.875rem" aria-label="Coût d'achat inconnu : réapprovisionnez ce produit" role="img" />
                    <MoneyAmount :cents="product.stockUnitCost" />
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="product.sellingPrice" /></td>
                <td class="data-table__cell--number">
                    <template v-if="knownCost(product)">
                        <MoneyAmount :cents="margin(product)" />
                        <span class="product-list__ratio">{{ formatRatio(margin(product), product.sellingPrice) }}</span>
                    </template>
                    <span v-else class="product-list__muted">—</span>
                </td>
                <td class="data-table__cell--number">
                    <template v-if="product.sales">
                        <MoneyAmount :cents="product.sales" />
                        <span class="product-list__ratio">{{ plural(product.unitsSold, 'vendu', 'vendus') }}</span>
                    </template>
                    <span v-else class="product-list__muted">—</span>
                </td>
                <td class="data-table__cell--actions">
                    <IconButton :icon="PackagePlus" :label="`Réapprovisionner ${product.displayName}`" @click="emit('restock', product)" />
                    <IconButton :icon="FolderInput" :label="product.variants.length ? `Déplacer une variante de ${product.displayName}` : `Faire de ${product.displayName} une variante`" @click="emit('move', product)" />
                    <IconButton :icon="Pencil" :label="`Modifier ${product.displayName}`" @click="emit('edit', product)" />
                    <ConfirmButton
                        :icon="Trash2"
                        :label="`Supprimer ${product.displayName}`"
                        message="Il disparaît du catalogue et des remises. Les commandes passées gardent leurs lignes."
                        @confirm="emit('remove', product)"
                    />
                </td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.product-list__row { transition: background var(--transition); }
.product-list__row--selected { background: var(--color-accent-soft); }
.product-list__check { width: 2rem; }
.data-table.product-list :deep(th),
.data-table.product-list :deep(td) { padding-left: var(--space-2); padding-right: var(--space-2); }
.product-list__name { min-width: 11rem; }
.product-list__variants { display: block; max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.product-list__reference { display: block; color: var(--color-muted); font-size: 0.75rem; }
.product-list__type { display: inline-flex; align-items: center; gap: var(--space-2); white-space: nowrap; }
.product-list__muted { color: var(--color-subtle); }
.product-list__ratio { display: block; color: var(--color-muted); font-size: 0.75rem; }
.product-list__stock { white-space: nowrap; }
.product-list__on-hand {
    padding: 0;
    border: none;
    border-bottom: 0.0625rem dotted var(--color-border-strong);
    background: none;
    color: inherit;
    font: inherit;
    font-variant-numeric: tabular-nums;
    cursor: pointer;
    transition: border-color var(--transition);
}
.product-list__on-hand:hover { border-bottom-color: var(--color-ink); }
.product-list__on-hand:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.product-list__stock .status-badge { margin-left: var(--space-1); }
.product-list__warning { margin-right: var(--space-1); color: var(--color-warning); vertical-align: -0.125rem; }
</style>
