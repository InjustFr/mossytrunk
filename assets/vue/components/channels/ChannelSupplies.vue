<script setup>
import { computed } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import EmptyState from '../ui/EmptyState.vue';

const props = defineProps({
    supplies: { type: Array, required: true },
    offered: { type: Array, required: true },
});
const emit = defineEmits(['change']);
const { t } = useI18n();

const offeredIds = computed(() => props.offered.map((supply) => supply.id));
</script>

<template>
    <EmptyState v-if="supplies.length === 0">
        <a href="/products?kind=supply">{{ t('channels.supplies.none') }}</a>
    </EmptyState>
    <ToggleGroupRoot
        v-else
        :model-value="offeredIds"
        type="multiple"
        class="channel-supplies"
        :aria-label="t('channels.supplies.title')"
        @update:model-value="(ids) => emit('change', ids)"
    >
        <ToggleGroupItem v-for="supply in supplies" :key="supply.id" :value="supply.id" class="chip">{{ supply.displayName }}</ToggleGroupItem>
    </ToggleGroupRoot>
</template>

<style scoped>
.channel-supplies { display: flex; flex-wrap: wrap; gap: var(--space-2); }
</style>
