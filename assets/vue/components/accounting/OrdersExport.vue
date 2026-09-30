<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Download } from '@lucide/vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseDateRangePicker from '../ui/BaseDateRangePicker.vue';
import { formatDate } from '../../composables/useDate.js';

const props = defineProps({
    urlFor: { type: Function, required: true },
});

const { t } = useI18n();

const iso = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const today = new Date();
const quarterStart = Math.floor(today.getMonth() / 3) * 3;
const presets = {
    lastMonth: { label: 'accounting.export.lastMonth', from: iso(new Date(today.getFullYear(), today.getMonth() - 1, 1)), to: iso(new Date(today.getFullYear(), today.getMonth(), 0)) },
    lastQuarter: { label: 'accounting.export.lastQuarter', from: iso(new Date(today.getFullYear(), quarterStart - 3, 1)), to: iso(new Date(today.getFullYear(), quarterStart, 0)) },
    thisYear: { label: 'accounting.export.thisYear', from: `${today.getFullYear()}-01-01`, to: iso(today) },
    custom: { label: 'accounting.export.custom' },
};

const choice = ref('lastMonth');
const customFrom = ref('');
const customTo = ref('');

const range = computed(() => (choice.value === 'custom' ? { from: customFrom.value, to: customTo.value } : presets[choice.value]));
const ready = computed(() => Boolean(range.value.from && range.value.to));
</script>

<template>
    <div class="orders-export">
        <ToggleGroupRoot :model-value="choice" type="single" class="orders-export__presets" :aria-label="t('accounting.export.label')" @update:model-value="(value) => value && (choice = value)">
            <ToggleGroupItem v-for="(preset, key) in presets" :key="key" :value="key" class="orders-export__preset">{{ t(preset.label) }}</ToggleGroupItem>
        </ToggleGroupRoot>
        <div v-if="choice === 'custom'" class="orders-export__custom">
            <BaseDateRangePicker v-model:start="customFrom" v-model:end="customTo" :aria-label="t('accounting.export.custom')" />
        </div>
        <div class="orders-export__footer">
            <p class="orders-export__summary">
                <template v-if="ready">{{ t('accounting.export.summary', { from: formatDate(range.from), to: formatDate(range.to) }) }}</template>
                <template v-else>{{ t('accounting.export.chooseDates') }}</template>
            </p>
            <BaseButton v-if="ready" :href="props.urlFor(range.from, range.to)" variant="secondary" download data-turbo="false">
                <Download size="1rem" aria-hidden="true" /> {{ t('accounting.export.download') }}
            </BaseButton>
        </div>
    </div>
</template>

<style scoped>
.orders-export { display: flex; flex-direction: column; gap: var(--space-4); }
.orders-export__presets { display: flex; gap: var(--space-2); flex-wrap: wrap; }

.orders-export__preset {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.85rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.orders-export__preset:hover { border-color: var(--color-ink); }
.orders-export__preset:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.orders-export__preset[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }
.orders-export__custom { max-width: 24rem; }
.orders-export__footer { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); flex-wrap: wrap; }
.orders-export__summary { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
</style>
