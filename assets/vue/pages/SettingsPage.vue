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
import ReferenceFormats from '../components/settings/ReferenceFormats.vue';
import ReferenceFormatForm from '../components/settings/ReferenceFormatForm.vue';
import SalesChannelForm from '../components/settings/SalesChannelForm.vue';
import SalesChannels from '../components/settings/SalesChannels.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import { useToast } from '../composables/useToast.js';
import { useServices } from '../composables/useServices.js';
import { useWorkspaceSettings } from '../composables/useWorkspaceSettings.js';
import { useReferenceFormats } from '../composables/useReferenceFormats.js';
import { useSalesChannels } from '../composables/useSalesChannels.js';

const { t } = useI18n();

const { settings, load } = useWorkspaceSettings();
const services = useServices();
const toast = useToast();
const references = useReferenceFormats();
const salesChannels = useSalesChannels();

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

const importingCatalogue = ref(null);

async function onImportCatalogue(service, importing) {
    importingCatalogue.value = service.key;
    try {
        const report = await importing();
        toast.success([
            t('settings.connected.catalogueImported', { label: service.label, linked: report.itemsLinked, read: report.itemsRead }, report.itemsLinked),
            report.productsCreated > 0 ? t('settings.connected.catalogueCreated', report.productsCreated) : '',
            report.itemsToLink > 0 ? t('settings.connected.catalogueToLink', report.itemsToLink) : '',
        ].filter(Boolean).join(' '));
    } catch (error) {
        toast.error(error.message);
    } finally {
        importingCatalogue.value = null;
    }
}

async function onPublishReferences(service) {
    try {
        const published = await services.publishReferences(service.key);
        toast.success(t('settings.connected.referencesPublished', { label: service.label, linked: published.itemsLinked, updated: published.listingsUpdated }, published.listingsUpdated));
    } catch (error) {
        toast.error(error.message);
    }
}

const editingFormat = ref(null);
const formatModalOpen = ref(false);
const formatKind = computed(() => (editingFormat.value ? t(`settings.references.kinds.${editingFormat.value.kind}`) : ''));

function openFormat(format) {
    editingFormat.value = format;
    formatModalOpen.value = true;
}

async function onFormatSaved(renamed) {
    formatModalOpen.value = false;
    toast.success(renamed > 0
        ? t('settings.references.renamed', { kind: formatKind.value, count: renamed }, renamed)
        : t('settings.references.saved', { kind: formatKind.value }));
    await references.load();
}

const editingChannel = ref(null);
const channelModalOpen = ref(false);

function openChannel(channel) {
    editingChannel.value = channel;
    channelModalOpen.value = true;
}

const saveChannel = (payload) => (editingChannel.value ? salesChannels.update(editingChannel.value.id, payload) : salesChannels.create(payload));

async function onChannelSaved(name) {
    toast.success(t(editingChannel.value ? 'settings.channels.updated' : 'settings.channels.created', { name }));
    channelModalOpen.value = false;
    await salesChannels.load();
}

async function onChannelRemoved(channel) {
    try {
        await salesChannels.remove(channel.id);
        toast.success(t('settings.channels.removed', { name: channel.name }));
    } catch (error) {
        toast.error(error.message);
    }
    await salesChannels.load();
}

const onRemove = (service) => run(() => services.remove(service.key), t('settings.services.removed', { label: service.label }));
const onDisconnect = (service) => run(() => services.disconnect(service.key), t('settings.services.disconnected', { label: service.label }));

onMounted(async () => {
    await Promise.all([load(), services.load(), references.load(), salesChannels.load()]);
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
                    :importing="importingCatalogue"
                    @import-catalogue="(service, file) => onImportCatalogue(service, () => services.importCatalogue(service.key, file))"
                    @read-catalogue="(service) => onImportCatalogue(service, () => services.readCatalogue(service.key))"
                    @publish-references="onPublishReferences"
                />
                <EmptyState v-else>{{ t('settings.services.empty', { available }) }}</EmptyState>
            </BaseCard>
            <BaseCard :title="t('settings.channels.title')">
                <template #actions>
                    <BaseButton variant="secondary" @click="openChannel(null)"><Plus size="1rem" aria-hidden="true" /> {{ t('settings.channels.add') }}</BaseButton>
                </template>
                <p class="settings-page__intro">{{ t('settings.channels.intro') }}</p>
                <SalesChannels v-if="salesChannels.channels.value.length" :channels="salesChannels.channels.value" @edit="openChannel" @remove="onChannelRemoved" />
                <EmptyState v-else>{{ t('settings.channels.empty') }}</EmptyState>
            </BaseCard>
            <BaseCard :title="t('settings.references.title')">
                <p class="settings-page__intro">{{ t('settings.references.intro') }}</p>
                <ReferenceFormats :formats="references.formats.value" @edit="openFormat" />
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
        <BaseModal v-model:open="channelModalOpen" :title="editingChannel ? t('settings.channels.editTitle', { name: editingChannel.name }) : t('settings.channels.add')">
            <SalesChannelForm
                v-if="channelModalOpen"
                :key="editingChannel?.id ?? 'new'"
                :channel="editingChannel"
                :services="services.services.value"
                :submit="saveChannel"
                @saved="onChannelSaved"
                @cancel="channelModalOpen = false"
            />
        </BaseModal>
        <BaseModal v-model:open="formatModalOpen" :title="t('settings.references.edit', { kind: formatKind })">
            <ReferenceFormatForm
                v-if="editingFormat"
                :key="editingFormat.kind"
                :format="editingFormat"
                :preview="references.preview"
                :change="references.change"
                @saved="onFormatSaved"
                @cancel="formatModalOpen = false"
            />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.settings-page { display: flex; flex-direction: column; gap: var(--space-4); }
.settings-page__workspace { margin: 0; color: var(--color-muted); }
.settings-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); font-size: 0.9rem; }
</style>
