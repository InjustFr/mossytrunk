<script setup>
import { X } from '@lucide/vue';
// The single error message listing everything the SumUp import could not bring in.
defineProps({
    problem: { type: Object, required: true },
});
const emit = defineEmits(['dismiss']);
</script>

<template>
    <div class="sumup-import-problem" role="alert" data-test="sumup-import-problem">
        <div class="sumup-import-problem__content">
            <p class="sumup-import-problem__message">{{ problem.message }}</p>
            <p v-if="problem.dates.length" class="sumup-import-problem__detail">
                Dates sans événement : <strong>{{ problem.dates.join(', ') }}</strong>
                — <a href="/evenements">Créer un événement</a>
            </p>
            <p v-if="problem.products.length" class="sumup-import-problem__detail">
                Produits à préciser : <strong>{{ problem.products.join(', ') }}</strong>
            </p>
        </div>
        <button type="button" class="sumup-import-problem__close" aria-label="Fermer le message" @click="emit('dismiss')"><X size="1rem" aria-hidden="true" /></button>
    </div>
</template>

<style scoped>
.sumup-import-problem {
    display: flex;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-danger);
    border-left-width: 0.25rem;
    border-radius: var(--radius);
    background: var(--color-danger-soft);
}

.sumup-import-problem__content { flex: 1; }
.sumup-import-problem__message { margin: 0; font-weight: 600; color: var(--color-danger); }
.sumup-import-problem__detail { margin: var(--space-1) 0 0; }

.sumup-import-problem__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1;
    color: var(--color-muted);
}
</style>
