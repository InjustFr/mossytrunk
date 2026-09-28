<script setup>
import { CalendarDays, MapPin } from '@lucide/vue';
import { formatDate } from '../../composables/useDate.js';

defineProps({
    event: { type: Object, required: true },
});
</script>

<template>
    <p class="event-header">
        <span class="event-header__item"><MapPin :size="16" aria-hidden="true" /> {{ event.location }}</span>
        <span class="event-header__item">
            <CalendarDays :size="16" aria-hidden="true" /> {{ formatDate(event.startDate) }}
            <template v-if="event.endDate !== event.startDate"> → {{ formatDate(event.endDate) }}</template>
        </span>
    </p>
</template>

<style scoped>
.event-header { display: flex; gap: var(--space-5); flex-wrap: wrap; margin: 0; color: var(--color-muted); }
.event-header__item { display: inline-flex; align-items: center; gap: var(--space-2); }
</style>
