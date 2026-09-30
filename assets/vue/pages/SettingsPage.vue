<script setup>
import { computed, onMounted, ref } from 'vue';
import { Plus } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const { settings, load } = useWorkspaceSettings();
const services = useServices();
const { removeAll: removeAllOrders } = useOrders();
const { removeAll: removeAllProducts } = useProducts();
const toast = useToast();

const modalOpen = ref(false);
const editing = ref(null);
const allAdded = computed(() => services.services.value.every((service) => service.connection));
const available = computed(() => services.services.value.map((service) => service.label).join(t('settings.services.or')));

const CONNECTION_OUTCOMES = {
    connected: 'success',
    refused: 'error',
    error: 'error',
    unavailable: 'error',
};

function announceConnectionOutcome() {
    const params = new URLSearchParams(window.location.search);
    const outcome = params.get('connection');
    const tone = CONNECTION_OUTCOMES[outcome];
    if (!tone) return;
    const service = services.services.value.find((candidate) => candidate.key === params.get('service'));
    toast[tone](t(`settings.services.outcome.${outcome}`, { label: service?.label ?? t('settings.services.someService') }));
    params.delete('connection');
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
    toast.success(t(added ? 'settings.services.added' : 'settings.services.updated', { label: service.label }));
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

const onRemove = (service) => run(() => services.remove(service.key), t('settings.services.removed', { label: service.label }));
const onDisconnect = (service) => run(() => services.disconnect(service.key), t('settings.services.disconnected', { label: service.label }));

async function deleteAll(removeAll, message) {
    try {
        const { deleted } = await removeAll();
        toast.success(message(deleted));
    } catch (error) {
        toast.error(error.message);
    }
}

const ordersDeleted = (count) => t('settings.danger.orders.deleted', { count });
const productsDeleted = (count) => t('settings.danger.products.deleted', { count });

onMounted(async () => {
    await Promise.all([load(), services.load()]);
    announceConnectionOutcome();
});
</script>

<template>
    <AppLayout :title="t('settings.title')">
        <div v-if="settings" class="settings-page">
            <p class="settings-page__workspace">{{ t('settings.workspace') }} <strong>{{ settings.name }}</strong></p>
            <BaseCard :title="t('settings.services.title')">
                <template #actions>
                    <BaseButton v-if="!allAdded" variant="secondary" @click="openAdd"><Plus size="1rem" aria-hidden="true" /> {{ t('settings.services.add') }}</BaseButton>
                </template>
                <p class="settings-page__intro">{{ t('settings.services.intro') }}</p>
                <ConnectedServices
                    v-if="services.added.value.length"
                    :services="services.added.value"
                    @edit="openEdit"
                    @remove="onRemove"
                    @disconnect="onDisconnect"
                />
                <EmptyState v-else>{{ t('settings.services.empty', { available }) }}</EmptyState>
            </BaseCard>
            <BaseCard :title="t('settings.danger.title')">
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
