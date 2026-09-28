<script setup>
import ToastHost from '../components/ui/ToastHost.vue';

defineProps({
    title: { type: String, required: true },
});

const links = [
    { href: '/commandes', label: 'Commandes' },
    { href: '/produits', label: 'Produits' },
    { href: '/evenements', label: 'Événements' },
    { href: '/remises', label: 'Remises' },
];

const currentPath = window.location.pathname;
const isActive = (href) => currentPath === href || currentPath.startsWith(`${href}/`);
</script>

<template>
    <div class="app-layout">
        <header class="app-layout__header">
            <a class="app-layout__brand" href="/">🌿 MossyTrunk</a>
            <nav class="app-layout__nav" aria-label="Navigation principale">
                <a
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    :class="['app-layout__link', { 'app-layout__link--active': isActive(link.href) }]"
                    :aria-current="isActive(link.href) ? 'page' : undefined"
                >{{ link.label }}</a>
            </nav>
        </header>

        <main class="app-layout__main">
            <div class="app-layout__heading">
                <slot name="back" />
                <h1 class="app-layout__title">{{ title }}</h1>
                <div class="app-layout__actions"><slot name="actions" /></div>
            </div>
            <slot />
        </main>

        <ToastHost />
    </div>
</template>

<style scoped>
.app-layout { min-height: 100vh; }

.app-layout__header {
    display: flex;
    align-items: center;
    gap: var(--space-6);
    padding: var(--space-3) var(--space-5);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
}

.app-layout__brand {
    font-weight: 700;
    color: var(--color-accent-strong);
    text-decoration: none;
}

.app-layout__nav { display: flex; gap: var(--space-2); flex-wrap: wrap; }

.app-layout__link {
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    color: var(--color-text);
    text-decoration: none;
    transition: background var(--transition);
}

.app-layout__link:hover { background: var(--color-accent-soft); }

.app-layout__link--active {
    background: var(--color-accent);
    color: #fff;
}

.app-layout__link--active:hover { background: var(--color-accent-strong); }

.app-layout__main {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--space-5);
}

.app-layout__heading {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
    flex-wrap: wrap;
}

.app-layout__title { margin: 0; flex: 1; }

.app-layout__actions { display: flex; gap: var(--space-2); }
</style>
