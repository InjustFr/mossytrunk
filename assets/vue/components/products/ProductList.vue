<script setup>
import { VisuallyHidden } from 'reka-ui';
import { TriangleAlert } from '@lucide/vue';
import { Pencil } from '@lucide/vue';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    products: { type: Array, required: true },
    selectedId: { type: String, default: null },
    allSelected: { type: Boolean, default: false },
});
const emit = defineEmits(['edit', 'toggle-all']);
const checkedIds = defineModel('checkedIds', { type: Array, required: true });

function setChecked(id, checked) {
    checkedIds.value = checked ? [...checkedIds.value, id] : checkedIds.value.filter((existing) => existing !== id);
}
</script>

<template>
    <EmptyState v-if="products.length === 0">Aucun produit.</EmptyState>
    <DataTable v-else :items="products" class="product-list">
        <template #head>
            <tr>
                <th class="product-list__check">
                    <BaseCheckbox :model-value="allSelected" aria-label="Tout sélectionner" @update:model-value="emit('toggle-all')" />
                </th>
                <th>Référence</th>
                <th>Type</th>
                <th>Nom</th>
                <th>Variantes</th>
                <th class="data-table__cell--number">Achat</th>
                <th class="data-table__cell--number">Vente</th>
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
                <td class="product-list__reference">{{ product.reference }}</td>
                <td class="product-list__type">{{ product.typeName ?? '—' }}</td>
                <td>{{ product.displayName }}</td>
                <td>
                    <span v-if="product.variants.length === 0" class="product-list__unique">Produit unique</span>
                    <span v-else class="product-list__variants">{{ product.variants.join(', ') }}</span>
                </td>
                <td class="data-table__cell--number">
                    <MoneyAmount :cents="product.buyingPrice" />
                    <TriangleAlert v-if="product.buyingPrice === 0" class="product-list__warning" size="0.875rem" aria-label="Prix d'achat à renseigner" role="img" />
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="product.sellingPrice" /></td>
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
.product-list__reference { color: var(--color-muted); font-size: 0.9rem; }
.product-list__check { width: 2rem; }
.product-list__type { color: var(--color-muted); }
.product-list__unique { color: var(--color-muted); font-style: italic; }
.product-list__warning { margin-left: var(--space-1); color: var(--color-warning); vertical-align: -0.125rem; }
</style>
