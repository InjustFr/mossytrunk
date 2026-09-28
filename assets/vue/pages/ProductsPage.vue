<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ProductList from '../components/products/ProductList.vue';
import ProductForm from '../components/products/ProductForm.vue';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useToast } from '../composables/useToast.js';

const { products, load, create, update } = useProducts();
const { load: loadTypes } = useProductTypes();
const toast = useToast();
const modalOpen = ref(false);
const editing = ref(null);

const modalTitle = computed(() => (editing.value ? 'Modifier le produit' : 'Nouveau produit'));
const submit = (payload) => (editing.value ? update(editing.value.id, payload) : create(payload));

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

onMounted(() => Promise.all([load(), loadTypes()]));
</script>

<template>
    <AppLayout title="Produits">
        <template #actions>
            <BaseButton @click="openCreate">Nouveau produit</BaseButton>
        </template>

        <BaseCard>
            <ProductList :products="products" :selected-id="modalOpen ? editing?.id ?? null : null" @edit="openEdit" />
        </BaseCard>

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <ProductForm :product="editing" :submit="submit" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>
