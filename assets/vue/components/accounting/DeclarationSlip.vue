<script setup>
import { computed } from 'vue';
import { CircleCheck } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { formatDate, formatDateTime, fromToday } from '../../composables/useDate.js';
import { plural } from '../../composables/usePlural.js';
import { PERIOD_STATUSES, periodLabel } from '../../composables/useAccounting.js';

const props = defineProps({
    period: { type: Object, required: true },
    rate: { type: Number, required: true },
    saving: { type: Boolean, default: false },
});
const emit = defineEmits(['declare', 'withdraw']);

const status = computed(() => PERIOD_STATUSES[props.period.status]);
const declarable = computed(() => ['due', 'late', 'changed'].includes(props.period.status));
const over = computed(() => !['current', 'upcoming', 'inactive'].includes(props.period.status));
</script>

<template>
    <section class="declaration-slip" :aria-label="`Déclaration ${periodLabel(period)}`">
        <header class="declaration-slip__header">
            <div>
                <h2 class="declaration-slip__period">{{ periodLabel(period) }}</h2>
                <p class="declaration-slip__dates">Du {{ formatDate(period.start) }} au {{ formatDate(period.end) }}</p>
            </div>
            <StatusBadge :tone="status.tone">{{ status.label }}</StatusBadge>
        </header>

        <div class="declaration-slip__line">
            <span class="declaration-slip__box">
                <span class="declaration-slip__box-label">Ventes de marchandises (BIC)</span>
                <span class="declaration-slip__box-hint">Chiffre d'affaires à déclarer</span>
            </span>
            <span class="declaration-slip__amount"><MoneyAmount :cents="period.turnover" /></span>
        </div>

        <dl class="declaration-slip__facts">
            <div><dt>Commandes</dt><dd>{{ period.orderCount }}</dd></div>
            <div><dt>Cotisations estimées ({{ String(rate).replace('.', ',') }} %)</dt><dd><MoneyAmount :cents="period.contribution" /></dd></div>
            <div>
                <dt>À déclarer avant le</dt>
                <dd>{{ formatDate(period.deadline) }} <span class="declaration-slip__relative">({{ fromToday(period.deadline) }})</span></dd>
            </div>
        </dl>

        <p v-if="period.status === 'changed'" class="declaration-slip__notice" role="status">
            Déclaré <MoneyAmount :cents="period.declaredTurnover" /> le {{ formatDateTime(period.declaredAt) }} ; les commandes de la période ont changé depuis. Corrigez la déclaration sur urssaf.fr puis marquez-la à nouveau.
        </p>
        <p v-else-if="period.status === 'declared'" class="declaration-slip__done">
            <CircleCheck size="1rem" aria-hidden="true" /> Déclarée le {{ formatDateTime(period.declaredAt) }}
        </p>
        <p v-else-if="period.status === 'inactive'" class="declaration-slip__hint">Cette période précède la première vente enregistrée dans MossyTrunk.</p>
        <p v-else-if="!over" class="declaration-slip__hint">La période n'est pas terminée : le montant peut encore évoluer.</p>
        <p v-else-if="period.turnover === 0" class="declaration-slip__hint">Aucune vente : déclarez tout de même un chiffre d'affaires de 0 €.</p>

        <footer class="declaration-slip__actions">
            <ConfirmButton
                v-if="period.status === 'declared' || period.status === 'changed'"
                variant="ghost"
                label="Annuler la déclaration"
                confirm-label="Annuler"
                message="La période repassera « à déclarer » dans MossyTrunk. Cela ne change rien sur urssaf.fr."
                @confirm="emit('withdraw', period)"
            />
            <BaseButton v-if="declarable" :loading="saving" @click="emit('declare', period)">
                Marquer comme déclarée ({{ plural(period.orderCount, 'commande') }})
            </BaseButton>
        </footer>
    </section>
</template>

<style scoped>
.declaration-slip {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding: var(--space-5);
    border: 0.0625rem solid var(--color-border);
    border-left: 0.25rem solid var(--color-accent);
    border-radius: var(--radius);
    background: var(--color-surface);
}

.declaration-slip__header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); }
.declaration-slip__period { margin: 0; font-size: 1.5rem; }
.declaration-slip__dates { margin: var(--space-1) 0 0; color: var(--color-muted); font-size: 0.85rem; }

.declaration-slip__line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 0.0625rem dashed var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-bg);
}

.declaration-slip__box { display: flex; flex-direction: column; }
.declaration-slip__box-label { font-weight: 600; }
.declaration-slip__box-hint { color: var(--color-muted); font-size: 0.85rem; }
.declaration-slip__amount { font-family: var(--font-display); font-size: 2.5rem; line-height: 1; color: var(--color-ink); font-variant-numeric: tabular-nums; }

.declaration-slip__facts { display: flex; gap: var(--space-6); flex-wrap: wrap; margin: 0; }
.declaration-slip__facts dt { color: var(--color-muted); font-size: 0.8rem; }
.declaration-slip__facts dd { margin: 0; font-weight: 600; font-variant-numeric: tabular-nums; }
.declaration-slip__relative { color: var(--color-muted); font-weight: 400; }

.declaration-slip__notice { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-warning-soft); color: var(--color-warning); font-size: 0.9rem; }
.declaration-slip__done { display: flex; align-items: center; gap: var(--space-2); margin: 0; color: var(--color-accent-strong); }
.declaration-slip__hint { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.declaration-slip__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }

@media (max-width: 40rem) {
    .declaration-slip__line { flex-direction: column; align-items: flex-start; }
    .declaration-slip__amount { font-size: 2rem; }
}
</style>
