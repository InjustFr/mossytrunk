<script setup>
import { computed, onMounted, ref } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import SupplierManager from '../components/purchasing/SupplierManager.vue';
import SupplierOrderForm from '../components/purchasing/SupplierOrderForm.vue';
import SupplierOrderList from '../components/purchasing/SupplierOrderList.vue';
import { useProducts } from '../composables/useProducts.js';
import { useSupplierOrders, useSuppliers } from '../composables/usePurchasing.js';
import { useToast } from '../composables/useToast.js';

const { orders, load, create } = useSupplierOrders();
const { suppliers, load: loadSuppliers, save: saveSupplier } = useSuppliers();
const { products, load: loadProducts } = useProducts();
const toast = useToast();

const formOpen = ref(false);
const suppliersOpen = ref(false);
const status = ref('ordered');

const awaiting = computed(() => orders.value.filter((order) => order.status === 'ordered'));
const visible = computed(() => (status.value === 'all' ? orders.value : orders.value.filter((order) => order.status === status.value)));
const filters = computed(() => [
    { value: 'ordered', label: `À réceptionner (${awaiting.value.length})` },
    { value: 'received', label: 'Reçues' },
    { value: 'all', label: 'Toutes' },
]);

async function saveAndReload(id, payload) {
    const supplier = await saveSupplier(id, payload);
    await loadSuppliers();
    return supplier;
}

async function onOrderSaved() {
    formOpen.value = false;
    toast.success('Commande fournisseur passée.');
    status.value = 'ordered';
    await load();
}

async function onSupplierSaved(name) {
    toast.success(`Fournisseur « ${name} » enregistré.`);
    await Promise.all([loadSuppliers(), load()]);
}

onMounted(async () => {
    await Promise.all([load(), loadSuppliers(), loadProducts()]);
    if (awaiting.value.length === 0 && orders.value.length > 0) {
        status.value = 'all';
    }
});
</script>

<template>
    <AppLayout title="Commandes fournisseurs">
        <template #actions>
            <BaseButton variant="secondary" @click="suppliersOpen = true">Fournisseurs</BaseButton>
            <BaseButton @click="formOpen = true">Nouvelle commande</BaseButton>
        </template>

        <BaseCard>
            <ToggleGroupRoot
                :model-value="status"
                type="single"
                class="supplier-orders-page__filters"
                aria-label="Filtrer par statut"
                @update:model-value="(value) => value && (status = value)"
            >
                <ToggleGroupItem v-for="filter in filters" :key="filter.value" :value="filter.value" class="supplier-orders-page__filter">{{ filter.label }}</ToggleGroupItem>
            </ToggleGroupRoot>
            <SupplierOrderList :orders="visible" />
        </BaseCard>

        <BaseModal v-model:open="formOpen" title="Nouvelle commande fournisseur" variant="drawer">
            <SupplierOrderForm :products="products" :suppliers="suppliers" :save-supplier="saveAndReload" :submit="create" @saved="onOrderSaved" @cancel="formOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="suppliersOpen" title="Fournisseurs">
            <SupplierManager :suppliers="suppliers" :save="saveSupplier" @saved="onSupplierSaved" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.supplier-orders-page__filters { display: flex; gap: var(--space-2); flex-wrap: wrap; margin-bottom: var(--space-4); }

.supplier-orders-page__filter {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.85rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.supplier-orders-page__filter:hover { border-color: var(--color-ink); }
.supplier-orders-page__filter:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.supplier-orders-page__filter[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }
</style>
