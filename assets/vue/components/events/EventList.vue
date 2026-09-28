<script setup>
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDate } from '../../composables/useDate.js';

defineProps({
    events: { type: Array, required: true },
});
</script>

<template>
    <EmptyState v-if="events.length === 0">Aucun événement. Créez-en un pour y rattacher des commandes.</EmptyState>
    <ul v-else class="event-list">
        <li v-for="event in events" :key="event.id" class="event-list__item">
            <a class="event-list__link" :href="`/evenements/${event.id}`">
                <span class="event-list__name">{{ event.name }}</span>
                <span class="event-list__meta">
                    {{ event.location }} · {{ formatDate(event.startDate) }}
                    <template v-if="event.endDate !== event.startDate"> → {{ formatDate(event.endDate) }}</template>
                </span>
                <span class="event-list__expenses">Dépenses : <MoneyAmount :cents="event.expensesTotal" /></span>
            </a>
        </li>
    </ul>
</template>

<style scoped>
.event-list { display: flex; flex-direction: column; gap: var(--space-2); margin: 0; padding: 0; list-style: none; }

.event-list__link {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--space-1) var(--space-4);
    padding: var(--space-4) var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: inherit;
    text-decoration: none;
    transition: border-color var(--transition), transform var(--transition);
}

.event-list__link:hover { border-color: var(--color-ink); transform: translateX(2px); }

.event-list__name { font-family: var(--font-display); font-size: 1.3rem; line-height: 1.2; }
.event-list__meta { grid-column: 1; color: var(--color-muted); font-size: 0.9rem; }
.event-list__expenses { grid-row: 1 / span 2; grid-column: 2; align-self: center; color: var(--color-muted); font-size: 0.9rem; }
</style>
