<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ProductBatchForm from '../components/products/ProductBatchForm.vue';
import ProductFilters from '../components/products/ProductFilters.vue';
import MoveVariantForm from '../components/products/MoveVariantForm.vue';
import ProductForm from '../components/products/ProductForm.vue';
import ProductList from '../components/products/ProductList.vue';
import SelectionBar from '../components/products/SelectionBar.vue';
import { useProductFilters } from '../composables/useProductFilters.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useToast } from '../composables/useToast.js';
import { plural } from '../composables/usePlural.js';
import { typeColors } from '../composables/useTypeColor.js';

const { products, load, create, update, batchUpdate, moveVariant, remove } = useProducts();
const { types, load: loadTypes } = useProductTypes();
const filters = useProductFilters(products);
const toast = useToast();

const colors = computed(() => typeColors(types.value.map((type) => type.name)));
const modalOpen = ref(false);
const batchOpen = ref(false);
const moving = ref(null);
const moveOpen = computed({ get: () => moving.value !== null, set: (open) => { if (!open) moving.value = null; } });
const editing = ref(null);

const modalTitle = computed(() => (editing.value ? 'Modifier le produit' : 'Nouveau produit'));
const submit = (payload) => (editing.value ? update(editing.value.id, payload) : create(payload));
const submitBatch = (changes) => batchUpdate({ productIds: filters.selectedIds.value, ...changes });

function openCreate() {
    editing.value = null;
    modalOpen.value = true;
}

function openEdit(product) {
    editing.value = product;
    modalOpen.value = true;
}

async function onSaved(name) {
    toast.success(editing.value ? `Produit « ${name} » mis à jour.` : `Produit « ${name} » ajouté.`);
    modalOpen.value = false;
    await load();
}

async function onMoved({ variant, target }) {
    toast.success(variant ? `Déplacé vers « ${target} — ${variant} ».` : `Déplacé vers « ${target} ».`);
    moving.value = null;
    await load();
}

async function onRemove(product) {
    try {
        await remove(product.id);
        toast.success(`Produit « ${product.displayName} » supprimé.`);
        filters.selectedIds.value = filters.selectedIds.value.filter((id) => id !== product.id);
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

async function onBatchSaved(count) {
    toast.success(`${plural(count, 'produit mis à jour', 'produits mis à jour')}.`);
    batchOpen.value = false;
    filters.clearSelection();
    await load();
}

onMounted(() => Promise.all([load(), loadTypes()]));
</script>

<template>
    <AppLayout title="Produits">
        <template #actions>
            <BaseButton @click="openCreate">Nouveau produit</BaseButton>
        </template>

        <BaseCard>
            <ProductFilters
                v-model:type-id="filters.typeId.value"
                v-model:search="filters.search.value"
                v-model:missing-cost="filters.missingCost.value"
                :types="types"
                :missing-cost-count="filters.missingCostCount.value"
                :type-colors="colors"
            />
            <ProductList
                v-model:checked-ids="filters.selectedIds.value"
                :products="filters.filtered.value"
                :all-selected="filters.allVisibleSelected.value"
                :type-colors="colors"
                :selected-id="modalOpen ? editing?.id ?? null : null"
                @toggle-all="filters.toggleAllVisible"
                @edit="openEdit"
                @move="moving = $event"
                @remove="onRemove"
            />
        </BaseCard>

        <SelectionBar :count="filters.selectedIds.value.length" @edit="batchOpen = true" @clear="filters.clearSelection" />

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <ProductForm :product="editing" :submit="submit" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="moveOpen" :title="moving?.variants.length ? 'Déplacer une variante' : 'Faire une variante de ce produit'">
            <MoveVariantForm v-if="moving" :product="moving" :products="products" :submit="(payload) => moveVariant(moving.id, payload)" @moved="onMoved" @cancel="moving = null" />
        </BaseModal>
        <BaseModal v-model:open="batchOpen" title="Modifier la sélection">
            <ProductBatchForm :count="filters.selectedIds.value.length" :submit="submitBatch" @saved="onBatchSaved" @cancel="batchOpen = false" />
        </BaseModal>
    </AppLayout>
</template>
