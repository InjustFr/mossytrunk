<script setup>
defineProps({
    tone: { type: String, default: 'neutral' },
});
</script>

<template>
    <span :class="['status-badge', `status-badge--${tone}`]"><slot /></span>
</template>

<style scoped>
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: 0.0625rem var(--space-2);
    border-radius: 62.4375rem;
    background: var(--color-bg);
    color: var(--color-muted);
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.02rem;
    white-space: nowrap;
}

.status-badge--warning { background: var(--color-warning-soft); color: var(--color-warning); }
.status-badge--danger { background: var(--color-danger-soft); color: var(--color-danger); }
.status-badge--success { background: var(--color-success-soft); color: var(--color-success); }
</style>
