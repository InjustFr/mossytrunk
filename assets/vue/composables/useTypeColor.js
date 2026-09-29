import { computed } from 'vue';
import { useProductTypes } from './useProductTypes.js';

const PALETTE = ['#5b7f3a', '#b5654a', '#4f6d8f', '#c29a2e', '#8a6d8f', '#2f7f7a', '#7a5238', '#c07a8a'];

const collator = new Intl.Collator('fr-FR', { sensitivity: 'base' });

export function typeColors(names) {
    const sorted = [...new Set(names.filter(Boolean))].sort(collator.compare);
    return new Map(sorted.map((name, index) => [name, PALETTE[index % PALETTE.length]]));
}

export function useTypeColors() {
    const { types, load } = useProductTypes();
    const colors = computed(() => typeColors(types.value.map((type) => type.name)));

    return { colors, load };
}
