<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import NotebookPhotos from '../components/notebook/NotebookPhotos.vue';
import NotebookReport from '../components/notebook/NotebookReport.vue';
import { useEvent } from '../composables/useEvents.js';
import { useNotebook } from '../composables/useNotebook.js';
import { useToast } from '../composables/useToast.js';
import { formatDateTime } from '../composables/useDate.js';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, load: loadEvent } = useEvent(props.eventId);
const notebook = useNotebook(props.eventId);
const toast = useToast();
const { t } = useI18n();

const report = ref(null);
const photos = ref([]);
const rescanning = ref(false);
const scanning = ref(false);
const error = ref(null);
const showUpload = computed(() => report.value === null || rescanning.value);

async function onAnalyse() {
    scanning.value = true;
    error.value = null;
    try {
        await notebook.scan(photos.value);
        await notebook.load(report);
        toast.success(t('notebook.page.analysed', report.value.summary.entries));
        photos.value = [];
        rescanning.value = false;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        scanning.value = false;
    }
}

function cancelRescan() {
    rescanning.value = false;
    photos.value = [];
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
                <p class="notebook-page__intro">{{ t('notebook.page.intro') }}</p>
                <NotebookPhotos v-model="photos" />
                <p v-if="error" class="notebook-page__error" role="alert">{{ error }}</p>
                <p v-if="scanning" class="notebook-page__progress" role="status">{{ t('notebook.page.analysing') }}</p>
                <div class="notebook-page__actions">
                    <BaseButton v-if="rescanning" variant="ghost" :disabled="scanning" @click="cancelRescan">{{ t('notebook.page.cancel') }}</BaseButton>
                    <BaseButton :loading="scanning" :disabled="photos.length === 0" @click="onAnalyse">{{ t('notebook.page.analyse') }}</BaseButton>
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
.notebook-page__progress { margin: var(--space-3) 0 0; color: var(--color-muted); }
.notebook-page__actions { display: flex; justify-content: flex-end; gap: var(--space-2); margin-top: var(--space-4); }
.notebook-page__scanned { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
</style>
