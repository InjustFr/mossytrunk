<script setup>
import { Pencil } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import IconButton from '../ui/IconButton.vue';

const { t } = useI18n();

defineProps({
    formats: { type: Array, required: true },
});
const emit = defineEmits(['edit']);
</script>

<template>
    <ul class="reference-formats">
        <li v-for="format in formats" :key="format.kind" class="reference-formats__row">
            <div class="reference-formats__text">
                <h3 class="reference-formats__title">{{ t(`settings.references.titles.${format.kind}`) }}</h3>
                <code class="reference-formats__template">{{ format.template }}</code>
                <p class="reference-formats__example">{{ t('settings.references.example') }} <strong>{{ format.example }}</strong></p>
            </div>
            <IconButton :icon="Pencil" :label="t('settings.references.edit', { kind: t(`settings.references.kinds.${format.kind}`) })" @click="emit('edit', format)" />
        </li>
    </ul>
</template>

<style scoped>
.reference-formats { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }

.reference-formats__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-3) 0;
    border-top: 0.0625rem solid var(--color-border);
}

.reference-formats__text { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; }
.reference-formats__title { margin: 0; font-size: 0.9375rem; }
.reference-formats__template { font-size: 0.875rem; color: var(--color-ink); overflow-wrap: anywhere; }
.reference-formats__example { margin: 0; color: var(--color-muted); font-size: 0.8125rem; }
.reference-formats__example strong { color: var(--color-ink); font-weight: 500; }
</style>
