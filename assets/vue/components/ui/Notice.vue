<script setup>
defineProps({
    tone: { type: String, default: 'warning' },
    as: { type: String, default: 'p' },
});
</script>

<template>
    <component :is="as" :class="['notice', `notice--${tone}`]"><slot /></component>
</template>

<style scoped>
.notice { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); }
.notice--danger { background: var(--color-danger-soft); color: var(--color-danger); }
.notice--warning { background: var(--color-warning-soft); color: var(--color-warning); }
.notice--success { background: var(--color-accent-soft); color: var(--color-accent-strong); }
</style>
