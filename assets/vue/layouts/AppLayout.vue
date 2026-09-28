<script setup>
import { CalendarDays, LayoutDashboard, Percent, Receipt, Tag } from '@lucide/vue';
import ToastHost from '../components/ui/ToastHost.vue';

defineProps({
    title: { type: String, required: true },
});

const links = [
    { href: '/tableau-de-bord', label: 'Tableau de bord', icon: LayoutDashboard },
    { href: '/commandes', label: 'Commandes', icon: Receipt },
    { href: '/evenements', label: 'Événements', icon: CalendarDays },
    { href: '/produits', label: 'Produits', icon: Tag },
    { href: '/remises', label: 'Remises', icon: Percent },
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
                    <component :is="link.icon" class="app-layout__icon" size="1.125rem" :stroke-width="1.75" aria-hidden="true" />
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
    border-right: 0.0625rem solid var(--color-border);
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
    border-left: 0.125rem solid transparent;
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

.app-layout__icon { flex-shrink: 0; color: var(--color-subtle); transition: color var(--transition); }
.app-layout__link:hover .app-layout__icon { color: var(--color-ink); }
.app-layout__link--active .app-layout__icon { color: var(--color-accent); }

.app-layout__footer { margin: auto 0 0; padding: 0 var(--space-3); color: var(--color-subtle); font-size: 0.8rem; }

.app-layout__main {
    min-width: 0;
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
    border-bottom: 0.0625rem solid var(--color-border);
}

.app-layout__back { margin-bottom: var(--space-2); font-size: 0.9rem; }
.app-layout__back :deep(a) { color: var(--color-muted); text-decoration: none; }
.app-layout__back :deep(a:hover) { color: var(--color-ink); }
.app-layout__title { margin: 0; }
.app-layout__actions { display: flex; gap: var(--space-2); flex-wrap: wrap; }

@media (max-width: 53.75rem) {
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
        border-bottom: 0.0625rem solid var(--color-border);
    }

    .app-layout__nav { flex-direction: row; flex-wrap: wrap; }
    .app-layout__link { border-left: none; border-bottom: 0.125rem solid transparent; padding: var(--space-2); }
    .app-layout__link--active { border-bottom-color: var(--color-accent); }
    .app-layout__footer { display: none; }
    .app-layout__main { padding: var(--space-4); }
}
</style>
