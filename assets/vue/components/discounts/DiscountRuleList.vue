<script setup>
import { Pencil, Trash2 } from '@lucide/vue';
import BaseSwitch from '../ui/BaseSwitch.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

defineProps({
    rules: { type: Array, required: true },
    selectedId: { type: String, default: null },
});
const emit = defineEmits(['edit', 'toggle', 'remove']);
</script>

<template>
    <EmptyState v-if="rules.length === 0">Aucune remise. Créez un lot, ex. « 3 stickers pour 10 € ».</EmptyState>
    <TransitionGroup v-else name="discount-rule-list__item" tag="ul" class="discount-rule-list">
        <li
            v-for="rule in rules"
            :key="rule.id"
            :class="['discount-rule-list__item', {
                'discount-rule-list__item--inactive': !rule.active,
                'discount-rule-list__item--selected': rule.id === selectedId,
            }]"
            :data-test="`discount-rule-${rule.name}`"
        >
            <div class="discount-rule-list__main">
                <span class="discount-rule-list__name">{{ rule.name }}</span>
                <span class="discount-rule-list__deal">
                    {{ rule.bundleSize }} articles pour <MoneyAmount :cents="rule.bundlePrice" />
                </span>
                <span v-if="rule.types.length" class="discount-rule-list__products">Types : {{ rule.types.map((t) => t.name).join(', ') }}</span>
                <span v-if="rule.products.length" class="discount-rule-list__products">Produits : {{ rule.products.map((p) => p.name).join(', ') }}</span>
            </div>
            <label class="discount-rule-list__toggle">
                <BaseSwitch :default-value="rule.active" @update:model-value="emit('toggle', rule, $event)" />
                {{ rule.active ? 'Active' : 'Inactive' }}
            </label>
            <div class="discount-rule-list__actions">
                <IconButton :icon="Pencil" :label="`Modifier ${rule.name}`" @click="emit('edit', rule)" />
                <ConfirmButton :icon="Trash2" :label="`Supprimer ${rule.name}`" @confirm="emit('remove', rule)" />
            </div>
        </li>
    </TransitionGroup>
</template>

<style scoped>
.discount-rule-list { display: flex; flex-direction: column; gap: var(--space-2); margin: 0; padding: 0; list-style: none; }

.discount-rule-list__item {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-border);
    border-left: 0.25rem solid var(--color-accent);
    border-radius: var(--radius);
    background: var(--color-surface);
    transition: opacity var(--transition), border-color var(--transition), background var(--transition);
}

.discount-rule-list__item--inactive { opacity: 0.6; border-left-color: var(--color-border); }
.discount-rule-list__item--selected { background: var(--color-accent-soft); }

.discount-rule-list__main { flex: 1; display: flex; flex-direction: column; }
.discount-rule-list__name { font-weight: 600; }
.discount-rule-list__deal { font-size: 0.9rem; }
.discount-rule-list__products { color: var(--color-muted); font-size: 0.85rem; }

.discount-rule-list__toggle { display: flex; align-items: center; gap: var(--space-2); font-size: 0.9rem; cursor: pointer; }

.discount-rule-list__actions { display: flex; align-items: center; gap: var(--space-1); }

.discount-rule-list__item-enter-active,
.discount-rule-list__item-leave-active { transition: opacity var(--transition), transform var(--transition); }
.discount-rule-list__item-enter-from,
.discount-rule-list__item-leave-to { opacity: 0; transform: translateY(-0.25rem); }
</style>
