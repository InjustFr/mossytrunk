<script setup>
import { computed, onMounted, ref } from 'vue';
import { Plus } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import AppearanceSettings from '../components/settings/AppearanceSettings.vue';
import ConnectedServices from '../components/settings/ConnectedServices.vue';
import ServiceModal from '../components/settings/ServiceModal.vue';
import ReferenceFormats from '../components/settings/ReferenceFormats.vue';
import ReferenceFormatForm from '../components/settings/ReferenceFormatForm.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import { takeQuery } from '../composables/useQueryState.js';
import { useToast } from '../composables/useToast.js';
import { useServices } from '../composables/useServices.js';
import { useSession } from '../composables/useSession.js';
import { useReferenceFormats } from '../composables/useReferenceFormats.js';

const { t } = useI18n();

const session = useSession();
const services = useServices();
const toast = useToast();
const references = useReferenceFormats();

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
    const outcome = takeQuery('connection');
    const serviceKey = takeQuery('service');
    const tone = CONNECTION_OUTCOMES[outcome];
    if (!tone) return;
    const service = services.services.value.find((candidate) => candidate.key === serviceKey);
    toast[tone](t(`settings.services.outcome.${outcome}`, { label: service?.label ?? t('settings.services.someService') }));
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
    await toast.attempt(action, message);
    await services.load();
}

const importingCatalogue = ref(null);

async function onImportCatalogue(service, importing) {
    importingCatalogue.value = service.key;
    await toast.attempt(async () => {
        const report = await importing();
        toast.success([
            t('settings.connected.catalogueImported', { label: service.label, linked: report.itemsLinked, read: report.itemsRead }, report.itemsLinked),
            report.productsCreated > 0 ? t('settings.connected.catalogueCreated', report.productsCreated) : '',
            report.itemsToLink > 0 ? t('settings.connected.catalogueToLink', report.itemsToLink) : '',
        ].filter(Boolean).join(' '));
    });
    importingCatalogue.value = null;
}

const onPublishReferences = (service) => toast.attempt(async () => {
    const published = await services.publishReferences(service.key);
    toast.success(t('settings.connected.referencesPublished', { label: service.label, linked: published.itemsLinked, updated: published.listingsUpdated }, published.listingsUpdated));
});

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

const onRemove = (service) => run(() => services.remove(service.key), t('settings.services.removed', { label: service.label }));
const onDisconnect = (service) => run(() => services.disconnect(service.key), t('settings.services.disconnected', { label: service.label }));

onMounted(async () => {
    await Promise.all([services.load(), references.load()]);
    announceConnectionOutcome();
});
</script>

<template>
    <AppLayout :title="t('settings.title')">
        <div class="settings-page">
            <p class="settings-page__workspace">{{ t('settings.workspace') }} <strong>{{ session.workspace }}</strong></p>
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
            <BaseCard :title="t('settings.references.title')">
                <p class="settings-page__intro">{{ t('settings.references.intro') }}</p>
                <ReferenceFormats :formats="references.formats.value" @edit="openFormat" />
            </BaseCard>
            <BaseCard :title="t('settings.appearance.title')">
                <p class="settings-page__intro">{{ t('settings.appearance.intro') }}</p>
                <AppearanceSettings />
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
.settings-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); font-size: var(--font-size-md); }
</style>
