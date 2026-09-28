<script setup>
defineProps({
    events: { type: Array, required: true },
});
const selected = defineModel({ type: String, required: true });
</script>

<template>
    <label class="event-filter">
        <span class="event-filter__label">Événement</span>
        <select v-model="selected" class="event-filter__select">
            <option value="">Tous</option>
            <option v-for="event in events" :key="event.id" :value="event.id">{{ event.name }}</option>
        </select>
    </label>
</template>

<style scoped>
.event-filter { display: inline-flex; align-items: center; gap: var(--space-2); }
.event-filter__label { color: var(--color-muted); font-size: 0.9rem; }
.event-filter__select {
    padding: var(--space-1) var(--space-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
}
</style>
