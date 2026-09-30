<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Banknote, CreditCard } from '@lucide/vue';

const props = defineProps({
    method: { type: String, default: null },
});

const { t } = useI18n();

const shown = computed(() => ({
    card: { icon: CreditCard, label: t('orders.payment.card') },
    cash: { icon: Banknote, label: t('orders.payment.cash') },
})[props.method] ?? null);
</script>

<template>
    <span v-if="shown" class="payment-method">
        <component :is="shown.icon" size="0.875rem" :stroke-width="1.75" aria-hidden="true" />
        {{ shown.label }}
    </span>
    <span v-else class="payment-method payment-method--unknown">—</span>
</template>

<style scoped>
.payment-method { display: inline-flex; align-items: center; gap: var(--space-1); white-space: nowrap; }
.payment-method--unknown { color: var(--color-subtle); }
</style>
