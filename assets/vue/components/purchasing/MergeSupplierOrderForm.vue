<script setup>
import { onMounted, ref } from 'vue';
import { RadioGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import RadioCard from '../ui/RadioCard.vue';
import { formatDate } from '../../composables/useDate.js';

const props = defineProps({
    order: { type: Object, required: true },
    orders: { type: Function, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['merged', 'cancel']);
const { t } = useI18n();

const candidates = ref(null);
const chosen = ref('');
const error = ref(null);
const saving = ref(false);

const mergeable = (other) => other.id !== props.order.id && other.supplier.id === props.order.supplier.id && other.status === props.order.status;

onMounted(async () => {
    candidates.value = (await props.orders()).filter(mergeable);
    chosen.value = candidates.value[0]?.id ?? '';
});

async function onSubmit() {
    saving.value = true;
    error.value = null;
    try {
        await props.submit(chosen.value);
        emit('merged', candidates.value.find((other) => other.id === chosen.value));
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="merge-supplier-order-form" novalidate @submit.prevent="onSubmit">
        <p class="merge-supplier-order-form__intro">{{ t('purchasing.merge.intro', { reference: order.reference }) }}</p>
        <FormError v-if="error">{{ error }}</FormError>
        <EmptyState v-if="candidates && candidates.length === 0">{{ t('purchasing.merge.none') }}</EmptyState>
        <RadioGroupRoot v-else-if="candidates" v-model="chosen" class="merge-supplier-order-form__orders" :aria-label="t('purchasing.merge.choose')">
            <RadioCard v-for="other in candidates" :key="other.id" :value="other.id" class="merge-supplier-order-form__order">
                <span class="merge-supplier-order-form__summary">
                    <span class="merge-supplier-order-form__reference">{{ other.reference }}</span>
                    <span class="merge-supplier-order-form__meta">{{ formatDate(other.orderedOn) }}, {{ t('purchasing.merge.units', other.orderedUnits) }}</span>
                </span>
                <MoneyAmount class="merge-supplier-order-form__total" :cents="other.total" />
            </RadioCard>
        </RadioGroupRoot>
        <FormActions>
            <BaseButton variant="ghost" @click="emit('cancel')">{{ t('purchasing.merge.cancel') }}</BaseButton>
            <BaseButton type="submit" :loading="saving" :disabled="!chosen">{{ t('purchasing.merge.submit') }}</BaseButton>
        </FormActions>
    </form>
</template>

<style scoped>
.merge-supplier-order-form { display: flex; flex-direction: column; gap: var(--space-4); }
.merge-supplier-order-form__intro { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }
.merge-supplier-order-form__orders { display: flex; flex-direction: column; gap: var(--space-2); max-height: 40vh; overflow-y: auto; }

.merge-supplier-order-form__order { align-items: center; }

.merge-supplier-order-form__summary { display: flex; flex: 1; flex-direction: column; min-width: 0; }
.merge-supplier-order-form__reference { color: var(--color-ink); font-weight: 500; }
.merge-supplier-order-form__meta { color: var(--color-muted); font-size: var(--font-size-sm); }
.merge-supplier-order-form__total { font-weight: 600; }
</style>
