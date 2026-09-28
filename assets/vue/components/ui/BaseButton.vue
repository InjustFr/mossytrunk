<script setup>
defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | danger | ghost
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        :type="type"
        :class="['button', `button--${variant}`, { 'button--loading': loading }]"
        :disabled="disabled || loading"
    >
        <span v-if="loading" class="button__spinner" aria-hidden="true" />
        <slot />
    </button>
</template>

<style scoped>
.button {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    border: 1px solid transparent;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: 600;
    transition: background var(--transition), border-color var(--transition), opacity var(--transition);
}

.button:disabled { opacity: 0.6; cursor: not-allowed; }

.button--primary { background: var(--color-accent); color: #fff; }
.button--primary:hover:not(:disabled) { background: var(--color-accent-strong); }

.button--secondary { background: var(--color-surface); border-color: var(--color-border); }
.button--secondary:hover:not(:disabled) { border-color: var(--color-accent); }

.button--danger { background: var(--color-surface); border-color: var(--color-danger); color: var(--color-danger); }
.button--danger:hover:not(:disabled) { background: var(--color-danger-soft); }

.button--ghost { background: none; color: var(--color-muted); padding-inline: var(--space-2); }
.button--ghost:hover:not(:disabled) { color: var(--color-text); }

.button__spinner {
    width: 0.9em;
    height: 0.9em;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: button-spin 0.7s linear infinite;
}

@keyframes button-spin { to { transform: rotate(360deg); } }
</style>
