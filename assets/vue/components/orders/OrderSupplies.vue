<script setup>
import { Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import ConfirmButton from '../ui/ConfirmButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    supplies: { type: Array, required: true },
    removable: { type: Boolean, default: true },
});
const emit = defineEmits(['remove']);
const { t } = useI18n();
</script>

<template>
    <EmptyState v-if="supplies.length === 0">{{ t('orders.supplies.empty') }}</EmptyState>
    <ul v-else class="order-supplies">
        <li v-for="supply in supplies" :key="supply.id" class="order-supplies__row">
            <span class="order-supplies__label">{{ supply.quantity }} × {{ supply.label }}</span>
            <MoneyAmount :cents="supply.cost" class="order-supplies__cost" />
            <ConfirmButton
                v-if="removable"
                :icon="Trash2"
                :label="t('orders.supplies.remove', { label: supply.label })"
                :message="t('orders.supplies.removeMessage')"
                @confirm="emit('remove', supply)"
            />
        </li>
    </ul>
</template>

<style scoped>
.order-supplies { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.order-supplies__row { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-top: 0.0625rem solid var(--color-border); }
.order-supplies__row:first-child { border-top: none; }
.order-supplies__label { flex: 1 1 auto; min-width: 0; }
.order-supplies__cost { color: var(--color-muted); font-variant-numeric: tabular-nums; }
</style>
