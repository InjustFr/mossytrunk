<script setup>
defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <main class="auth-layout">
        <div class="auth-layout__panel">
            <p class="auth-layout__brand">mossytrunk</p>
            <section class="auth-layout__card">
                <h1 class="auth-layout__title">{{ title }}</h1>
                <slot />
            </section>
            <p v-if="$slots.footer" class="auth-layout__footer"><slot name="footer" /></p>
        </div>
    </main>
</template>

<style scoped>
.auth-layout {
    display: grid;
    place-items: center;
    min-height: 100vh;
    padding: var(--space-5) var(--space-4);
}

.auth-layout__panel { width: 100%; max-width: 24rem; }

.auth-layout__brand {
    margin: 0 0 var(--space-5);
    text-align: center;
    font-family: var(--font-display);
    font-size: 2rem;
    line-height: 1;
    color: var(--color-ink);
}

.auth-layout__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding: var(--space-6);
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
}

.auth-layout__title { margin: 0; font-size: 1.5rem; }

.auth-layout__footer { margin: var(--space-4) 0 0; text-align: center; font-size: 0.9rem; }
</style>
