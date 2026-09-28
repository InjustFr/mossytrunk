<script setup>
// Toggle chips: a rule on a type covers all its products, including ones created later.
defineProps({
    types: { type: Array, required: true },
});
const selected = defineModel({ type: Array, required: true });

function toggle(id) {
    selected.value = selected.value.includes(id) ? selected.value.filter((t) => t !== id) : [...selected.value, id];
}
</script>

<template>
    <div class="type-picker" role="group" aria-label="Types concernés">
        <p v-if="types.length === 0" class="type-picker__empty">Aucun type de produit défini.</p>
        <button
            v-for="type in types"
            :key="type.id"
            type="button"
            :class="['type-picker__chip', { 'type-picker__chip--active': selected.includes(type.id) }]"
            :aria-pressed="selected.includes(type.id)"
            @click="toggle(type.id)"
        >{{ type.name }}</button>
    </div>
</template>

<style scoped>
.type-picker { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.type-picker__empty { margin: 0; color: var(--color-muted); font-size: 0.9rem; }

.type-picker__chip {
    padding: var(--space-1) var(--space-3);
    border: 1px solid var(--color-border-strong);
    border-radius: 999px;
    background: var(--color-surface);
    cursor: pointer;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.type-picker__chip--active { background: var(--color-accent); border-color: var(--color-accent); color: #fff; }
</style>
