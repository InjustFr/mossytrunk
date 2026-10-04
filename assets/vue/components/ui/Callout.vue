<script setup>
defineProps({
    tone: { type: String, default: 'warning' },
    as: { type: String, default: 'div' },
});
</script>

<template>
    <component :is="as" :class="['callout', `callout--${tone}`]"><slot /></component>
</template>

<style scoped>
.callout {
    display: flex;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--callout-tone);
    border-left-width: 0.25rem;
    border-radius: var(--radius);
    background: var(--callout-soft);
}

.callout--warning { --callout-tone: var(--color-warning); --callout-soft: var(--color-warning-soft); }
.callout--danger { --callout-tone: var(--color-danger); --callout-soft: var(--color-danger-soft); }
</style>
