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
        <SelectTrigger :class="['control', 'select', `select--${size}`]" v-bind="$attrs">
            <SelectValue class="select__value" :placeholder="placeholder ?? t('ui.select.placeholder')" />
            <ChevronDown class="select__chevron" size="1rem" aria-hidden="true" />
        </SelectTrigger>
        <SelectPortal>
            <SelectContent class="popover select__content" position="popper" :side-offset="4">
                <SelectViewport class="select__viewport">
                    <SelectItem
                        v-for="item in items"
                        :key="item.itemValue"
                        :value="item.itemValue"
                        :disabled="item.disabled"
                        class="popover__item"
                    >
                        <SelectItemText>{{ item.label }}</SelectItemText>
                        <SelectItemIndicator class="popover__indicator">
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
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
}

.select--small { width: auto; min-height: 2rem; padding: var(--space-1) var(--space-2); }

.select:hover { border-color: var(--color-ink); }

.select__value { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.select[data-placeholder] .select__value { color: var(--color-subtle); }
.select__chevron { flex-shrink: 0; color: var(--color-muted); transition: transform var(--transition); }
.select[data-state="open"] .select__chevron { transform: rotate(180deg); }
</style>

<style>

.select__content {
    min-width: var(--reka-select-trigger-width);
    max-height: var(--reka-select-content-available-height);
    overflow: hidden;
}

.select__viewport { padding: var(--space-1); }


</style>
