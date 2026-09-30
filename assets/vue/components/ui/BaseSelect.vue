<script setup>
import { computed } from 'vue';
import { Check, ChevronDown } from '@lucide/vue';
import {
    SelectContent,
    SelectItem,
    SelectItemIndicator,
    SelectItemText,
    SelectPortal,
    SelectRoot,
    SelectTrigger,
    SelectValue,
    SelectViewport,
} from 'reka-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({ inheritAttrs: false });

const props = defineProps({
    options: { type: Array, required: true },
    placeholder: { type: String, default: undefined },
    size: { type: String, default: 'default' },
});
const model = defineModel({ type: [String, Number], default: '' });

const NONE = '__none__';
const toItemValue = (value) => (value === '' ? NONE : String(value));

const items = computed(() => props.options.map((option) => ({ ...option, itemValue: toItemValue(option.value) })));

const selected = computed({
    get: () => {
        const item = items.value.find((option) => option.value === model.value);
        return item ? item.itemValue : '';
    },
    set: (itemValue) => {
        model.value = items.value.find((option) => option.itemValue === itemValue)?.value ?? '';
    },
});
</script>

<template>
    <SelectRoot v-model="selected">
        <SelectTrigger :class="['select', `select--${size}`]" v-bind="$attrs">
            <SelectValue class="select__value" :placeholder="placeholder ?? t('ui.select.placeholder')" />
            <ChevronDown class="select__chevron" size="1rem" aria-hidden="true" />
        </SelectTrigger>
        <SelectPortal>
            <SelectContent class="select__content" position="popper" :side-offset="4">
                <SelectViewport class="select__viewport">
                    <SelectItem
                        v-for="item in items"
                        :key="item.itemValue"
                        :value="item.itemValue"
                        :disabled="item.disabled"
                        class="select__item"
                    >
                        <SelectItemText>{{ item.label }}</SelectItemText>
                        <SelectItemIndicator class="select__indicator">
                            <Check size="0.875rem" aria-hidden="true" />
                        </SelectItemIndicator>
                    </SelectItem>
                </SelectViewport>
            </SelectContent>
        </SelectPortal>
    </SelectRoot>
</template>

<style scoped>
.select {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-2);
    width: 100%;
    min-height: 2.375rem;
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-text);
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.select--small { width: auto; min-height: 2rem; padding: var(--space-1) var(--space-2); }

.select:hover { border-color: var(--color-ink); }

.select:focus-visible,
.select[data-state="open"] {
    outline: none;
    border-color: var(--color-accent);
    box-shadow: 0 0 0 0.1875rem var(--color-accent-soft);
}

.select__value { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.select[data-placeholder] .select__value { color: var(--color-subtle); }
.select__chevron { flex-shrink: 0; color: var(--color-muted); transition: transform var(--transition); }
.select[data-state="open"] .select__chevron { transform: rotate(180deg); }
</style>

<style>

.select__content {
    z-index: 60;
    min-width: var(--reka-select-trigger-width);
    max-height: var(--reka-select-content-available-height);
    overflow: hidden;
    background: var(--color-surface);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    animation: select-in var(--transition);
}

.select__viewport { padding: var(--space-1); }

.select__item {
    position: relative;
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

.select__item[data-highlighted] { background: var(--color-bg); }
.select__item[data-state="checked"] { color: var(--color-accent-strong); font-weight: 600; }
.select__item[data-disabled] { color: var(--color-subtle); cursor: default; }
.select__indicator { display: inline-flex; color: var(--color-accent); }

@keyframes select-in {
    from { opacity: 0; transform: translateY(-0.25rem); }
}
</style>
