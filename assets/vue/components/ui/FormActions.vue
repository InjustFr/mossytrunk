<script setup>
defineProps({
    sticky: { type: Boolean, default: false },
});
</script>

<template>
    <footer :class="['form-actions', { 'form-actions--sticky': sticky }]">
        <p v-if="$slots.note" class="form-actions__note"><slot name="note" /></p>
        <div class="form-actions__buttons"><slot /></div>
    </footer>
</template>

<style scoped>
.form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-4);
    flex-wrap: wrap;
    padding-top: var(--space-4);
    border-top: 0.0625rem solid var(--color-border);
}

.form-actions--sticky {
    position: sticky;
    bottom: calc(-1 * var(--space-5));
    margin: 0 calc(-1 * var(--space-5)) calc(-1 * var(--space-5));
    padding: var(--space-4) var(--space-5);
    background: var(--color-surface);
}

.form-actions__note { flex: 1 1 14rem; margin: 0; color: var(--color-muted); font-size: var(--font-size-sm); line-height: 1.4; }
.form-actions__buttons { display: flex; gap: var(--space-2); margin-left: auto; }
</style>
