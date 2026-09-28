<script setup>
import { X } from '@lucide/vue';
import { useToast } from '../../composables/useToast.js';

const { toasts, dismiss } = useToast();
</script>

<template>
    <div class="toast-host" aria-live="polite">
        <TransitionGroup name="toast-host__item">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="['toast', `toast--${toast.type}`]"
                role="status"
                data-test="toast"
            >
                <span class="toast__message">{{ toast.message }}</span>
                <button class="toast__close" type="button" aria-label="Fermer" @click="dismiss(toast.id)"><X size="1rem" aria-hidden="true" /></button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-host {
    position: fixed;
    right: var(--space-4);
    bottom: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    z-index: 100;
    max-width: min(26.25rem, calc(100vw - 2 * var(--space-4)));
}

.toast {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius);
    background: var(--color-surface);
    border-left: 0.25rem solid var(--color-accent);
    box-shadow: var(--shadow);
}

.toast--error { border-left-color: var(--color-danger); }
.toast--info { border-left-color: var(--color-muted); }

.toast__message { flex: 1; }

.toast__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--color-muted);
    font-size: 1.2rem;
    line-height: 1;
}

.toast-host__item-enter-active,
.toast-host__item-leave-active { transition: opacity var(--transition), transform var(--transition); }
.toast-host__item-enter-from,
.toast-host__item-leave-to { opacity: 0; transform: translateY(0.5rem); }
</style>
