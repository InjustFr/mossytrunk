import { computed } from 'vue';
import { useProductTypes } from './useProductTypes.js';

export const TYPE_COLORS = [
    { value: '#5b7f3a', label: 'Mousse' },
    { value: '#b5654a', label: 'Terre cuite' },
    { value: '#4f6d8f', label: 'Ardoise' },
    { value: '#c29a2e', label: 'Ocre' },
    { value: '#8a6d8f', label: 'Lavande' },
    { value: '#2f7f7a', label: 'Sarcelle' },
    { value: '#7a5238', label: 'Noyer' },
    { value: '#c07a8a', label: 'Rose poudré' },
    { value: '#a3485a', label: 'Framboise' },
    { value: '#6b7a2f', label: 'Olive' },
    { value: '#3f5a4c', label: 'Sapin' },
    { value: '#9c7b5b', label: 'Sable' },
];

export const customColorLabel = (color) => `Personnalisée ${color.toUpperCase()}`;

export function availableTypeColors(types, ...extra) {
    const known = new Set(TYPE_COLORS.map((color) => color.value));
    const custom = [...types.map((type) => type.color), ...extra]
        .filter(Boolean)
        .map((color) => color.toLowerCase())
        .filter((color) => !known.has(color) && known.add(color));

    return [...TYPE_COLORS, ...custom.map((color) => ({ value: color, label: customColorLabel(color) }))];
}

export const nextTypeColor = (types) => TYPE_COLORS[types.length % TYPE_COLORS.length].value;

export function typeColors(types) {
    return new Map(types.map((type) => [type.name, type.color]));
}

export function useTypeColors() {
    const { types, load } = useProductTypes();
    const colors = computed(() => typeColors(types.value));

    return { colors, load };
}
