<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
});
</script>

<template>
    <section class="form-section">
        <header v-if="title || description" class="form-section__header">
            <h3 v-if="title" class="form-section__title">{{ title }}</h3>
            <p v-if="description" class="form-section__description">{{ description }}</p>
        </header>
        <div class="form-section__fields"><slot /></div>
    </section>
</template>

<style scoped>
.form-section { display: flex; flex-direction: column; gap: var(--space-3); container: form / inline-size; }
.form-section + .form-section { padding-top: var(--space-5); border-top: 0.0625rem solid var(--color-border); }
.form-section__header { display: flex; flex-direction: column; gap: var(--space-1); }
.form-section__title { margin: 0; font-family: var(--font-body); font-size: 0.9375rem; font-weight: 600; color: var(--color-ink); }
.form-section__description { margin: 0; color: var(--color-muted); font-size: 0.8125rem; line-height: 1.4; }
.form-section__fields { display: flex; flex-direction: column; gap: var(--space-4); }
</style>
