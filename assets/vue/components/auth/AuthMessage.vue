<script setup>
defineProps({
    variant: { type: String, default: 'error' },
});
</script>

<template>
    <p :class="['auth-message', `auth-message--${variant}`]" :role="variant === 'error' ? 'alert' : 'status'">
        <slot />
    </p>
</template>

<style scoped>
.auth-message {
    margin: 0;
    padding: var(--space-3);
    border-radius: var(--radius);
    font-size: 0.9rem;
}

.auth-message--error { background: var(--color-danger-soft); color: var(--color-danger); }
.auth-message--success { background: var(--color-accent-soft); color: var(--color-accent-strong); }
</style>
