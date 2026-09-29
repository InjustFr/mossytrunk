<script setup>
import { computed, onMounted, ref } from 'vue';
import { Plus } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import ConnectedServices from '../components/settings/ConnectedServices.vue';
import ServiceModal from '../components/settings/ServiceModal.vue';
import DeleteAllData from '../components/settings/DeleteAllData.vue';
import { useToast } from '../composables/useToast.js';
import { useServices } from '../composables/useServices.js';
import { useWorkspaceSettings } from '../composables/useWorkspaceSettings.js';
import { useOrders } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';

const { settings, load } = useWorkspaceSettings();
const services = useServices();
const { removeAll: removeAllOrders } = useOrders();
const { removeAll: removeAllProducts } = useProducts();
const toast = useToast();

const modalOpen = ref(false);
const editing = ref(null);
const allAdded = computed(() => services.services.value.every((service) => service.connection));
const available = computed(() => services.services.value.map((service) => service.label).join(' ou '));

const CONNECTION_OUTCOMES = {
    connecte: ['success', (label) => `${label} connecté : ses commandes s'importent depuis la page Commandes.`],
    refuse: ['error', (label) => `La connexion à ${label} a été annulée.`],
    erreur: ['error', (label) => `${label} n'a pas pu confirmer la connexion. Réessayez.`],
    indisponible: ['error', (label) => `Complétez d'abord les accès de ${label}.`],
};

function announceConnectionOutcome() {
    const params = new URLSearchParams(window.location.search);
    const outcome = CONNECTION_OUTCOMES[params.get('connexion')];
    if (!outcome) return;
    const service = services.services.value.find((candidate) => candidate.key === params.get('service'));
    toast[outcome[0]](outcome[1](service?.label ?? 'Le service'));
    params.delete('connexion');
    params.delete('service');
    window.history.replaceState(window.history.state, '', `${window.location.pathname}${params.size ? `?${params}` : ''}`);
}

function openAdd() {
    editing.value = null;
    modalOpen.value = true;
}

function openEdit(service) {
    editing.value = service;
    modalOpen.value = true;
}

async function onSaved(service, added) {
    toast.success(`${service.label} ${added ? 'ajouté' : 'modifié'}.`);
    await services.load();
}

async function run(action, message) {
    try {
        await action();
        toast.success(message);
    } catch (error) {
        toast.error(error.message);
    }
    await services.load();
}

const onRemove = (service) => run(() => services.remove(service.key), `${service.label} retiré.`);
const onDisconnect = (service) => run(() => services.disconnect(service.key), `${service.label} déconnecté.`);

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

onMounted(async () => {
    await Promise.all([load(), services.load()]);
    announceConnectionOutcome();
});
</script>

<template>
    <AppLayout title="Paramètres">
        <div v-if="settings" class="settings-page">
            <p class="settings-page__workspace">Espace de travail <strong>{{ settings.name }}</strong></p>
            <BaseCard title="Services connectés">
                <template #actions>
                    <BaseButton v-if="!allAdded" variant="secondary" @click="openAdd"><Plus size="1rem" aria-hidden="true" /> Ajouter un service</BaseButton>
                </template>
                <p class="settings-page__intro">Vos ventes arrivent dans Commandes quand vous lancez un import.</p>
                <ConnectedServices
                    v-if="services.added.value.length"
                    :services="services.added.value"
                    @edit="openEdit"
                    @remove="onRemove"
                    @disconnect="onDisconnect"
                />
                <EmptyState v-else>Aucun service. Ajoutez {{ available }} pour importer vos ventes.</EmptyState>
            </BaseCard>
            <BaseCard title="Zone de danger">
                <DeleteAllData @delete-orders="deleteAll(removeAllOrders, ordersDeleted)" @delete-products="deleteAll(removeAllProducts, productsDeleted)" />
            </BaseCard>
        </div>
        <ServiceModal
            v-model:open="modalOpen"
            :services="services.services.value"
            :editing="editing"
            :add="services.add"
            :update="services.update"
            @saved="onSaved"
        />
    </AppLayout>
</template>

<style scoped>
.settings-page { display: flex; flex-direction: column; gap: var(--space-4); }
.settings-page__workspace { margin: 0; color: var(--color-muted); }
.settings-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); font-size: 0.9rem; }
</style>
