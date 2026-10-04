<script setup>
import { computed } from 'vue';
import { X } from '@lucide/vue';
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    title: { type: String, required: true },
    variant: { type: String, default: 'dialog' },
    focusField: { type: Boolean, default: true },
});
const open = defineModel('open', { type: Boolean, required: true });

const modal = computed(() => props.variant === 'dialog');

function focusFirstField(event) {
    if (!props.focusField) {
        event.preventDefault();
        event.target.focus({ preventScroll: true });
        return;
    }
    const field = event.target.querySelector('.modal__body :is(input, textarea, button, [role="combobox"])');
    if (field) {
        event.preventDefault();
        field.focus();
    }
}
</script>

<template>
    <DialogRoot v-model:open="open" :modal="modal">
        <DialogPortal>
            <component :is="modal ? DialogOverlay : 'div'" :class="['modal', `modal--${props.variant}`]">
                <DialogContent class="modal__panel" :aria-describedby="undefined" @open-auto-focus="focusFirstField">
                    <header class="modal__header">
                        <DialogTitle class="modal__title">{{ title }}</DialogTitle>
                        <DialogClose class="modal__close" :aria-label="t('common.close')"><X size="1rem" aria-hidden="true" /></DialogClose>
                    </header>
                    <div class="modal__body"><slot /></div>
                </DialogContent>
            </component>
        </DialogPortal>
    </DialogRoot>
</template>

<style>
.modal {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    background: var(--color-backdrop);
}

.modal--dialog { align-items: flex-start; justify-content: center; padding: 8vh var(--space-4) var(--space-4); overflow-y: auto; }
.modal--drawer { justify-content: flex-end; background: none; pointer-events: none; }

.modal__panel {
    display: flex;
    flex-direction: column;
    width: min(35rem, 100%);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.modal__panel:focus { outline: none; }

.modal--drawer .modal__panel { width: min(32.5rem, 100%); height: 100%; border-radius: 0; border-width: 0 0 0 0.0625rem; pointer-events: auto; }
.modal--drawer .modal__body { flex: 1; display: flex; flex-direction: column; }

.modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-5);
    border-bottom: 0.0625rem solid var(--color-border);
}

.modal__title { margin: 0; font-family: var(--font-display); font-weight: 400; font-size: 1.35rem; }

.modal__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--color-muted);
    line-height: 1;
}

.modal__close:hover { color: var(--color-ink); }

.modal__body { padding: var(--space-5); overflow-y: auto; }

.modal[data-state="open"] { animation: fade-in var(--transition); }
.modal[data-state="closed"] { animation: modal-fade-out var(--transition); }
.modal--dialog .modal__panel[data-state="open"] { animation: rise-in var(--transition); }
.modal--dialog .modal__panel[data-state="closed"] { animation: modal-rise-out var(--transition); }
.modal--drawer .modal__panel[data-state="open"] { animation: modal-slide-in var(--transition); }
.modal--drawer .modal__panel[data-state="closed"] { animation: modal-slide-out var(--transition); }

@keyframes modal-fade-out { to { opacity: 0; } }
@keyframes modal-rise-out { to { transform: translateY(0.5rem) scale(0.98); } }
@keyframes modal-slide-in { from { transform: translateX(1.5rem); } }
@keyframes modal-slide-out { to { transform: translateX(1.5rem); } }
</style>
