<script setup>
import { ref } from 'vue';
import { X } from '@lucide/vue';

defineProps({
    inputLabel: { type: String, default: 'Nouvelle variante' },
});
const variants = defineModel({ type: Array, required: true });
const draft = ref('');

function add() {
    const value = draft.value.trim();
    if (value !== '' && !variants.value.includes(value)) {
        variants.value = [...variants.value, value];
    }
    draft.value = '';
}

function remove(variant) {
    variants.value = variants.value.filter((existing) => existing !== variant);
}
</script>

<template>
    <div class="variants-input">
        <TransitionGroup name="variants-input__chip" tag="ul" class="variants-input__chips">
            <li v-for="variant in variants" :key="variant" class="variants-input__chip">
                {{ variant }}
                <button
                    type="button"
                    class="variants-input__remove"
                    :aria-label="`Retirer la variante ${variant}`"
                    @click="remove(variant)"
                ><X size="0.75rem" aria-hidden="true" /></button>
            </li>
        </TransitionGroup>
        <input
            v-model="draft"
            class="variants-input__field"
            type="text"
            placeholder="Ajouter une variante puis Entrée"
            :aria-label="inputLabel"
            @keydown.enter.prevent="add"
            @blur="add"
        >
    </div>
</template>

<style scoped>
.variants-input { display: flex; flex-direction: column; gap: var(--space-2); }

.variants-input__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-1);
    margin: 0;
    padding: 0;
    list-style: none;
}

.variants-input__chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: 0.125rem var(--space-2);
    border-radius: 62.4375rem;
    background: var(--color-accent-soft);
    color: var(--color-accent-strong);
    font-size: 0.85rem;
}

.variants-input__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    cursor: pointer;
    color: inherit;
    padding: 0;
    line-height: 1;
}

.variants-input__chip-enter-active,
.variants-input__chip-leave-active { transition: opacity var(--transition), transform var(--transition); }
.variants-input__chip-enter-from,
.variants-input__chip-leave-to { opacity: 0; transform: scale(0.9); }
</style>
