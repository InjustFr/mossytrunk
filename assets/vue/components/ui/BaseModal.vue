<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { X } from '@lucide/vue';

// Accessible modal: centred dialog, or a right-hand drawer that leaves the page visible (e.g. order entry next to the list).
const props = defineProps({
    title: { type: String, required: true },
    variant: { type: String, default: 'dialog' }, // dialog | drawer
});
const open = defineModel('open', { type: Boolean, required: true });

const panel = ref(null);
let previousFocus = null;

function close() {
    open.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        close();
    }
}

watch(open, async (isOpen) => {
    if (isOpen) {
        previousFocus = document.activeElement;
        document.addEventListener('keydown', onKeydown);
        await nextTick();
        panel.value?.querySelector('input, select, textarea, button:not(.modal__close)')?.focus();
    } else {
        document.removeEventListener('keydown', onKeydown);
        previousFocus?.focus?.();
    }
});

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition :name="`modal--${props.variant}`">
            <div v-if="open" :class="['modal', `modal--${props.variant}`]" @mousedown.self="close">
                <section
                    ref="panel"
                    class="modal__panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                >
                    <header class="modal__header">
                        <h2 class="modal__title">{{ title }}</h2>
                        <button type="button" class="modal__close" aria-label="Fermer" @click="close"><X size="1rem" aria-hidden="true" /></button>
                    </header>
                    <div class="modal__body"><slot /></div>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    background: var(--color-backdrop);
}

.modal--dialog { align-items: flex-start; justify-content: center; padding: 8vh var(--space-4) var(--space-4); overflow-y: auto; }
.modal--drawer { justify-content: flex-end; background: rgb(17 17 17 / 12%); }

.modal__panel {
    display: flex;
    flex-direction: column;
    width: min(35rem, 100%);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.modal--drawer .modal__panel { width: min(32.5rem, 100%); height: 100%; border-radius: 0; border-width: 0 0 0 0.0625rem; }

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
    font-size: 1.6rem;
    line-height: 1;
}

.modal__close:hover { color: var(--color-ink); }

.modal__body { padding: var(--space-5); overflow-y: auto; }

.modal--dialog-enter-active,
.modal--dialog-leave-active,
.modal--drawer-enter-active,
.modal--drawer-leave-active { transition: opacity var(--transition); }

.modal--dialog-enter-active .modal__panel,
.modal--dialog-leave-active .modal__panel,
.modal--drawer-enter-active .modal__panel,
.modal--drawer-leave-active .modal__panel { transition: transform var(--transition); }

.modal--dialog-enter-from,
.modal--dialog-leave-to,
.modal--drawer-enter-from,
.modal--drawer-leave-to { opacity: 0; }

.modal--dialog-enter-from .modal__panel,
.modal--dialog-leave-to .modal__panel { transform: translateY(0.5rem) scale(0.98); }

.modal--drawer-enter-from .modal__panel,
.modal--drawer-leave-to .modal__panel { transform: translateX(1.5rem); }
</style>
