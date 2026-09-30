<script setup>
import { Pencil } from '@lucide/vue';
import IconButton from '../ui/IconButton.vue';
import TypeMark from '../ui/TypeMark.vue';

defineProps({
    types: { type: Array, required: true },
});
const emit = defineEmits(['edit']);
</script>

<template>
    <ul class="product-type-list">
        <li v-for="type in types" :key="type.id" class="product-type-list__row">
            <TypeMark :color="type.color" />
            <span class="product-type-list__name">{{ type.name }}</span>
            <span class="product-type-list__code">{{ type.code }}</span>
            <IconButton :icon="Pencil" :label="`Modifier le type ${type.name}`" @click="emit('edit', type)" />
        </li>
    </ul>
</template>

<style scoped>
.product-type-list { margin: 0; padding: 0; list-style: none; }

.product-type-list__row {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    border-top: 0.0625rem solid var(--color-border);
}

.product-type-list__row:first-child { border-top: none; }
.product-type-list__name { color: var(--color-ink); font-weight: 500; }

.product-type-list__code {
    color: var(--color-muted);
    font-size: 0.75rem;
    letter-spacing: 0.06rem;
    text-transform: uppercase;
}
</style>
