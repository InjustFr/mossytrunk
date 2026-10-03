<script setup>
import { useI18n } from 'vue-i18n';
import BaseButton from './BaseButton.vue';

defineProps({
    count: { type: Number, required: true },
    summary: { type: String, required: true },
});
const emit = defineEmits(['clear']);
const { t } = useI18n();
</script>

<template>
    <Transition name="selection-bar">
        <div v-if="count > 0" class="selection-bar" role="region" :aria-label="t('ui.selection.label')">
            <span class="selection-bar__count">{{ summary }}</span>
            <slot />
            <BaseButton variant="ghost" @click="emit('clear')">{{ t('ui.selection.clear') }}</BaseButton>
        </div>
    </Transition>
</template>

<style scoped>
.selection-bar {
    position: sticky;
    bottom: var(--space-4);
    z-index: 5;
    display: flex;
    align-items: center;
    gap: var(--space-3);
    width: fit-content;
    margin: var(--space-4) auto 0;
    padding: var(--space-2) var(--space-2) var(--space-2) var(--space-4);
    background: var(--color-ink);
    color: var(--color-surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.selection-bar__count { font-weight: 600; }
.selection-bar :deep(.button--ghost) { color: color-mix(in oklch, var(--color-surface) 85%, var(--color-ink)); }

.selection-bar-enter-active,
.selection-bar-leave-active { transition: opacity var(--transition), transform var(--transition); }
.selection-bar-enter-from,
.selection-bar-leave-to { opacity: 0; transform: translateY(0.5rem); }
</style>
