<script setup>
import Notice from '../ui/Notice.vue';

defineProps({
    variant: { type: String, default: 'error' },
});
</script>

<template>
    <Notice :tone="variant === 'error' ? 'danger' : 'success'" :role="variant === 'error' ? 'alert' : 'status'" class="auth-message">
        <slot />
    </Notice>
</template>

<style scoped>
.auth-message { padding: var(--space-3); font-size: var(--font-size-md); }
</style>
