<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Download } from '@lucide/vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseDateRangePicker from '../ui/BaseDateRangePicker.vue';
import { formatDate, localDay } from '../../composables/useDate.js';

const props = defineProps({
    urlFor: { type: Function, required: true },
});

const { t } = useI18n();

const today = new Date();
const quarterStart = Math.floor(today.getMonth() / 3) * 3;
const presets = {
    lastMonth: { label: 'accounting.export.lastMonth', from: localDay(new Date(today.getFullYear(), today.getMonth() - 1, 1)), to: localDay(new Date(today.getFullYear(), today.getMonth(), 0)) },
    lastQuarter: { label: 'accounting.export.lastQuarter', from: localDay(new Date(today.getFullYear(), quarterStart - 3, 1)), to: localDay(new Date(today.getFullYear(), quarterStart, 0)) },
    thisYear: { label: 'accounting.export.thisYear', from: `${today.getFullYear()}-01-01`, to: localDay(today) },
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
            <ToggleGroupItem v-for="(preset, key) in presets" :key="key" :value="key" class="chip">{{ t(preset.label) }}</ToggleGroupItem>
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

.orders-export__custom { max-width: 24rem; }
.orders-export__footer { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); flex-wrap: wrap; }
.orders-export__summary { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }
</style>
