<script setup>
import { ChevronLeft } from '@lucide/vue';
</script>

<template>
    <button type="button" class="back-button">
        <ChevronLeft size="1rem" aria-hidden="true" /> <slot />
    </button>
</template>

<style scoped>
.back-button {
    display: inline-flex;
    align-self: flex-start;
    align-items: center;
    gap: var(--space-1);
    padding: 0;
    border: none;
    background: none;
    color: var(--color-muted);
    font-size: 0.875rem;
    cursor: pointer;
}

.back-button:hover { color: var(--color-ink); }
</style>
