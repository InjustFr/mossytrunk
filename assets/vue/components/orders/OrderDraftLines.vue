<script setup>
import { X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseNumberField from '../ui/BaseNumberField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    lines: { type: Array, required: true },
    products: { type: Array, required: true },
});
const emit = defineEmits(['quantity', 'remove']);
const { t } = useI18n();

const productOf = (line) => props.products.find((p) => p.id === line.productId);
const label = (line) => {
    const name = productOf(line)?.displayName ?? '?';
    return line.variant ? `${name} — ${line.variant}` : name;
};
</script>

<template>
    <TransitionGroup name="order-draft-lines__line" tag="ul" class="order-draft-lines">
        <li v-for="line in lines" :key="line.key" class="order-draft-lines__line">
            <span class="order-draft-lines__label">{{ label(line) }}</span>
            <BaseNumberField
                class="order-draft-lines__quantity"
                :model-value="line.quantity"
                :min="0"
                :label="t('orders.draft.quantityOf', { line: label(line) })"
                @update:model-value="emit('quantity', line.key, $event ?? 0)"
            />
            <MoneyAmount class="order-draft-lines__total" :cents="(productOf(line)?.sellingPrice ?? 0) * line.quantity" />
            <button type="button" class="order-draft-lines__remove" :aria-label="t('orders.draft.remove', { line: label(line) })" @click="emit('remove', line.key)"><X size="1rem" aria-hidden="true" /></button>
        </li>
    </TransitionGroup>
</template>

<style scoped>
.order-draft-lines { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }

.order-draft-lines__line {
    display: grid;
    grid-template-columns: 1fr auto 5.625rem auto;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-bg);
}

.order-draft-lines__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    background: none;
    color: var(--color-muted);
    cursor: pointer;
    transition: color var(--transition);
}

.order-draft-lines__remove:hover { color: var(--color-danger); }
.order-draft-lines__quantity { width: 7.5rem; }
.order-draft-lines__total { text-align: right; }

.order-draft-lines__line-enter-active,
.order-draft-lines__line-leave-active { transition: opacity var(--transition), transform var(--transition); }
.order-draft-lines__line-enter-from,
.order-draft-lines__line-leave-to { opacity: 0; transform: translateX(-0.375rem); }
</style>
