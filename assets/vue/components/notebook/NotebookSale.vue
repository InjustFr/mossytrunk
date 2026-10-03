<script setup>
import { useI18n } from 'vue-i18n';
import StatusBadge from '../ui/StatusBadge.vue';

defineProps({
    entry: { type: Object, required: true },
});
const { t } = useI18n();
</script>

<template>
    <div class="notebook-sale">
        <p class="notebook-sale__title">{{ t('notebook.report.sale', { number: entry.number, page: entry.page }) }}</p>
        <ul class="notebook-sale__lines">
            <li v-for="(line, index) in entry.lines" :key="index" class="notebook-sale__line">
                <span>{{ line.quantity }} × {{ line.label }}</span>
                <span v-if="line.written !== line.label" class="notebook-sale__written">{{ t('notebook.report.written', { written: line.written }) }}</span>
                <StatusBadge v-if="!line.identified" tone="warning">{{ t('notebook.report.unidentified') }}</StatusBadge>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.notebook-sale__title { margin: 0 0 var(--space-1); color: var(--color-muted); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04rem; text-transform: uppercase; }
.notebook-sale__lines { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }
.notebook-sale__line { display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--space-2); }
.notebook-sale__written { color: var(--color-muted); font-size: 0.85rem; font-style: italic; }
</style>
