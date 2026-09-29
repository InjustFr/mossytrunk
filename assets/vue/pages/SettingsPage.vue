<script setup>
import { onMounted } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import SumUpSettingsForm from '../components/settings/SumUpSettingsForm.vue';
import DeleteAllData from '../components/settings/DeleteAllData.vue';
import { useToast } from '../composables/useToast.js';
import { useWorkspaceSettings } from '../composables/useWorkspaceSettings.js';
import { useOrders } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';

const { settings, load, saveSumUp, removeSumUpApiKey } = useWorkspaceSettings();
const { removeAll: removeAllOrders } = useOrders();
const { removeAll: removeAllProducts } = useProducts();
const toast = useToast();

async function onSaved() {
    toast.success('Paramètres SumUp enregistrés.');
    await load();
}

async function onRemoved() {
    toast.success('Clé API SumUp supprimée.');
    await load();
}

async function deleteAll(removeAll, message) {
    try {
        const { deleted } = await removeAll();
        toast.success(message(deleted));
    } catch (error) {
        toast.error(error.message);
    }
}

const ordersDeleted = (count) => `Commandes supprimées : ${count}.`;
const productsDeleted = (count) => `Produits supprimés : ${count}.`;

onMounted(load);
</script>

<template>
    <AppLayout title="Paramètres">
        <div v-if="settings" class="settings-page">
            <p class="settings-page__workspace">Espace de travail <strong>{{ settings.name }}</strong></p>
            <BaseCard title="SumUp">
                <SumUpSettingsForm :sum-up="settings.sumUp" :submit="saveSumUp" :remove-api-key="removeSumUpApiKey" @saved="onSaved" @removed="onRemoved" />
            </BaseCard>
            <BaseCard title="Zone de danger">
                <DeleteAllData @delete-orders="deleteAll(removeAllOrders, ordersDeleted)" @delete-products="deleteAll(removeAllProducts, productsDeleted)" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.settings-page { display: flex; flex-direction: column; gap: var(--space-4); }
.settings-page__workspace { margin: 0; color: var(--color-muted); }
</style>
