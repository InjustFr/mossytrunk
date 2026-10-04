<script setup>
import { computed, onMounted, ref } from 'vue';
import { RadioGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import RadioCard from '../ui/RadioCard.vue';
import PaymentMethod from './PaymentMethod.vue';
import { formatDateTime } from '../../composables/useDate.js';

const props = defineProps({
    order: { type: Object, required: true },
    candidates: { type: Function, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['merged', 'cancel']);
const { t } = useI18n();

const orders = ref(null);
const chosen = ref('');
const error = ref(null);
const saving = ref(false);

const distance = (other) => Math.abs(new Date(other.placedAt) - new Date(props.order.placedAt));
const sorted = computed(() => [...(orders.value ?? [])].sort((a, b) => distance(a) - distance(b)));

onMounted(async () => {
    orders.value = await props.candidates();
    chosen.value = sorted.value[0]?.id ?? '';
});

async function onSubmit() {
    saving.value = true;
    error.value = null;
    try {
        await props.submit(chosen.value);
        emit('merged', orders.value.find((other) => other.id === chosen.value));
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="merge-order-form" novalidate @submit.prevent="onSubmit">
        <p class="merge-order-form__intro">{{ t('orders.merge.intro', { reference: order.reference }) }}</p>
        <FormError v-if="error">{{ error }}</FormError>
        <EmptyState v-if="orders && orders.length === 0">{{ t('orders.merge.none') }}</EmptyState>
        <RadioGroupRoot v-else-if="orders" v-model="chosen" class="merge-order-form__orders" :aria-label="t('orders.merge.choose')">
            <RadioCard v-for="other in sorted" :key="other.id" :value="other.id" class="merge-order-form__order">
                <span class="merge-order-form__summary">
                    <span class="merge-order-form__when">{{ formatDateTime(other.placedAt) }}</span>
                    <span class="merge-order-form__meta">
                        {{ t('orders.merge.items', other.itemCount) }} · <PaymentMethod :method="other.paymentMethod" />
                        <template v-if="other.externalReferences.length"> · {{ other.externalReferences.join(', ') }}</template>
                    </span>
                </span>
                <MoneyAmount class="merge-order-form__total" :cents="other.total" />
            </RadioCard>
        </RadioGroupRoot>
        <FormActions>
            <BaseButton variant="ghost" @click="emit('cancel')">{{ t('orders.merge.cancel') }}</BaseButton>
            <BaseButton type="submit" :loading="saving" :disabled="!chosen">{{ t('orders.merge.submit') }}</BaseButton>
        </FormActions>
    </form>
</template>

<style scoped>
.merge-order-form { display: flex; flex-direction: column; gap: var(--space-4); }
.merge-order-form__intro { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }
.merge-order-form__orders { display: flex; flex-direction: column; gap: var(--space-2); max-height: 40vh; overflow-y: auto; }

.merge-order-form__order { align-items: center; }

.merge-order-form__summary { display: flex; flex: 1; flex-direction: column; min-width: 0; }
.merge-order-form__when { color: var(--color-ink); font-weight: 500; }
.merge-order-form__meta { color: var(--color-muted); font-size: var(--font-size-sm); }
.merge-order-form__total { font-weight: 600; }
</style>
