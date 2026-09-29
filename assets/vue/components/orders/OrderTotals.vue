<script setup>
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    subtotal: { type: Number, required: true },
    discounts: { type: Array, required: true },
    total: { type: Number, required: true },
    linkRules: { type: Boolean, default: false },
});
</script>

<template>
    <dl class="order-totals">
        <div class="order-totals__row">
            <dt>Sous-total</dt>
            <dd><MoneyAmount :cents="subtotal" /></dd>
        </div>
        <TransitionGroup name="order-totals__discount">
            <div v-for="discount in discounts" :key="discount.label" class="order-totals__row order-totals__row--discount">
                <dt>
                    <a v-if="linkRules && discount.ruleId" :href="`/remises?remise=${discount.ruleId}`" class="order-totals__rule">{{ discount.label }}</a>
                    <template v-else>{{ discount.label }}</template>
                </dt>
                <dd>− <MoneyAmount :cents="discount.amount" /></dd>
            </div>
        </TransitionGroup>
        <div class="order-totals__row order-totals__row--total">
            <dt>Total</dt>
            <dd><MoneyAmount :cents="total" data-test="order-total" /></dd>
        </div>
    </dl>
</template>

<style scoped>
.order-totals { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; }
.order-totals__row { display: flex; justify-content: space-between; gap: var(--space-3); }
.order-totals__row dd { margin: 0; }
.order-totals__row--discount { color: var(--color-success); }
.order-totals__rule { color: inherit; text-decoration: underline; text-underline-offset: 0.2em; }
.order-totals__rule:hover { text-decoration-thickness: 0.125rem; }
.order-totals__row--total {
    margin-top: var(--space-1);
    padding-top: var(--space-2);
    border-top: 0.0625rem solid var(--color-border);
    font-size: 1.1rem;
    font-weight: 700;
}

.order-totals__discount-enter-active,
.order-totals__discount-leave-active { transition: opacity var(--transition), transform var(--transition); }
.order-totals__discount-enter-from,
.order-totals__discount-leave-to { opacity: 0; transform: translateY(-0.25rem); }
</style>
