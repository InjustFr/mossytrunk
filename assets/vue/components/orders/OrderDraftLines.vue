<script setup>
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    lines: { type: Array, required: true },
    products: { type: Array, required: true },
});
const emit = defineEmits(['quantity', 'remove']);

const productOf = (line) => props.products.find((p) => p.id === line.productId);
const label = (line) => {
    const name = productOf(line)?.name ?? '?';
    return line.variant ? `${name} — ${line.variant}` : name;
};
</script>

<template>
    <TransitionGroup name="order-draft-lines__line" tag="ul" class="order-draft-lines">
        <li v-for="line in lines" :key="line.key" class="order-draft-lines__line">
            <span class="order-draft-lines__label">{{ label(line) }}</span>
            <span class="order-draft-lines__stepper">
                <button type="button" class="order-draft-lines__step" :aria-label="`Retirer un ${label(line)}`" @click="emit('quantity', line.key, line.quantity - 1)">−</button>
                <span class="order-draft-lines__quantity tabular" :aria-label="`Quantité ${label(line)}`">{{ line.quantity }}</span>
                <button type="button" class="order-draft-lines__step" :aria-label="`Ajouter un ${label(line)}`" @click="emit('quantity', line.key, line.quantity + 1)">+</button>
            </span>
            <MoneyAmount class="order-draft-lines__total" :cents="(productOf(line)?.sellingPrice ?? 0) * line.quantity" />
            <button type="button" class="order-draft-lines__remove" :aria-label="`Supprimer ${label(line)}`" @click="emit('remove', line.key)">×</button>
        </li>
    </TransitionGroup>
</template>

<style scoped>
.order-draft-lines { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }

.order-draft-lines__line {
    display: grid;
    grid-template-columns: 1fr auto 90px auto;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-bg);
}

.order-draft-lines__stepper { display: inline-flex; align-items: center; gap: var(--space-2); }

.order-draft-lines__step,
.order-draft-lines__remove {
    width: 28px;
    height: 28px;
    border: 1px solid var(--color-border);
    border-radius: 50%;
    background: var(--color-surface);
    cursor: pointer;
    line-height: 1;
    transition: border-color var(--transition);
}

.order-draft-lines__step:hover { border-color: var(--color-accent); }
.order-draft-lines__remove { border: none; background: none; color: var(--color-muted); font-size: 1.2rem; }
.order-draft-lines__remove:hover { color: var(--color-danger); }
.order-draft-lines__quantity { min-width: 2ch; text-align: center; font-weight: 600; }
.order-draft-lines__total { text-align: right; }

.order-draft-lines__line-enter-active,
.order-draft-lines__line-leave-active { transition: opacity var(--transition), transform var(--transition); }
.order-draft-lines__line-enter-from,
.order-draft-lines__line-leave-to { opacity: 0; transform: translateX(-6px); }
</style>
