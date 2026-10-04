<script setup>
defineProps({
    items: { type: Array, required: true },
    editingId: { type: String, default: null },
});
</script>

<template>
    <ul class="editable-list">
        <li v-for="item in items" :key="item.id" :class="['editable-list__item', { 'editable-list__item--editing': item.id === editingId }]">
            <slot :item="item" />
        </li>
    </ul>
</template>

<style scoped>
.editable-list {
    display: flex;
    flex-direction: column;
    max-height: 30vh;
    margin: 0;
    padding: 0;
    overflow-y: auto;
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    list-style: none;
    overscroll-behavior: contain;
}

.editable-list__item { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); padding: var(--space-2); border-bottom: 0.0625rem solid var(--color-border); }
.editable-list__item:last-child { border-bottom: none; }
.editable-list__item--editing { background: var(--color-accent-soft); }
</style>
