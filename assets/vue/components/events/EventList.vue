<script setup>
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDate, fromToday } from '../../composables/useDate.js';

defineProps({
    events: { type: Array, required: true },
    emptyMessage: { type: String, default: 'Aucun événement.' },
});
</script>

<template>
    <EmptyState v-if="events.length === 0">{{ emptyMessage }}</EmptyState>
    <ul v-else class="event-list">
        <li v-for="event in events" :key="event.id" class="event-list__item">
            <span :class="['event-list__when', { 'event-list__when--ongoing': event.timing === 'ongoing' }]">
                {{ event.timing === 'ongoing' ? 'En cours' : fromToday(event.startDate) }}
            </span>
            <a class="event-list__link" :href="`/events/${event.id}`">
                <span class="event-list__name">{{ event.name }}</span>
                <span class="event-list__meta">
                    {{ event.location }}, {{ formatDate(event.startDate) }}<template v-if="event.endDate !== event.startDate"> → {{ formatDate(event.endDate) }}</template>
                </span>
            </a>
            <dl class="event-list__figures">
                <div class="event-list__figure">
                    <dt>Dépenses engagées</dt>
                    <dd><MoneyAmount :cents="event.expensesTotal" /></dd>
                </div>
                <div v-if="event.orderCount > 0" class="event-list__figure">
                    <dt>Résultat</dt>
                    <dd><MoneyAmount :cents="event.result" signed :data-test="`event-result-${event.id}`" /></dd>
                </div>
            </dl>
        </li>
    </ul>
</template>

<style scoped>
.event-list { margin: 0; padding: 0; list-style: none; }

.event-list__item {
    display: grid;
    grid-template-columns: 7rem minmax(0, 1fr) auto;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) 0;
    border-bottom: 0.0625rem solid var(--color-border);
}

.event-list__item:last-child { border-bottom: none; }

.event-list__when { color: var(--color-muted); font-size: 0.875rem; font-variant-numeric: tabular-nums; }
.event-list__when--ongoing { color: var(--color-accent-strong); font-weight: 600; }

.event-list__link { display: flex; flex-direction: column; min-width: 0; color: inherit; text-decoration: none; }
.event-list__link:hover .event-list__name { text-decoration: underline; text-underline-offset: 0.1875rem; }
.event-list__name { font-family: var(--font-display); font-size: 1.2rem; line-height: 1.25; }
.event-list__meta { color: var(--color-muted); font-size: 0.875rem; }

.event-list__figures { display: flex; gap: var(--space-5); margin: 0; }
.event-list__figure { display: flex; flex-direction: column; align-items: flex-end; }
.event-list__figure dt { font-size: 0.8rem; color: var(--color-muted); }
.event-list__figure dd { margin: 0; font-variant-numeric: tabular-nums; }

@media (max-width: 43.75rem) {
    .event-list__item { grid-template-columns: 1fr; gap: var(--space-1); }
    .event-list__figures { justify-content: flex-start; }
}
</style>
