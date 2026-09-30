<script setup>
import { computed } from 'vue';
import { Check } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { ColorSwatchPickerItem, ColorSwatchPickerItemIndicator, ColorSwatchPickerRoot } from 'reka-ui';
import TypeCustomColor from './TypeCustomColor.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { availableTypeColors } from '../../composables/useTypeColor.js';

const color = defineModel({ type: String, required: true });
const { types } = useProductTypes();
const { t } = useI18n();

const colors = computed(() => availableTypeColors(types.value, color.value));
</script>

<template>
    <div class="type-color-picker">
        <ColorSwatchPickerRoot v-model="color" selection-behavior="replace" class="type-color-picker__swatches" :aria-label="t('products.colors.label')">
            <ColorSwatchPickerItem
                v-for="option in colors"
                :key="option.value"
                :value="option.value"
                :aria-label="option.label"
                :title="option.label"
                class="type-color-picker__swatch"
            >
                <ColorSwatchPickerItemIndicator class="type-color-picker__check">
                    <Check size="0.875rem" :stroke-width="3" aria-hidden="true" />
                </ColorSwatchPickerItemIndicator>
            </ColorSwatchPickerItem>
        </ColorSwatchPickerRoot>
        <TypeCustomColor :initial="color" @pick="color = $event" />
    </div>
</template>

<style scoped>
.type-color-picker { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); }
.type-color-picker :deep(.type-color-picker__swatches) { display: contents; }

.type-color-picker__swatch {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    background: var(--reka-color-swatch-picker-item-color);
    cursor: pointer;
    transition: box-shadow var(--transition), transform var(--transition);
}

.type-color-picker__swatch:hover { transform: scale(1.08); }
.type-color-picker__swatch[data-state='checked'] { box-shadow: 0 0 0 0.125rem var(--color-surface), 0 0 0 0.25rem var(--color-ink); }
.type-color-picker__swatch:focus-visible,
.type-color-picker__swatch[data-highlighted] { outline: 0.125rem solid var(--color-accent); outline-offset: 0.25rem; }
.type-color-picker__check { display: inline-flex; color: #ffffff; }

@media (prefers-reduced-motion: reduce) {
    .type-color-picker__swatch { transition: none; }
    .type-color-picker__swatch:hover { transform: none; }
}
</style>
