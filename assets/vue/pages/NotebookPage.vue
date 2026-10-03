<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import NotebookPages from '../components/notebook/NotebookPages.vue';
import NotebookReport from '../components/notebook/NotebookReport.vue';
import { useEvent } from '../composables/useEvents.js';
import { useNotebook, useNotebookPages } from '../composables/useNotebook.js';
import { useToast } from '../composables/useToast.js';
import { formatDateTime } from '../composables/useDate.js';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, load: loadEvent } = useEvent(props.eventId);
const notebook = useNotebook(props.eventId);
const { pages, addPhotos, addTyped, remove, clear, write } = useNotebookPages(notebook.recognize);
const toast = useToast();
const { t } = useI18n();

const report = ref(null);
const rescanning = ref(false);
const scanning = ref(false);
const error = ref(null);
const showUpload = computed(() => report.value === null || rescanning.value);
const ready = computed(() => pages.value.length > 0 && pages.value.every((page) => !page.reading) && pages.value.some((page) => page.text.trim() !== ''));

async function onAnalyse() {
    scanning.value = true;
    error.value = null;
    try {
        await notebook.scan(pages.value.filter((page) => page.text.trim() !== '').map((page) => page.text));
        await notebook.load(report);
        toast.success(t('notebook.page.analysed', report.value.summary.entries));
        clear();
        rescanning.value = false;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        scanning.value = false;
    }
}

function cancelRescan() {
    rescanning.value = false;
    clear();
    error.value = null;
}

onMounted(() => Promise.all([notebook.load(report), loadEvent()]));
</script>

<template>
    <AppLayout :title="t('notebook.page.title')">
        <template #back><a class="back-link" :href="`/events/${eventId}`"><ArrowLeft size="0.875rem" aria-hidden="true" /> {{ event?.name ?? t('notebook.page.eventFallback') }}</a></template>
        <template #actions>
            <BaseButton v-if="report && !rescanning" variant="secondary" @click="rescanning = true">{{ t('notebook.page.newScan') }}</BaseButton>
        </template>

        <div class="notebook-page">
            <BaseCard v-if="showUpload">
                <i18n-t keypath="notebook.page.intro" tag="p" class="notebook-page__intro" scope="global">
                    <template #settings><a href="/settings">{{ t('notebook.page.settingsLink') }}</a></template>
                </i18n-t>
                <NotebookPages :pages="pages" @add-photos="addPhotos" @add-typed="addTyped" @remove="remove" @write="write" />
                <p v-if="error" class="notebook-page__error" role="alert">{{ error }}</p>
                <div class="notebook-page__actions">
                    <BaseButton v-if="rescanning" variant="ghost" :disabled="scanning" @click="cancelRescan">{{ t('notebook.page.cancel') }}</BaseButton>
                    <BaseButton :loading="scanning" :disabled="!ready" @click="onAnalyse">{{ t('notebook.page.analyse') }}</BaseButton>
                </div>
            </BaseCard>

            <template v-if="report">
                <p class="notebook-page__scanned">{{ t('notebook.page.scannedAt', { date: formatDateTime(report.scannedAt), pages: t('notebook.page.pages', report.pages) }) }}</p>
                <NotebookReport :report="report" />
            </template>
        </div>
    </AppLayout>
</template>

<style scoped>
.notebook-page { display: flex; flex-direction: column; gap: var(--space-4); }
.notebook-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); }
.notebook-page__error { margin: var(--space-3) 0 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
.notebook-page__actions { display: flex; justify-content: flex-end; gap: var(--space-2); margin-top: var(--space-4); }
.notebook-page__scanned { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
