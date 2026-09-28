<script setup>
import BaseButton from '../ui/BaseButton.vue';

defineProps({
    count: { type: Number, required: true },
});
const emit = defineEmits(['edit', 'clear']);
</script>

<template>
    <Transition name="selection-bar">
        <div v-if="count > 0" class="selection-bar" role="region" aria-label="Sélection">
            <span class="selection-bar__count">{{ count }} produit(s) sélectionné(s)</span>
            <BaseButton @click="emit('edit')">Modifier la sélection</BaseButton>
            <BaseButton variant="ghost" @click="emit('clear')">Désélectionner</BaseButton>
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
    color: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.selection-bar__count { font-weight: 600; }
.selection-bar :deep(.button--ghost) { color: #ddd; }

.selection-bar-enter-active,
.selection-bar-leave-active { transition: opacity var(--transition), transform var(--transition); }
.selection-bar-enter-from,
.selection-bar-leave-to { opacity: 0; transform: translateY(8px); }
</style>
