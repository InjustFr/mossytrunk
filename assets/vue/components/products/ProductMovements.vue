<script setup>
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDateTime } from '../../composables/useDate.js';
import { MOVEMENTS } from '../../composables/useStock.js';

defineProps({
    movements: { type: Array, required: true },
});
</script>

<template>
    <EmptyState v-if="movements.length === 0">Aucun mouvement : ni achat, ni vente pour l'instant.</EmptyState>
    <DataTable v-else :items="movements" class="product-movements">
        <template #head>
            <tr>
                <th>Date</th>
                <th>Mouvement</th>
                <th>Variante</th>
                <th class="data-table__cell--number">Quantité</th>
                <th class="data-table__cell--number">Coût</th>
            </tr>
        </template>
        <template #default="{ rows }">
            <tr v-for="(movement, index) in rows" :key="`${movement.date}-${index}`">
                <td class="product-movements__date">{{ formatDateTime(movement.date) }}</td>
                <td>
                    <a v-if="movement.link" :href="movement.link">{{ MOVEMENTS[movement.kind] }}</a>
                    <template v-else>{{ MOVEMENTS[movement.kind] }}</template>
                    <span v-if="movement.label" class="product-movements__label">{{ movement.label }}</span>
                </td>
                <td>{{ movement.variant ?? '—' }}</td>
                <td :class="['data-table__cell--number', movement.quantity > 0 ? 'product-movements__in' : 'product-movements__out']">
                    {{ movement.quantity > 0 ? `+${movement.quantity}` : movement.quantity }}
                </td>
                <td class="data-table__cell--number"><MoneyAmount :cents="movement.cost" /></td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.product-movements__date { white-space: nowrap; color: var(--color-muted); }
.product-movements__label { display: block; color: var(--color-muted); font-size: 0.8rem; }
.product-movements__in { color: var(--color-accent-strong); font-weight: 600; }
.product-movements__out { color: var(--color-text); }
</style>
