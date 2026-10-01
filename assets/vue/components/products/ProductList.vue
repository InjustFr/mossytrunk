<script setup>
import { computed, toRef } from 'vue';
import { VisuallyHidden } from 'reka-ui';
import { Archive, ArchiveRestore, FolderInput, PackagePlus, Pencil, Trash2, TriangleAlert } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
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
import { variantStock } from '../../composables/useVariantStock.js';

const props = defineProps({
    products: { type: Array, required: true },
    selectedId: { type: String, default: null },
    allSelected: { type: Boolean, default: false },
    typeColors: { type: Map, required: true },
    stockVariants: { type: Array, default: () => [] },
});
const emit = defineEmits(['edit', 'move', 'remove', 'archive', 'restore', 'toggle-all', 'restock', 'history']);
const checkedIds = defineModel('checkedIds', { type: Array, required: true });
const { t } = useI18n();

const knownCost = (product) => product.stockUnitCost > 0;
const margin = (product) => (knownCost(product) ? product.sellingPrice - product.stockUnitCost : null);
const stockOf = (product) => variantStock(product, props.stockVariants);
const stockDetail = (product) => stockOf(product).entries.map((item) => t('products.list.stockDetail', { variant: item.variant ?? t('products.list.stock'), count: item.onHand })).join(', ');

const columns = {
    type: (product) => `${product.typeName} ${product.displayName}`,
    name: (product) => product.displayName,
    collection: (product) => product.collectionName ?? '',
    stock: (product) => stockOf(product).onHand,
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
    <EmptyState v-if="products.length === 0">{{ t('products.list.empty') }}</EmptyState>
    <DataTable remember-page v-else :items="sorted" class="product-list">
        <template #head>
            <tr>
                <th class="product-list__check">
                    <BaseCheckbox :model-value="allSelected" :aria-label="t('products.list.selectAll')" @update:model-value="emit('toggle-all')" />
                </th>
                <SortableHeader :sort="ariaSort('name')" @sort="sortBy('name')">{{ t('products.list.name') }}</SortableHeader>
                <SortableHeader :sort="ariaSort('type')" @sort="sortBy('type')">{{ t('products.list.type') }}</SortableHeader>
                <SortableHeader :sort="ariaSort('collection')" @sort="sortBy('collection')">{{ t('products.list.collection') }}</SortableHeader>
                <th>{{ t('products.list.variants') }}</th>
                <SortableHeader :sort="ariaSort('stock')" numeric @sort="sortBy('stock')">{{ t('products.list.stock') }}</SortableHeader>
                <SortableHeader :sort="ariaSort('stockUnitCost')" numeric @sort="sortBy('stockUnitCost')">{{ t('products.list.cost') }}</SortableHeader>
                <SortableHeader :sort="ariaSort('sellingPrice')" numeric @sort="sortBy('sellingPrice')">{{ t('products.list.sellingPrice') }}</SortableHeader>
                <SortableHeader :sort="ariaSort('margin')" numeric @sort="sortBy('margin')">{{ t('products.list.margin') }}</SortableHeader>
                <SortableHeader :sort="ariaSort('sales')" numeric @sort="sortBy('sales')">{{ t('products.list.sales', { year: salesYear }) }}</SortableHeader>
                <th class="data-table__cell--actions"><VisuallyHidden>{{ t('products.list.actions') }}</VisuallyHidden></th>
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
                        :aria-label="t('products.list.select', { name: product.displayName })"
                        @update:model-value="setChecked(product.id, $event)"
                    />
                </td>
                <td class="product-list__name">
                    <a :href="`/products/${product.id}`">{{ product.displayName }}</a>
                    <span class="product-list__reference">{{ product.reference }}</span>
                </td>
                <td>
                    <span class="product-list__type"><TypeMark :color="typeColors.get(product.typeName)" />{{ product.typeName }}</span>
                    <StatusBadge v-if="product.archived && !product.archivedItself">{{ t('products.list.typeArchived') }}</StatusBadge>
                </td>
                <td>
                    <span v-if="product.collectionName" class="product-list__collection">{{ product.collectionName }}</span>
                    <span v-else class="product-list__muted">—</span>
                </td>
                <td>
                    <span v-if="product.variants.length === 0" class="product-list__muted">{{ t('products.single') }}</span>
                    <span v-else class="product-list__variants" :title="product.variants.join(', ')">{{ product.variants.join(', ') }}</span>
                </td>
                <td class="data-table__cell--number product-list__stock" :title="product.variants.length ? stockDetail(product) : null">
                    <button type="button" class="product-list__on-hand" :aria-label="t('products.list.stockHistory', { name: product.displayName })" @click="emit('history', product)">{{ stockOf(product).onHand }}</button>
                    <StatusBadge v-if="stockOf(product).negative" tone="danger">{{ t('products.negative') }}</StatusBadge>
                    <StatusBadge v-else-if="stockOf(product).low" tone="warning">{{ t('products.lowStock') }}</StatusBadge>
                </td>
                <td class="data-table__cell--number">
                    <TriangleAlert v-if="!knownCost(product)" class="product-list__warning" size="0.875rem" :aria-label="t('products.list.unknownCost')" role="img" />
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
                        <span class="product-list__ratio">{{ t('products.list.sold', product.unitsSold) }}</span>
                    </template>
                    <span v-else class="product-list__muted">—</span>
                </td>
                <td class="data-table__cell--actions">
                    <IconButton :icon="PackagePlus" :label="t('products.list.restock', { name: product.displayName })" @click="emit('restock', product)" />
                    <IconButton :icon="FolderInput" :label="t(product.variants.length ? 'products.list.moveVariant' : 'products.list.makeVariant', { name: product.displayName })" @click="emit('move', product)" />
                    <IconButton :icon="Pencil" :label="t('products.list.edit', { name: product.displayName })" @click="emit('edit', product)" />
                    <IconButton v-if="product.archivedItself" :icon="ArchiveRestore" :label="t('products.list.restore', { name: product.displayName })" @click="emit('restore', product)" />
                    <IconButton v-else-if="!product.archived" :icon="Archive" :label="t('products.list.archive', { name: product.displayName })" @click="emit('archive', product)" />
                    <ConfirmButton
                        :icon="Trash2"
                        :label="t('products.list.remove', { name: product.displayName })"
                        :message="t('products.list.removeMessage')"
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
.product-list__collection { white-space: nowrap; }
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
