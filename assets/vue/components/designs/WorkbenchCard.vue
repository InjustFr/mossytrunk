<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    design: { type: Object, required: true },
});

const { t } = useI18n();

const progress = computed(() => (props.design.adaptationsTotal === 0 ? 1 : props.design.adaptationsDone / props.design.adaptationsTotal));
const remaining = computed(() => props.design.adaptationsTotal - props.design.adaptationsDone);
</script>

<template>
    <a :href="`/designs/${design.id}`" class="workbench-card">
        <span v-if="design.collection" class="workbench-card__collection">{{ design.collection.name }}</span>
        <span class="workbench-card__name">{{ design.name }}</span>
        <span class="workbench-card__gabarits">
            <span v-for="declination in design.declinations" :key="declination.id" :class="['workbench-card__gabarit', { 'workbench-card__gabarit--ready': declination.ready }]">
                {{ declination.gabarit.name }}
            </span>
            <span v-if="design.declinations.length === 0" class="workbench-card__empty">{{ t('designs.notDeclined') }}</span>
        </span>
        <span class="workbench-card__rail" role="progressbar" :aria-valuenow="design.adaptationsDone" :aria-valuemax="design.adaptationsTotal" :aria-label="t('designs.workbench.progress', { name: design.name })">
            <span class="workbench-card__fill" :style="{ width: `${progress * 100}%` }" />
        </span>
        <span class="workbench-card__status">
            <template v-if="design.declinations.length === 0">{{ t('designs.workbench.chooseGabarits') }}</template>
            <template v-else-if="remaining > 0">{{ t('designs.workbench.remaining', remaining) }}</template>
            <template v-else>{{ t('designs.workbench.ready') }}</template>
        </span>
    </a>
</template>

<style scoped>
.workbench-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-4);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-text);
    text-decoration: none;
    transition: border-color var(--transition);
}

.workbench-card:hover { border-color: var(--color-ink); }
.workbench-card__collection { color: var(--color-muted); font-size: 0.8rem; }
.workbench-card__name { font-family: var(--font-display); font-size: 1.5rem; line-height: 1.15; color: var(--color-ink); }
.workbench-card__gabarits { display: flex; flex-wrap: wrap; gap: var(--space-1); }
.workbench-card__gabarit { padding: 0.0625rem var(--space-2); border: 0.0625rem dashed var(--color-border-strong); border-radius: var(--radius-pill); font-size: 0.75rem; color: var(--color-muted); }
.workbench-card__gabarit--ready { border-style: solid; border-color: var(--color-accent); color: var(--color-accent-strong); }
.workbench-card__empty { color: var(--color-subtle); font-size: 0.8rem; }
.workbench-card__rail { display: block; height: 0.25rem; margin-top: auto; border-radius: var(--radius-pill); background: var(--color-bg); overflow: hidden; }
.workbench-card__fill { display: block; height: 100%; background: var(--color-accent); transition: width var(--transition); }
.workbench-card__status { color: var(--color-muted); font-size: 0.8rem; }
</style>
