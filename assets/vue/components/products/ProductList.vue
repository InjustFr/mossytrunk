<script setup>
import { computed, toRef } from 'vue';
import { VisuallyHidden } from 'reka-ui';
import { Pencil, TriangleAlert } from '@lucide/vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import SortableHeader from '../ui/SortableHeader.vue';
import TypeMark from './TypeMark.vue';
import { formatRatio } from '../../composables/useMoney.js';
import { useSort } from '../../composables/useSort.js';

const props = defineProps({
    products: { type: Array, required: true },
    selectedId: { type: String, default: null },
    allSelected: { type: Boolean, default: false },
    typeColors: { type: Map, required: true },
});
const emit = defineEmits(['edit', 'toggle-all']);
const checkedIds = defineModel('checkedIds', { type: Array, required: true });

const knownCost = (product) => product.buyingPrice > 0;
const margin = (product) => (knownCost(product) ? product.sellingPrice - product.buyingPrice : null);

const columns = {
    type: (product) => `${product.typeName ?? '￿'} ${product.displayName}`,
    name: (product) => product.displayName,
    buyingPrice: (product) => product.buyingPrice,
    sellingPrice: (product) => product.sellingPrice,
    margin,
    unitsSold: (product) => product.unitsSold,
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
                <SortableHeader :sort="ariaSort('buyingPrice')" numeric @sort="sortBy('buyingPrice')">Achat</SortableHeader>
                <SortableHeader :sort="ariaSort('sellingPrice')" numeric @sort="sortBy('sellingPrice')">Vente</SortableHeader>
                <SortableHeader :sort="ariaSort('margin')" numeric @sort="sortBy('margin')">Marge</SortableHeader>
                <SortableHeader :sort="ariaSort('unitsSold')" numeric @sort="sortBy('unitsSold')">Vendus {{ salesYear }}</SortableHeader>
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
                <td>
                    {{ product.displayName }}
                    <span class="product-list__reference">{{ product.reference }}</span>
                </td>
                <td>
                    <span class="product-list__type"><TypeMark :color="typeColors.get(product.typeName)" />{{ product.typeName ?? 'Sans type' }}</span>
                </td>
                <td>
                    <span v-if="product.variants.length === 0" class="product-list__muted">Unique</span>
                    <span v-else>{{ product.variants.join(', ') }}</span>
                </td>
                <td class="data-table__cell--number">
                    <TriangleAlert v-if="!knownCost(product)" class="product-list__warning" size="0.875rem" aria-label="Prix d'achat à renseigner" role="img" />
                    <MoneyAmount :cents="product.buyingPrice" />
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="product.sellingPrice" /></td>
                <td class="data-table__cell--number">
                    <template v-if="knownCost(product)">
                        <MoneyAmount :cents="margin(product)" />
                        <span class="product-list__ratio">{{ formatRatio(margin(product), product.sellingPrice) }}</span>
                    </template>
                    <span v-else class="product-list__muted">—</span>
                </td>
                <td class="data-table__cell--number">{{ product.unitsSold || '' }}<span v-if="!product.unitsSold" class="product-list__muted">—</span></td>
                <td class="data-table__cell--number">
                    <MoneyAmount v-if="product.sales" :cents="product.sales" />
                    <span v-else class="product-list__muted">—</span>
                </td>
                <td class="data-table__cell--actions">
                    <IconButton :icon="Pencil" :label="`Modifier ${product.displayName}`" @click="emit('edit', product)" />
                </td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.product-list__row { transition: background var(--transition); }
.product-list__row--selected { background: var(--color-accent-soft); }
.product-list__check { width: 2rem; }
.product-list__reference { display: block; color: var(--color-muted); font-size: 0.75rem; }
.product-list__type { display: inline-flex; align-items: center; gap: var(--space-2); white-space: nowrap; }
.product-list__muted { color: var(--color-subtle); }
.product-list__ratio { display: block; color: var(--color-muted); font-size: 0.75rem; }
.product-list__warning { margin-right: var(--space-1); color: var(--color-warning); vertical-align: -0.125rem; }
</style>
