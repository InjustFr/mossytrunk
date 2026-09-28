<script setup>
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';

defineProps({
    sort: { type: String, required: true },
    numeric: { type: Boolean, default: false },
});
const emit = defineEmits(['sort']);
</script>

<template>
    <th :aria-sort="sort" :class="{ 'data-table__cell--number': numeric }">
        <button type="button" :class="['sortable-header', { 'sortable-header--active': sort !== 'none', 'sortable-header--numeric': numeric }]" @click="emit('sort')">
            <slot />
            <ArrowUp v-if="sort === 'ascending'" size="0.875rem" aria-hidden="true" />
            <ArrowDown v-else-if="sort === 'descending'" size="0.875rem" aria-hidden="true" />
            <ArrowUpDown v-else class="sortable-header__idle" size="0.875rem" aria-hidden="true" />
        </button>
    </th>
</template>

<style scoped>
.sortable-header {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    white-space: nowrap;
    cursor: pointer;
    transition: color var(--transition);
}

.sortable-header--numeric { flex-direction: row-reverse; }
.sortable-header:hover,
.sortable-header--active { color: var(--color-ink); }
.sortable-header__idle { opacity: 0; transition: opacity var(--transition); }
.sortable-header:hover .sortable-header__idle,
.sortable-header:focus-visible .sortable-header__idle { opacity: 1; }
</style>
