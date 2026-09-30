<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    events: { type: Array, required: true },
});
const selected = defineModel({ type: String, required: true });
const { t } = useI18n();

const options = computed(() => [{ value: '', label: t('orders.eventFilter.all') }, ...props.events.map((event) => ({ value: event.id, label: event.name }))]);
</script>

<template>
    <div class="event-filter">
        <span class="event-filter__label">{{ t('orders.eventFilter.label') }}</span>
        <BaseSelect v-model="selected" :options="options" size="small" :aria-label="t('orders.eventFilter.label')" />
    </div>
</template>

<style scoped>
.event-filter { display: inline-flex; align-items: center; gap: var(--space-2); }
.event-filter__label { color: var(--color-muted); font-size: 0.9rem; }
</style>
