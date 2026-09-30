<script setup>
import { computed, onMounted, ref } from 'vue';
import { Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import BaseSwitch from '../components/ui/BaseSwitch.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import CollectionForm from '../components/designs/CollectionForm.vue';
import DesignForm from '../components/designs/DesignForm.vue';
import DesignRows from '../components/designs/DesignRows.vue';
import GabaritManager from '../components/designs/GabaritManager.vue';
import WorkbenchCard from '../components/designs/WorkbenchCard.vue';
import { useDesignBoard, useGabarits } from '../composables/useDesigns.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { visit } from '../composables/useNavigation.js';
import { useToast } from '../composables/useToast.js';

const { board, load, createDesign, saveCollection, removeCollection, workOnCollection, validateCollection } = useDesignBoard();
const { gabarits, load: loadGabarits, save: saveGabarit } = useGabarits();
const { load: loadTypes } = useProductTypes();
const toast = useToast();
const { t } = useI18n();

const designOpen = ref(false);
const designCollectionId = ref('');
const collectionOpen = ref(false);
const editingCollection = ref(null);
const gabaritsOpen = ref(false);

const allDesigns = computed(() => [...(board.value?.collections.flatMap((c) => c.designs) ?? []), ...(board.value?.standalone ?? [])]);
const onBench = computed(() => allDesigns.value.filter((design) => design.current && design.status !== 'validated'));
const collections = computed(() => board.value?.collections ?? []);
const openDesigns = (collection) => collection.designs.filter((design) => design.status !== 'validated');
const readyToValidate = (collection) => openDesigns(collection).length > 0 && openDesigns(collection).every((design) => design.declinations.length > 0 && design.adaptationsDone === design.adaptationsTotal);

function newDesign(collectionId = '') {
    designCollectionId.value = collectionId;
    designOpen.value = true;
}

function openCollection(collection = null) {
    editingCollection.value = collection;
    collectionOpen.value = true;
}

async function onDesignSaved({ name, id }) {
    designOpen.value = false;
    toast.success(t('designs.page.started', { name }));
    visit(`/designs/${id}`);
}

async function onCollectionSaved(name) {
    collectionOpen.value = false;
    toast.success(t(editingCollection.value ? 'designs.page.collectionUpdated' : 'designs.page.collectionCreated', { name }));
    await load();
}

async function onRemoveCollection(collection) {
    await removeCollection(collection.id);
    toast.success(t('designs.page.collectionRemoved', { name: collection.name }));
    await load();
}

async function onBenchToggled(collection, current) {
    await workOnCollection(collection.id, current);
    await load();
}

async function onValidateCollection(collection) {
    try {
        const { productsCreated } = await validateCollection(collection.id);
        toast.success(t('designs.page.collectionValidated', { name: collection.name, count: productsCreated }, productsCreated));
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

async function onGabaritSaved(name) {
    toast.success(t('designs.page.gabaritSaved', { name }));
    await Promise.all([loadGabarits(), loadTypes()]);
}

onMounted(() => Promise.all([load(), loadGabarits(), loadTypes()]));
</script>

<template>
    <AppLayout :title="t('designs.page.title')">
        <template #actions>
            <BaseButton variant="secondary" @click="gabaritsOpen = true">{{ t('designs.page.gabarits') }}</BaseButton>
            <BaseButton variant="secondary" @click="openCollection()">{{ t('designs.page.newCollection') }}</BaseButton>
            <BaseButton @click="newDesign()">{{ t('designs.page.newDesign') }}</BaseButton>
        </template>

        <div v-if="board" class="designs-page">
            <section class="designs-page__bench" aria-labelledby="bench-title">
                <h2 id="bench-title" class="designs-page__heading">{{ t('designs.onBench') }}</h2>
                <div v-if="onBench.length" class="designs-page__cards">
                    <WorkbenchCard v-for="design in onBench" :key="design.id" :design="design" />
                </div>
                <EmptyState v-else>{{ t('designs.page.benchEmpty') }}</EmptyState>
            </section>

            <BaseCard v-for="collection in collections" :key="collection.id" class="designs-page__collection">
                <header class="designs-page__collection-header">
                    <div>
                        <h2 class="designs-page__collection-name">{{ collection.name }}</h2>
                        <p v-if="collection.description" class="designs-page__description">{{ collection.description }}</p>
                    </div>
                    <label class="designs-page__bench-switch">
                        <BaseSwitch :model-value="collection.current" @update:model-value="onBenchToggled(collection, $event)" />
                        {{ t('designs.onBench') }}
                    </label>
                </header>
                <DesignRows :designs="collection.designs" :empty="t('designs.page.collectionEmpty')" />
                <footer class="designs-page__collection-actions">
                    <ConfirmButton
                        :icon="Trash2"
                        :label="t('designs.page.removeCollection', { name: collection.name })"
                        :message="t('designs.page.removeCollectionMessage', { count: collection.designs.length }, collection.designs.length)"
                        @confirm="onRemoveCollection(collection)"
                    />
                    <BaseButton variant="ghost" @click="openCollection(collection)">{{ t('designs.page.edit') }}</BaseButton>
                    <BaseButton variant="secondary" @click="newDesign(collection.id)">{{ t('designs.page.addDesign') }}</BaseButton>
                    <ConfirmButton
                        v-if="readyToValidate(collection)"
                        variant="primary"
                        :label="t('designs.page.validateCollection')"
                        :confirm-label="t('designs.page.validate')"
                        :message="t('designs.page.validateCollectionMessage', { name: collection.name, count: openDesigns(collection).length }, openDesigns(collection).length)"
                        @confirm="onValidateCollection(collection)"
                    />
                </footer>
            </BaseCard>

            <BaseCard :title="t('designs.noCollection')">
                <DesignRows :designs="board.standalone" :empty="t('designs.page.standaloneEmpty')" />
            </BaseCard>
        </div>

        <BaseModal v-model:open="designOpen" :title="t('designs.page.newDesign')">
            <DesignForm :collections="collections" :gabarits="gabarits" :collection-id="designCollectionId" :submit="createDesign" @saved="onDesignSaved" @cancel="designOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="collectionOpen" :title="editingCollection ? t('designs.page.editCollection') : t('designs.page.newCollection')">
            <CollectionForm :collection="editingCollection" :submit="(payload) => saveCollection(editingCollection?.id, payload)" @saved="onCollectionSaved" @cancel="collectionOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="gabaritsOpen" :title="t('designs.page.gabarits')">
            <GabaritManager :gabarits="gabarits" :save="saveGabarit" @saved="onGabaritSaved" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.designs-page { display: flex; flex-direction: column; gap: var(--space-5); }
.designs-page__heading { margin: 0 0 var(--space-3); font-size: 1.35rem; }
.designs-page__cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); gap: var(--space-3); }
.designs-page__collection-header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); margin-bottom: var(--space-2); }
.designs-page__collection-name { margin: 0; font-size: 1.2rem; }
.designs-page__description { margin: var(--space-1) 0 0; color: var(--color-muted); font-size: 0.9rem; }
.designs-page__bench-switch { display: inline-flex; align-items: center; gap: var(--space-2); font-size: 0.85rem; white-space: nowrap; }
.designs-page__collection-actions { display: flex; justify-content: flex-end; gap: var(--space-2); margin-top: var(--space-3); }
</style>
