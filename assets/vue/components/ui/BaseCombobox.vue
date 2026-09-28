<script setup>
import { Check, ChevronDown } from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Rechercher…' },
    empty: { type: String, default: 'Aucun résultat.' },
});
const model = defineModel({ type: String, default: '' });

const labelOf = (value) => props.options.find((option) => option.value === value)?.label ?? '';
</script>

<template>
    <ComboboxRoot v-model="model" open-on-click reset-search-term-on-select>
        <ComboboxAnchor class="combobox">
            <ComboboxInput class="combobox__input" :display-value="labelOf" :placeholder="placeholder" v-bind="$attrs" />
            <ComboboxTrigger class="combobox__trigger" aria-label="Afficher les choix">
                <ChevronDown size="1rem" aria-hidden="true" />
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxPortal>
            <ComboboxContent class="combobox__content" position="popper" :side-offset="4">
                <ComboboxViewport class="combobox__viewport">
                    <ComboboxEmpty class="combobox__empty">{{ empty }}</ComboboxEmpty>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        :disabled="option.disabled"
                        class="combobox__item"
                    >
                        <span>{{ option.label }}</span>
                        <ComboboxItemIndicator class="combobox__indicator">
                            <Check size="0.875rem" aria-hidden="true" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>

<style scoped>
.combobox {
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 2.375rem;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.combobox:focus-within {
    border-color: var(--color-accent);
    box-shadow: 0 0 0 0.1875rem var(--color-accent-soft);
}

.combobox .combobox__input {
    flex: 1;
    min-width: 0;
    min-height: auto;
    padding: var(--space-2) var(--space-3);
    border: none;
    background: none;
    box-shadow: none;
}

.combobox .combobox__input:focus { outline: none; border: none; box-shadow: none; }

.combobox__trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    align-self: stretch;
    border: none;
    background: none;
    color: var(--color-muted);
    cursor: pointer;
}

.combobox__trigger[data-state="open"] { transform: rotate(180deg); }
</style>

<style>
.combobox__content {
    z-index: 60;
    width: var(--reka-combobox-trigger-width);
    max-height: min(18rem, var(--reka-combobox-content-available-height));
    overflow: hidden;
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    animation: combobox-in var(--transition);
}

.combobox__viewport { max-height: inherit; padding: var(--space-1); overflow-y: auto; }

.combobox__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    border-radius: calc(var(--radius) - 0.125rem);
    color: var(--color-text);
    cursor: pointer;
    user-select: none;
    outline: none;
}

.combobox__item[data-highlighted] { background: var(--color-bg); }
.combobox__item[data-state="checked"] { color: var(--color-accent-strong); font-weight: 600; }
.combobox__item[data-disabled] { color: var(--color-subtle); cursor: default; }
.combobox__indicator { display: inline-flex; color: var(--color-accent); }
.combobox__empty { padding: var(--space-2) var(--space-3); color: var(--color-muted); font-size: 0.9rem; }

@keyframes combobox-in { from { opacity: 0; transform: translateY(-0.25rem); } }
</style>
