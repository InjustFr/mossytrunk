<script setup>
import { formatDate } from '../../composables/useDate.js';

defineProps({
    event: { type: Object, required: true },
});
</script>

<template>
    <p class="event-header">
        <span class="event-header__location">📍 {{ event.location }}</span>
        <span class="event-header__dates">
            📅 {{ formatDate(event.startDate) }}
            <template v-if="event.endDate !== event.startDate"> → {{ formatDate(event.endDate) }}</template>
        </span>
    </p>
</template>

<style scoped>
.event-header { display: flex; gap: var(--space-4); flex-wrap: wrap; margin: 0; color: var(--color-muted); }
</style>
