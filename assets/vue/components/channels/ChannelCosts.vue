<script setup>
import { Pencil, Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import ConfirmButton from '../ui/ConfirmButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import IconButton from '../ui/IconButton.vue';
import { formatCostAmount } from '../../composables/useChannelCosts.js';

defineProps({
    costs: { type: Array, required: true },
});
const emit = defineEmits(['edit', 'remove']);
const { t } = useI18n();
</script>

<template>
    <EmptyState v-if="costs.length === 0">{{ t('channels.costs.empty') }}</EmptyState>
    <ul v-else class="channel-costs">
        <li v-for="cost in costs" :key="cost.id" class="channel-costs__row">
            <span class="channel-costs__label">
                {{ cost.label }}
            </span>
            <span class="channel-costs__amount">{{ formatCostAmount(cost) }}</span>
            <span class="channel-costs__actions">
                <IconButton :icon="Pencil" :label="t('channels.costs.edit', { label: cost.label })" @click="emit('edit', cost)" />
                <ConfirmButton :icon="Trash2" :label="t('channels.costs.remove', { label: cost.label })" :message="t('channels.costs.removeMessage')" @confirm="emit('remove', cost)" />
            </span>
        </li>
    </ul>
</template>

<style scoped>
.channel-costs { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.channel-costs__row { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-top: 0.0625rem solid var(--color-border); }
.channel-costs__row:first-child { border-top: none; }
.channel-costs__label { flex: 1 1 auto; min-width: 0; }
.channel-costs__amount { font-variant-numeric: tabular-nums; }
.channel-costs__actions { display: flex; gap: var(--space-1); }
</style>
