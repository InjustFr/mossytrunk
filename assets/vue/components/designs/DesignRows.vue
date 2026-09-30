<script setup>
import EmptyState from '../ui/EmptyState.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDate } from '../../composables/useDate.js';

defineProps({
    designs: { type: Array, required: true },
    empty: { type: String, default: 'Aucun design.' },
});
</script>

<template>
    <EmptyState v-if="designs.length === 0">{{ empty }}</EmptyState>
    <ul v-else class="design-rows">
        <li v-for="design in designs" :key="design.id" class="design-rows__row">
            <a :href="`/designs/${design.id}`" class="design-rows__name">{{ design.name }}</a>
            <span class="design-rows__gabarits">{{ design.declinations.map((d) => d.gabarit.name).join(', ') || 'Pas encore décliné' }}</span>
            <StatusBadge v-if="design.status === 'validated'" tone="success">Sorti de l'atelier le {{ formatDate(design.validatedAt) }}</StatusBadge>
            <StatusBadge v-else-if="design.current" tone="warning">Sur l'établi</StatusBadge>
            <StatusBadge v-else>Mis de côté</StatusBadge>
        </li>
    </ul>
</template>

<style scoped>
.design-rows { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.design-rows__row { display: grid; grid-template-columns: minmax(8rem, 14rem) minmax(0, 1fr) auto; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 0.0625rem solid var(--color-border); }
.design-rows__row:last-child { border-bottom: none; }
.design-rows__name { font-weight: 600; }
.design-rows__gabarits { overflow: hidden; color: var(--color-muted); font-size: 0.85rem; text-overflow: ellipsis; white-space: nowrap; }
</style>
