<script setup>
import {
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
    AlertDialogTrigger,
} from 'reka-ui';
import BaseButton from './BaseButton.vue';
import IconButton from './IconButton.vue';

defineProps({
    label: { type: String, required: true },
    confirmLabel: { type: String, default: 'Confirmer ?' },
    message: { type: String, default: 'Cette action est définitive.' },
    icon: { type: [Object, Function], default: null },
    variant: { type: String, default: 'ghost' },
});
const emit = defineEmits(['confirm']);
</script>

<template>
    <AlertDialogRoot>
        <AlertDialogTrigger as-child>
            <IconButton v-if="icon" :icon="icon" :label="label" variant="danger" />
            <BaseButton v-else :variant="variant">{{ label }}</BaseButton>
        </AlertDialogTrigger>
        <AlertDialogPortal>
            <AlertDialogOverlay class="confirm-dialog">
                <AlertDialogContent class="confirm-dialog__panel">
                    <AlertDialogTitle class="confirm-dialog__title">{{ label }}</AlertDialogTitle>
                    <AlertDialogDescription class="confirm-dialog__message">{{ message }}</AlertDialogDescription>
                    <div class="confirm-dialog__actions">
                        <AlertDialogCancel as-child><BaseButton variant="ghost">Annuler</BaseButton></AlertDialogCancel>
                        <AlertDialogAction as-child><BaseButton variant="danger" @click="emit('confirm')">{{ confirmLabel }}</BaseButton></AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialogOverlay>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>

<style>
.confirm-dialog {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 20vh var(--space-4) var(--space-4);
    background: var(--color-backdrop);
}

.confirm-dialog[data-state="open"] { animation: confirm-dialog-fade var(--transition); }

.confirm-dialog__panel {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    width: min(25rem, 100%);
    padding: var(--space-5);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.confirm-dialog__panel:focus { outline: none; }
.confirm-dialog__panel[data-state="open"] { animation: confirm-dialog-rise var(--transition); }

.confirm-dialog__title { margin: 0; font-family: var(--font-display); font-weight: 400; font-size: 1.25rem; }
.confirm-dialog__message { margin: 0; color: var(--color-muted); }
.confirm-dialog__actions { display: flex; justify-content: flex-end; gap: var(--space-2); margin-top: var(--space-2); }

@keyframes confirm-dialog-fade { from { opacity: 0; } }
@keyframes confirm-dialog-rise { from { transform: translateY(0.5rem) scale(0.98); } }
</style>
