<script setup>
defineProps({
    title: { type: String, default: null },
});
</script>

<template>
    <section class="card">
        <header v-if="title || $slots.actions" class="card__header">
            <h2 class="card__title">{{ title }}</h2>
            <div class="card__actions"><slot name="actions" /></div>
        </header>
        <slot />
    </section>
</template>

<style scoped>
.card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-5);
}

.card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.card__title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--color-muted);
}

.card__actions { display: flex; gap: var(--space-2); align-items: center; }
</style>
