<script setup>
import { computed } from 'vue';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    events: { type: Array, required: true },
});
const selected = defineModel({ type: String, required: true });

const options = computed(() => [{ value: '', label: 'Tous' }, ...props.events.map((event) => ({ value: event.id, label: event.name }))]);
</script>

<template>
    <div class="event-filter">
        <span class="event-filter__label">Événement</span>
        <BaseSelect v-model="selected" :options="options" size="small" aria-label="Événement" />
    </div>
</template>

<style scoped>
.event-filter { display: inline-flex; align-items: center; gap: var(--space-2); }
.event-filter__label { color: var(--color-muted); font-size: 0.9rem; }
</style>
