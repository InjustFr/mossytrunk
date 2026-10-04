<script setup>
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import ColorPanel from './ColorPanel.vue';

defineProps({
    label: { type: String, required: true },
});
const color = defineModel({ type: String, required: true });
const emit = defineEmits(['commit']);

function onOpen(open) {
    if (!open) emit('commit', color.value);
}
</script>

<template>
    <PopoverRoot @update:open="onOpen">
        <PopoverTrigger class="control color-picker" :aria-label="label">
            <span class="color-picker__swatch" :style="{ background: color }" aria-hidden="true" />
            <span class="color-picker__value">{{ color.toUpperCase() }}</span>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent class="popover color-picker__content" side="bottom" align="start" :side-offset="8" :aria-label="label">
                <ColorPanel v-model="color" />
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>

<style scoped>
.color-picker {
    display: inline-flex;
    width: auto;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-3) var(--space-1) var(--space-1);
    color: var(--color-ink);
    cursor: pointer;
}

.color-picker:hover,
.color-picker[data-state='open'] { border-color: var(--color-ink); }

.color-picker__swatch { width: 1.75rem; height: 1.75rem; border: 0.0625rem solid var(--color-border); border-radius: var(--radius-inner); }
.color-picker__value { font-variant-numeric: tabular-nums; }
</style>

<style>
.color-picker__content {
    width: 15rem;
    padding: var(--space-3);
}
</style>
