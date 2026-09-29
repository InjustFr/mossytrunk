<script setup>
import { computed } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import EtsyListingRow from './EtsyListingRow.vue';

const props = defineProps({
    listings: { type: Array, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
    importing: { type: Boolean, default: false },
});
const emit = defineEmits(['linked', 'reimport']);

const unlinked = computed(() => props.listings.filter((listing) => !listing.linkedTo));
const linked = computed(() => props.listings.filter((listing) => listing.linkedTo));
</script>

<template>
    <div class="etsy-linker">
        <p class="etsy-linker__intro">
            Etsy ne connaît pas vos références : indiquez quel produit chaque annonce vend. Astuce : si le SKU de l'annonce sur Etsy est la référence du produit (ex. <code>STI-MOUSSE</code>), l'association est automatique.
        </p>

        <section v-if="unlinked.length" aria-label="Annonces à associer">
            <ul class="etsy-linker__list">
                <EtsyListingRow v-for="listing in unlinked" :key="listing.id" :listing="listing" :products="products" :submit="submit" @linked="emit('linked', $event)" />
            </ul>
        </section>
        <EmptyState v-else>Toutes les annonces vues sur Etsy sont associées.</EmptyState>

        <details v-if="linked.length" class="etsy-linker__linked">
            <summary>Annonces déjà associées ({{ linked.length }})</summary>
            <ul class="etsy-linker__list">
                <EtsyListingRow v-for="listing in linked" :key="listing.id" :listing="listing" :products="products" :submit="submit" @linked="emit('linked', $event)" />
            </ul>
        </details>

        <div class="etsy-linker__actions">
            <BaseButton :loading="importing" :disabled="unlinked.length > 0" @click="emit('reimport')">Relancer l'import Etsy</BaseButton>
        </div>
    </div>
</template>

<style scoped>
.etsy-linker { display: flex; flex-direction: column; gap: var(--space-4); }
.etsy-linker__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.etsy-linker__intro code { padding: 0 var(--space-1); border-radius: 0.25rem; background: var(--color-bg); color: var(--color-ink); }
.etsy-linker__list { margin: 0; padding: 0; list-style: none; }
.etsy-linker__linked summary { color: var(--color-muted); cursor: pointer; }
.etsy-linker__actions { display: flex; justify-content: flex-end; }
</style>
