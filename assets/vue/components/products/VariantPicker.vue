<script setup>
import { computed, watch } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import EmptyState from '../ui/EmptyState.vue';
import { sameVariant } from '../../composables/useVariantStock.js';

const props = defineProps({
    options: { type: Array, required: true },
    empty: { type: String, default: null },
    required: { type: Boolean, default: false },
});
const selected = defineModel({ type: Array, required: true });
const { t } = useI18n();


const choices = computed(() => [
    ...props.options,
    ...selected.value.filter((label) => !props.options.some((option) => sameVariant(option, label))),
]);

const ordered = computed({
    get: () => selected.value,
    set: (labels) => {
        if (props.required && labels.length === 0) {
            return;
        }
        selected.value = choices.value.filter((choice) => labels.includes(choice));
    },
});

watch(() => [props.required, props.options], () => {
    if (props.required && props.options.length > 0 && selected.value.length === 0) {
        selected.value = [props.options[0]];
    }
}, { immediate: true });
</script>

<template>
    <ToggleGroupRoot v-if="choices.length" v-model="ordered" type="multiple" class="variant-picker" :aria-label="t('products.variantPicker.label')">
        <ToggleGroupItem v-for="choice in choices" :key="choice" :value="choice" class="chip chip--accent variant-picker__chip">{{ choice }}</ToggleGroupItem>
    </ToggleGroupRoot>
    <EmptyState v-else inline class="variant-picker__empty">{{ empty ?? t('products.variantPicker.empty') }}</EmptyState>
</template>

<style scoped>
.variant-picker { display: flex; flex-wrap: wrap; gap: var(--space-2); padding-top: 0.125rem; }

.variant-picker__chip { min-width: 2.75rem; }

.variant-picker__empty { padding-top: 0.5625rem; }
</style>
