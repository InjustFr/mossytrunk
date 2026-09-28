<script setup>
// Compact icon-only action for table rows. `label` is both the accessible name and the hover tooltip.
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    variant: { type: String, default: 'default' }, // default | danger
});
</script>

<template>
    <button type="button" :class="['icon-button', `icon-button--${variant}`]" :aria-label="label" :title="label">
        <component :is="icon" size="1.0625rem" :stroke-width="1.75" aria-hidden="true" />
    </button>
</template>

<style scoped>
.icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 0.0625rem solid transparent;
    border-radius: var(--radius);
    background: none;
    color: var(--color-muted);
    cursor: pointer;
    transition: color var(--transition), background var(--transition), border-color var(--transition);
}

.icon-button:hover { color: var(--color-ink); background: var(--color-bg); border-color: var(--color-border); }
.icon-button:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.icon-button--danger:hover { color: var(--color-danger); background: var(--color-danger-soft); border-color: transparent; }
</style>
