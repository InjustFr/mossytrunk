<script setup>
import { useId } from 'vue';

const titleId = useId();

defineProps({
    title: { type: String, default: null },
});
</script>

<template>
    <section class="card" :aria-labelledby="title ? titleId : null">
        <header v-if="title || $slots.actions" class="card__header">
            <h2 v-if="title" :id="titleId" class="card__title">{{ title }}</h2>
            <div class="card__actions"><slot name="actions" /></div>
        </header>
        <slot />
    </section>
</template>

<style scoped>
.card {
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-5);
}

.card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.card__title {
    margin: 0;
    color: var(--color-ink);
}

.card__actions { display: flex; gap: var(--space-2); align-items: center; }
</style>
