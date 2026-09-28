<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ProductBatchForm from '../components/products/ProductBatchForm.vue';
import ProductFilters from '../components/products/ProductFilters.vue';
import ProductForm from '../components/products/ProductForm.vue';
import ProductList from '../components/products/ProductList.vue';
import SelectionBar from '../components/products/SelectionBar.vue';
import { useProductFilters } from '../composables/useProductFilters.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useToast } from '../composables/useToast.js';

const { products, load, create, update, batchUpdate } = useProducts();
const { types, load: loadTypes } = useProductTypes();
const filters = useProductFilters(products);
const toast = useToast();

const modalOpen = ref(false);
const batchOpen = ref(false);
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

async function onBatchSaved(count) {
    toast.success(`${count} produit(s) mis à jour.`);
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
            <ProductFilters v-model:type-id="filters.typeId.value" v-model:search="filters.search.value" :types="types" />
            <ProductList
                v-model:checked-ids="filters.selectedIds.value"
                :products="filters.filtered.value"
                :all-selected="filters.allVisibleSelected.value"
                :selected-id="modalOpen ? editing?.id ?? null : null"
                @toggle-all="filters.toggleAllVisible"
                @edit="openEdit"
            />
        </BaseCard>

        <SelectionBar :count="filters.selectedIds.value.length" @edit="batchOpen = true" @clear="filters.clearSelection" />

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <ProductForm :product="editing" :submit="submit" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="batchOpen" title="Modifier la sélection">
            <ProductBatchForm :count="filters.selectedIds.value.length" :submit="submitBatch" @saved="onBatchSaved" @cancel="batchOpen = false" />
        </BaseModal>
    </AppLayout>
</template>
