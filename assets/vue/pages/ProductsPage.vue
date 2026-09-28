<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import ProductList from '../components/products/ProductList.vue';
import ProductForm from '../components/products/ProductForm.vue';
import { useProducts } from '../composables/useProducts.js';
import { useToast } from '../composables/useToast.js';

const { products, load, create, update } = useProducts();
const toast = useToast();
const editing = ref(null);

const submit = (payload) => (editing.value ? update(editing.value.id, payload) : create(payload));

async function onSaved(name) {
    toast.success(editing.value ? `Produit « ${name} » mis à jour.` : `Produit « ${name} » ajouté.`);
    editing.value = null;
    await load();
}

onMounted(load);
</script>

<template>
    <AppLayout title="Produits">
        <div class="products-page">
            <BaseCard class="products-page__list">
                <ProductList :products="products" :selected-id="editing?.id ?? null" @edit="editing = $event" />
            </BaseCard>
            <BaseCard class="products-page__form">
                <ProductForm :product="editing" :submit="submit" @saved="onSaved" @cancel="editing = null" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.products-page {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: var(--space-4);
    align-items: start;
}

.products-page__form { position: sticky; top: var(--space-4); }

@media (max-width: 900px) {
    .products-page { grid-template-columns: 1fr; }
    .products-page__form { position: static; }
}
</style>
