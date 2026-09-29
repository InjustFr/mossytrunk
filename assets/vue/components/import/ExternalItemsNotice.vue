<script setup>
import { Link2 } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import { plural } from '../../composables/usePlural.js';

defineProps({
    label: { type: String, required: true },
    count: { type: Number, required: true },
});
const emit = defineEmits(['open']);
</script>

<template>
    <div class="items-notice" role="status">
        <Link2 class="items-notice__icon" size="1.25rem" aria-hidden="true" />
        <p class="items-notice__text">
            <strong>{{ plural(count, `article ${label}`, `articles ${label}`) }} à associer à vos produits.</strong>
            Leurs commandes sont en attente : associez chaque article une fois, puis relancez l'import.
        </p>
        <BaseButton variant="secondary" @click="emit('open')">Associer les articles</BaseButton>
    </div>
</template>

<style scoped>
.items-notice {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-warning);
    border-left-width: 0.25rem;
    border-radius: var(--radius);
    background: var(--color-warning-soft);
}

.items-notice__icon { flex: none; color: var(--color-warning); }
.items-notice__text { flex: 1; margin: 0; }
.items-notice__text strong { color: var(--color-warning); }

@media (max-width: 40rem) {
    .items-notice { flex-direction: column; align-items: flex-start; }
}
</style>
