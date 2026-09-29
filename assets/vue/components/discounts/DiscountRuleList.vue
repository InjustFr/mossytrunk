<script setup>
import { Pencil, Trash2 } from '@lucide/vue';
import BaseSwitch from '../ui/BaseSwitch.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import IconButton from '../ui/IconButton.vue';
import { describeAction, regularPrice, savingOn } from '../../composables/useRulePrice.js';
import { formatCents } from '../../composables/useMoney.js';
import { formatDate } from '../../composables/useDate.js';

const props = defineProps({
    rules: { type: Array, required: true },
    products: { type: Array, required: true },
    selectedId: { type: String, default: null },
});
const emit = defineEmits(['edit', 'toggle', 'remove']);

const range = (min, max) => (min === max ? formatCents(min) : `${formatCents(min)} à ${formatCents(max)}`);

const conditions = (rule) => rule.conditions.map((condition) => `${condition.quantity} × ${condition.name}`).join(' + ');

function period(rule) {
    if (rule.startsOn && rule.endsOn) {
        return `Du ${formatDate(rule.startsOn)} au ${formatDate(rule.endsOn)}`;
    }
    if (rule.startsOn) {
        return `À partir du ${formatDate(rule.startsOn)}`;
    }
    return rule.endsOn ? `Jusqu'au ${formatDate(rule.endsOn)}` : null;
}

const STATUSES = { running: 'En cours', upcoming: 'À venir', expired: 'Expirée' };

function saving(rule) {
    const regular = regularPrice(props.products, rule.conditions);
    if (!regular) {
        return null;
    }
    return {
        regular: range(regular.min, regular.max),
        saved: range(savingOn(regular.min, rule.action), savingOn(regular.max, rule.action)),
    };
}
</script>

<template>
    <EmptyState v-if="rules.length === 0">Aucune remise. Créez-en une, ex. « 2 prints et 1 sticker pour 15 € ».</EmptyState>
    <TransitionGroup v-else name="discount-rule-list__item" tag="ul" class="discount-rule-list">
        <li
            v-for="rule in rules"
            :key="rule.id"
            :class="['discount-rule-list__item', {
                'discount-rule-list__item--inactive': rule.status !== 'running',
                'discount-rule-list__item--selected': rule.id === selectedId,
            }]"
            :data-test="`discount-rule-${rule.name}`"
        >
            <div class="discount-rule-list__main">
                <span class="discount-rule-list__name">
                    {{ rule.name }}
                    <span v-if="rule.status === 'expired'" class="discount-rule-list__status">{{ STATUSES.expired }}</span>
                </span>
                <span class="discount-rule-list__deal">
                    {{ conditions(rule) }} {{ describeAction(rule.action, formatCents) }}
                    <template v-if="saving(rule)">, au lieu de {{ saving(rule).regular }}</template>
                </span>
                <span v-if="period(rule)" class="discount-rule-list__period">{{ period(rule) }}</span>
            </div>
            <p v-if="saving(rule)" class="discount-rule-list__saving">
                <span class="discount-rule-list__saving-label">Économie client</span>
                <span class="discount-rule-list__saving-amount">{{ saving(rule).saved }}</span>
            </p>
            <label v-if="rule.status !== 'expired'" class="discount-rule-list__toggle" :title="rule.status === 'running' ? 'Arrêter : la remise se termine hier' : 'Lancer : la remise commence aujourd\'hui'">
                <BaseSwitch :key="rule.status" :default-value="rule.status === 'running'" @update:model-value="emit('toggle', rule, $event)" />
                {{ STATUSES[rule.status] }}
            </label>
            <span v-else class="discount-rule-list__toggle discount-rule-list__toggle--none" />
            <div class="discount-rule-list__actions">
                <IconButton :icon="Pencil" :label="`Modifier ${rule.name}`" @click="emit('edit', rule)" />
                <ConfirmButton :icon="Trash2" :label="`Supprimer ${rule.name}`" @confirm="emit('remove', rule)" />
            </div>
        </li>
    </TransitionGroup>
</template>

<style scoped>
.discount-rule-list { margin: 0; padding: 0; list-style: none; }

.discount-rule-list__item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 9rem 7rem auto;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) var(--space-2);
    border-bottom: 0.0625rem solid var(--color-border);
    transition: opacity var(--transition), background var(--transition);
}

.discount-rule-list__item:last-child { border-bottom: none; }
.discount-rule-list__item--inactive .discount-rule-list__main,
.discount-rule-list__item--inactive .discount-rule-list__saving { opacity: 0.55; }
.discount-rule-list__item--selected { background: var(--color-accent-soft); }

.discount-rule-list__main { display: flex; flex-direction: column; min-width: 0; }
.discount-rule-list__name { display: flex; align-items: center; gap: var(--space-2); font-weight: 600; }
.discount-rule-list__status {
    padding: 0 var(--space-2);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    color: var(--color-muted);
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.discount-rule-list__deal { font-size: 0.9rem; }
.discount-rule-list__period { color: var(--color-muted); font-size: 0.85rem; }

.discount-rule-list__saving { display: flex; flex-direction: column; align-items: flex-end; margin: 0; grid-column: 2; }
.discount-rule-list__saving-label { color: var(--color-muted); font-size: 0.8rem; }
.discount-rule-list__saving-amount { color: var(--color-success); font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }

.discount-rule-list__toggle { grid-column: 3; display: flex; align-items: center; gap: var(--space-2); font-size: 0.9rem; cursor: pointer; }
.discount-rule-list__actions { grid-column: 4; display: flex; align-items: center; gap: var(--space-1); }

.discount-rule-list__item-enter-active,
.discount-rule-list__item-leave-active { transition: opacity var(--transition), transform var(--transition); }
.discount-rule-list__item-enter-from,
.discount-rule-list__item-leave-to { opacity: 0; transform: translateY(-0.25rem); }

@media (max-width: 43.75rem) {
    .discount-rule-list__item { grid-template-columns: 1fr auto; }
    .discount-rule-list__saving,
    .discount-rule-list__toggle,
    .discount-rule-list__actions { grid-column: auto; }
}
</style>
