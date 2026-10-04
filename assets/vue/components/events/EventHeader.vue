<script setup>
import { CalendarDays, MapPin } from '@lucide/vue';
import { formatDateSpan } from '../../composables/useDate.js';

defineProps({
    event: { type: Object, required: true },
});
</script>

<template>
    <p class="event-header">
        <span class="event-header__item"><MapPin size="1rem" aria-hidden="true" /> {{ event.location }}</span>
        <span class="event-header__item">
            <CalendarDays size="1rem" aria-hidden="true" /> {{ formatDateSpan(event.startDate, event.endDate) }}
        </span>
    </p>
</template>

<style scoped>
.event-header { display: flex; gap: var(--space-5); flex-wrap: wrap; margin: 0; color: var(--color-muted); }
.event-header__item { display: inline-flex; align-items: center; gap: var(--space-2); }
</style>
