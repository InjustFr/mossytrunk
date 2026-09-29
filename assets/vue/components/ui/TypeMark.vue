<script setup>
defineProps({
    color: { type: String, default: null },
});
</script>

<template>
    <span :class="['type-mark', { 'type-mark--none': !color }]" :style="color ? { background: color } : null" aria-hidden="true" />
</template>

<style scoped>
.type-mark {
    display: inline-block;
    flex-shrink: 0;
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
}

.type-mark--none { border: 0.0625rem dashed var(--color-border-strong); }
</style>
