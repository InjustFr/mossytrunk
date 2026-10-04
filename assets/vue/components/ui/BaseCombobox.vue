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
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({ inheritAttrs: false });

const props = defineProps({
    options: { type: Array, required: true },
    placeholder: { type: String, default: undefined },
    empty: { type: String, default: undefined },
});
const model = defineModel({ type: String, default: '' });

const labelOf = (value) => props.options.find((option) => option.value === value)?.label ?? '';
</script>

<template>
    <ComboboxRoot v-model="model" open-on-click reset-search-term-on-select>
        <ComboboxAnchor class="control combobox">
            <ComboboxInput class="combobox__input" :display-value="labelOf" :placeholder="placeholder ?? t('ui.combobox.placeholder')" v-bind="$attrs" />
            <ComboboxTrigger class="combobox__trigger" :aria-label="t('ui.combobox.showChoices')">
                <ChevronDown size="1rem" aria-hidden="true" />
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxPortal>
            <ComboboxContent class="popover combobox__content" position="popper" :side-offset="4">
                <ComboboxViewport class="combobox__viewport">
                    <ComboboxEmpty class="combobox__empty">{{ empty ?? t('ui.combobox.empty') }}</ComboboxEmpty>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        :disabled="option.disabled"
                        class="popover__item"
                    >
                        <span>{{ option.label }}</span>
                        <ComboboxItemIndicator class="popover__indicator">
                            <Check size="0.875rem" aria-hidden="true" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>

<style scoped>
.combobox { display: flex; align-items: center; padding: 0; }

.combobox__input {
    flex: 1;
    min-width: 0;
    padding: var(--space-2) var(--space-3);
    border: none;
    background: none;
}

.combobox__input:focus { outline: none; }

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
    width: var(--reka-combobox-trigger-width);
    max-height: min(18rem, var(--reka-combobox-content-available-height));
    overflow: hidden;
}

.combobox__viewport { max-height: inherit; padding: var(--space-1); overflow-y: auto; }


.combobox__empty { padding: var(--space-2) var(--space-3); color: var(--color-muted); font-size: var(--font-size-md); }
</style>
