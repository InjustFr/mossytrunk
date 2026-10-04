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
import { useI18n } from 'vue-i18n';
import BaseButton from './BaseButton.vue';
import IconButton from './IconButton.vue';

const { t } = useI18n();

defineProps({
    label: { type: String, required: true },
    confirmLabel: { type: String, default: undefined },
    message: { type: String, default: undefined },
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
            <AlertDialogOverlay class="modal modal--dialog confirm-dialog">
                <AlertDialogContent class="modal__panel confirm-dialog__panel">
                    <AlertDialogTitle class="modal__title">{{ label }}</AlertDialogTitle>
                    <AlertDialogDescription class="confirm-dialog__message">{{ message ?? t('ui.confirm.message') }}</AlertDialogDescription>
                    <div class="actions-row confirm-dialog__actions">
                        <AlertDialogCancel as-child><BaseButton variant="ghost">{{ t('ui.cancel') }}</BaseButton></AlertDialogCancel>
                        <AlertDialogAction as-child><BaseButton variant="danger" @click="emit('confirm')">{{ confirmLabel ?? t('ui.confirm.action') }}</BaseButton></AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialogOverlay>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>

<style>
.modal.confirm-dialog { z-index: 80; padding-top: 20vh; }
.modal__panel.confirm-dialog__panel { gap: var(--space-3); width: min(25rem, 100%); padding: var(--space-5); }
.confirm-dialog__message { margin: 0; color: var(--color-muted); }
.confirm-dialog__actions { margin-top: var(--space-2); }
</style>
