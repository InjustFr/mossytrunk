<script setup>
import { computed } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import ExternalItemRow from './ExternalItemRow.vue';

const props = defineProps({
    label: { type: String, required: true },
    items: { type: Array, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
    importing: { type: Boolean, default: false },
    canImport: { type: Boolean, default: true },
});
const emit = defineEmits(['linked', 'reimport']);

const unlinked = computed(() => props.items.filter((item) => !item.linkedTo));
const linked = computed(() => props.items.filter((item) => item.linkedTo));
</script>

<template>
    <div class="item-linker">
        <p class="item-linker__intro">
            {{ label }} ne connaît pas vos produits : indiquez ce que vend chaque article, une fois pour toutes. Astuce : quand le SKU de l'article est la référence du produit (ex. <code>STI-MOUSSE</code>), l'association est automatique.
        </p>

        <section v-if="unlinked.length" aria-label="Articles à associer">
            <ul class="item-linker__list">
                <ExternalItemRow v-for="item in unlinked" :key="item.id" :item="item" :products="products" :submit="submit" @linked="emit('linked', $event)" />
            </ul>
        </section>
        <EmptyState v-else>Tous les articles vus sur {{ label }} sont associés.</EmptyState>

        <details v-if="linked.length" class="item-linker__linked">
            <summary>Articles déjà associés ({{ linked.length }})</summary>
            <ul class="item-linker__list">
                <ExternalItemRow v-for="item in linked" :key="item.id" :item="item" :products="products" :submit="submit" @linked="emit('linked', $event)" />
            </ul>
        </details>

        <div v-if="canImport" class="item-linker__actions">
            <BaseButton :loading="importing" :disabled="unlinked.length > 0" @click="emit('reimport')">Relancer l'import {{ label }}</BaseButton>
        </div>
    </div>
</template>

<style scoped>
.item-linker { display: flex; flex-direction: column; gap: var(--space-4); }
.item-linker__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.item-linker__intro code { padding: 0 var(--space-1); border-radius: 0.25rem; background: var(--color-bg); color: var(--color-ink); }
.item-linker__list { margin: 0; padding: 0; list-style: none; }
.item-linker__linked summary { color: var(--color-muted); cursor: pointer; }
.item-linker__actions { display: flex; justify-content: flex-end; }
</style>
