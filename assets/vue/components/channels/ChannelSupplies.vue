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
        <ToggleGroupItem v-for="supply in supplies" :key="supply.id" :value="supply.id" class="channel-supplies__chip">{{ supply.displayName }}</ToggleGroupItem>
    </ToggleGroupRoot>
</template>

<style scoped>
.channel-supplies { display: flex; flex-wrap: wrap; gap: var(--space-2); }

.channel-supplies__chip {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    font: inherit;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.channel-supplies__chip:hover { border-color: var(--color-ink); }
.channel-supplies__chip:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.channel-supplies__chip[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }
</style>
