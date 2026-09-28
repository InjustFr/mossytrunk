<script setup>
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import BaseButton from '../ui/BaseButton.vue';

defineProps({
    products: { type: Array, required: true },
    selectedId: { type: String, default: null },
});
const emit = defineEmits(['edit']);
</script>

<template>
    <EmptyState v-if="products.length === 0">Aucun produit pour l'instant.</EmptyState>
    <DataTable v-else class="product-list">
        <template #head>
            <tr>
                <th>Référence</th>
                <th>Type</th>
                <th>Nom</th>
                <th>Variantes</th>
                <th class="data-table__cell--number">Achat</th>
                <th class="data-table__cell--number">Vente</th>
                <th><span class="visually-hidden">Actions</span></th>
            </tr>
        </template>
        <tr
            v-for="product in products"
            :key="product.id"
            :class="['product-list__row', { 'product-list__row--selected': product.id === selectedId }]"
        >
            <td class="product-list__reference">{{ product.reference }}</td>
            <td class="product-list__type">{{ product.typeName ?? '—' }}</td>
            <td>{{ product.displayName }}</td>
            <td>
                <span v-if="product.variants.length === 0" class="product-list__unique">Produit unique</span>
                <span v-else class="product-list__variants">{{ product.variants.join(', ') }}</span>
            </td>
            <td class="data-table__cell--number">
                <MoneyAmount :cents="product.buyingPrice" />
                <span v-if="product.buyingPrice === 0" class="product-list__warning" title="Prix d'achat à renseigner">⚠︎</span>
            </td>
            <td class="data-table__cell--number"><MoneyAmount :cents="product.sellingPrice" /></td>
            <td>
                <BaseButton variant="ghost" :aria-label="`Modifier ${product.displayName}`" @click="emit('edit', product)">Modifier</BaseButton>
            </td>
        </tr>
    </DataTable>
</template>

<style scoped>
.product-list__row { transition: background var(--transition); }
.product-list__row--selected { background: var(--color-accent-soft); }
.product-list__reference { color: var(--color-muted); font-size: 0.9rem; }
.product-list__type { color: var(--color-muted); }
.product-list__unique { color: var(--color-muted); font-style: italic; }
.product-list__warning { margin-left: var(--space-1); color: #b7791f; }
</style>
