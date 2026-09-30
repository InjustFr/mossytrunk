import { computed } from 'vue';
import { t } from '../i18n/index.js';
import { useProductTypes } from './useProductTypes.js';

export const TYPE_COLORS = [
    { value: '#5b7f3a', name: 'moss' },
    { value: '#b5654a', name: 'terracotta' },
    { value: '#4f6d8f', name: 'slate' },
    { value: '#c29a2e', name: 'ochre' },
    { value: '#8a6d8f', name: 'lavender' },
    { value: '#2f7f7a', name: 'teal' },
    { value: '#7a5238', name: 'walnut' },
    { value: '#c07a8a', name: 'dustyRose' },
    { value: '#a3485a', name: 'raspberry' },
    { value: '#6b7a2f', name: 'olive' },
    { value: '#3f5a4c', name: 'fir' },
    { value: '#9c7b5b', name: 'sand' },
];

export const customColorLabel = (color) => t('products.colors.customNamed', { color: color.toUpperCase() });

export function availableTypeColors(types, ...extra) {
    const known = new Set(TYPE_COLORS.map((color) => color.value));
    const custom = [...types.map((type) => type.color), ...extra]
        .filter(Boolean)
        .map((color) => color.toLowerCase())
        .filter((color) => !known.has(color) && known.add(color));

    return [...TYPE_COLORS.map(({ value, name }) => ({ value, label: t(`products.colors.${name}`) })), ...custom.map((color) => ({ value: color, label: customColorLabel(color) }))];
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
