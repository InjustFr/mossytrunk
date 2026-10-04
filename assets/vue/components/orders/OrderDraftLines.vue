<script setup>
import { computed } from 'vue';
import { X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseNumberField from '../ui/BaseNumberField.vue';
import EmptyState from '../ui/EmptyState.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    lines: { type: Array, required: true },
    products: { type: Array, required: true },
});
const emit = defineEmits(['quantity', 'remove']);
const { t } = useI18n();

const productsById = computed(() => new Map(props.products.map((product) => [product.id, product])));
const productOf = (line) => productsById.value.get(line.productId);
const nameOf = (line) => productOf(line)?.displayName ?? '?';
const label = (line) => (line.variant ? `${nameOf(line)} — ${line.variant}` : nameOf(line));
</script>

<template>
    <EmptyState v-if="lines.length === 0" inline>{{ t('orders.draft.empty') }}</EmptyState>
    <TransitionGroup name="order-draft-lines__line" tag="ul" class="order-draft-lines">
        <li v-for="line in lines" :key="line.key" class="order-draft-lines__line">
            <span class="order-draft-lines__label">
                <span class="order-draft-lines__name">{{ nameOf(line) }}</span>
                <span class="order-draft-lines__detail">
                    <template v-if="line.variant">{{ line.variant }} · </template><MoneyAmount :cents="productOf(line)?.sellingPrice ?? 0" />
                </span>
            </span>
            <BaseNumberField
                class="order-draft-lines__quantity"
                :model-value="line.quantity"
                :min="0"
                :label="t('orders.draft.quantityOf', { line: label(line) })"
                @update:model-value="emit('quantity', line.key, $event ?? 0)"
            />
            <MoneyAmount class="order-draft-lines__total" :cents="(productOf(line)?.sellingPrice ?? 0) * line.quantity" />
            <IconButton :icon="X" variant="danger" :label="t('orders.draft.remove', { line: label(line) })" @click="emit('remove', line.key)" />
        </li>
    </TransitionGroup>
</template>

<style scoped>
.order-draft-lines { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.order-draft-lines:empty { display: none; }

.order-draft-lines__line {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 6.5rem 5.5rem auto;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    border-bottom: 0.0625rem solid var(--color-border);
}

.order-draft-lines__line:first-child { border-top: 0.0625rem solid var(--color-border); }
.order-draft-lines__label { display: flex; flex-direction: column; min-width: 0; }
.order-draft-lines__name { color: var(--color-ink); font-size: var(--font-size-md); }
.order-draft-lines__detail { color: var(--color-muted); font-size: var(--font-size-sm); }
.order-draft-lines__total { text-align: right; }

@container form (max-width: 24rem) {
    .order-draft-lines__line { grid-template-columns: minmax(0, 1fr) 5.5rem auto; row-gap: var(--space-1); }
    .order-draft-lines__label { grid-column: 1 / -1; }
    .order-draft-lines__quantity { width: 6.5rem; }
}

.order-draft-lines__line-enter-active,
.order-draft-lines__line-leave-active { transition: opacity var(--transition), transform var(--transition); }
.order-draft-lines__line-enter-from,
.order-draft-lines__line-leave-to { opacity: 0; transform: translateX(-0.375rem); }
</style>
