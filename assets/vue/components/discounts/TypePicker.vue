<script setup>
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';

defineProps({
    types: { type: Array, required: true },
});
const selected = defineModel({ type: Array, required: true });
</script>

<template>
    <p v-if="types.length === 0" class="type-picker__empty">Aucun type de produit défini.</p>
    <ToggleGroupRoot v-else v-model="selected" type="multiple" class="type-picker" aria-label="Types concernés">
        <ToggleGroupItem v-for="type in types" :key="type.id" :value="type.id" class="type-picker__chip">{{ type.name }}</ToggleGroupItem>
    </ToggleGroupRoot>
</template>

<style scoped>
.type-picker { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.type-picker__empty { margin: 0; color: var(--color-muted); font-size: 0.9rem; }

.type-picker__chip {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.type-picker__chip:hover { border-color: var(--color-accent); }
.type-picker__chip:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.type-picker__chip[data-state="on"] { background: var(--color-accent); border-color: var(--color-accent); color: #fff; }
</style>
