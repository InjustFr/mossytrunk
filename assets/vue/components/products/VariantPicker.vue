<script setup>
import { computed } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    options: { type: Array, required: true },
    empty: { type: String, default: null },
});
const selected = defineModel({ type: Array, required: true });
const { t } = useI18n();

const same = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();

const choices = computed(() => [
    ...props.options,
    ...selected.value.filter((label) => !props.options.some((option) => same(option, label))),
]);

const ordered = computed({
    get: () => selected.value,
    set: (labels) => {
        selected.value = choices.value.filter((choice) => labels.includes(choice));
    },
});
</script>

<template>
    <ToggleGroupRoot v-if="choices.length" v-model="ordered" type="multiple" class="variant-picker" :aria-label="t('products.variantPicker.label')">
        <ToggleGroupItem v-for="choice in choices" :key="choice" :value="choice" class="variant-picker__chip">{{ choice }}</ToggleGroupItem>
    </ToggleGroupRoot>
    <p v-else class="variant-picker__empty">{{ empty ?? t('products.variantPicker.empty') }}</p>
</template>

<style scoped>
.variant-picker { display: flex; flex-wrap: wrap; gap: var(--space-2); padding-top: 0.125rem; }

.variant-picker__chip {
    min-width: 2.75rem;
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    color: var(--color-muted);
    cursor: pointer;
    font-size: 0.875rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.variant-picker__chip:hover { border-color: var(--color-ink); color: var(--color-ink); }
.variant-picker__chip:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.variant-picker__chip[data-state="on"] { background: var(--color-accent-soft); border-color: var(--color-accent); color: var(--color-accent-strong); font-weight: 500; }

.variant-picker__empty { margin: 0; padding-top: 0.5625rem; color: var(--color-muted); font-size: 0.875rem; }
</style>
