<script setup>
defineProps({
    inline: { type: Boolean, default: false },
});
</script>

<template>
    <p :class="['empty-state', { 'empty-state--inline': inline }]"><slot /></p>
</template>

<style scoped>
.empty-state {
    margin: 0;
    padding: var(--space-5);
    text-align: center;
    color: var(--color-muted);
    border: 0.0625rem dashed var(--color-border);
    border-radius: var(--radius);
}

.empty-state--inline { padding: 0; border: none; text-align: left; font-size: var(--font-size-md); }
</style>
