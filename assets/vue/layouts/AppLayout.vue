<script setup>
import ToastHost from '../components/ui/ToastHost.vue';

defineProps({
    title: { type: String, required: true },
});

const links = [
    { href: '/tableau-de-bord', label: 'Tableau de bord', icon: '📊' },
    { href: '/commandes', label: 'Commandes', icon: '🧾' },
    { href: '/evenements', label: 'Événements', icon: '📅' },
    { href: '/produits', label: 'Produits', icon: '🏷️' },
    { href: '/remises', label: 'Remises', icon: '％' },
];

const currentPath = window.location.pathname;
const isActive = (href) => currentPath === href || currentPath.startsWith(`${href}/`);
</script>

<template>
    <div class="app-layout">
        <aside class="app-layout__sidebar">
            <a class="app-layout__brand" href="/">mossytrunk</a>
            <nav class="app-layout__nav" aria-label="Navigation principale">
                <a
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    :class="['app-layout__link', 'eyebrow', { 'app-layout__link--active': isActive(link.href) }]"
                    :aria-current="isActive(link.href) ? 'page' : undefined"
                >
                    <span class="app-layout__icon" aria-hidden="true">{{ link.icon }}</span>
                    {{ link.label }}
                </a>
            </nav>
            <p class="app-layout__footer">Gestion des ventes</p>
        </aside>

        <main class="app-layout__main">
            <header class="app-layout__heading">
                <div class="app-layout__titles">
                    <div v-if="$slots.back" class="app-layout__back"><slot name="back" /></div>
                    <h1 class="app-layout__title">{{ title }}</h1>
                </div>
                <div class="app-layout__actions"><slot name="actions" /></div>
            </header>
            <slot />
        </main>

        <ToastHost />
    </div>
</template>

<style scoped>
.app-layout {
    display: grid;
    grid-template-columns: var(--sidebar-width) minmax(0, 1fr);
    min-height: 100vh;
}

.app-layout__sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
    padding: var(--space-6) var(--space-4);
    background: var(--color-sidebar);
    border-right: 1px solid var(--color-border);
}

.app-layout__brand {
    padding: 0 var(--space-3);
    font-family: var(--font-display);
    font-size: 1.7rem;
    line-height: 1;
    color: var(--color-ink);
    text-decoration: none;
}

.app-layout__nav { display: flex; flex-direction: column; gap: var(--space-1); }

.app-layout__link {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    border-left: 2px solid transparent;
    color: var(--color-muted);
    text-decoration: none;
    transition: color var(--transition), background var(--transition), border-color var(--transition);
}

.app-layout__link:hover { color: var(--color-ink); background: var(--color-bg); }

.app-layout__link--active {
    color: var(--color-ink);
    background: var(--color-accent-soft);
    border-left-color: var(--color-accent);
}

.app-layout__icon { width: 1.2em; text-align: center; font-size: 0.95rem; filter: grayscale(1); opacity: 0.75; }
.app-layout__link--active .app-layout__icon { filter: none; opacity: 1; }

.app-layout__footer { margin: auto 0 0; padding: 0 var(--space-3); color: var(--color-subtle); font-size: 0.8rem; }

.app-layout__main {
    width: 100%;
    max-width: 1280px;
    padding: var(--space-6) var(--space-7);
}

.app-layout__heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--space-4);
    flex-wrap: wrap;
    margin-bottom: var(--space-5);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--color-border);
}

.app-layout__back { margin-bottom: var(--space-2); font-size: 0.9rem; }
.app-layout__back :deep(a) { color: var(--color-muted); text-decoration: none; }
.app-layout__back :deep(a:hover) { color: var(--color-ink); }
.app-layout__title { margin: 0; }
.app-layout__actions { display: flex; gap: var(--space-2); flex-wrap: wrap; }

@media (max-width: 860px) {
    .app-layout { grid-template-columns: 1fr; }

    .app-layout__sidebar {
        position: static;
        height: auto;
        flex-direction: row;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        border-right: none;
        border-bottom: 1px solid var(--color-border);
    }

    .app-layout__nav { flex-direction: row; flex-wrap: wrap; }
    .app-layout__link { border-left: none; border-bottom: 2px solid transparent; padding: var(--space-2); }
    .app-layout__link--active { border-bottom-color: var(--color-accent); }
    .app-layout__footer { display: none; }
    .app-layout__main { padding: var(--space-4); }
}
</style>
