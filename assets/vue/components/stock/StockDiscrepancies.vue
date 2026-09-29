<script setup>
import { computed } from 'vue';
import { PackageSearch } from '@lucide/vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDateTime } from '../../composables/useDate.js';
import { plural } from '../../composables/usePlural.js';

const props = defineProps({
    checks: { type: Array, required: true },
});
const emit = defineEmits(['dismiss']);

const open = computed(() => props.checks.flatMap((check) => check.lines
    .filter((line) => line.unexplained > 0)
    .map((line) => ({ ...line, checkId: check.id, checkedAt: check.checkedAt }))));
const units = computed(() => open.value.reduce((sum, line) => sum + line.unexplained, 0));
const missedSales = computed(() => open.value.reduce((sum, line) => sum + line.missedSales, 0));
</script>

<template>
    <section v-if="open.length" class="stock-discrepancies" role="alert" aria-labelledby="stock-discrepancies-title">
        <PackageSearch class="stock-discrepancies__icon" size="1.25rem" aria-hidden="true" />
        <div class="stock-discrepancies__content">
            <h3 id="stock-discrepancies-title" class="stock-discrepancies__title">
                Commande manquante probable : {{ plural(units, 'article', 'articles') }} (≈ <MoneyAmount :cents="missedSales" /> de ventes)
            </h3>
            <p class="stock-discrepancies__hint">
                L'inventaire a trouvé moins d'articles que prévu. Ajoutez la commande oubliée pendant l'événement — le stock ne sera pas décompté une seconde fois — ou classez l'écart (casse, perte, cadeau).
            </p>
            <ul class="stock-discrepancies__lines">
                <li v-for="line in open" :key="line.id" class="stock-discrepancies__line">
                    <span><strong>{{ line.label }}</strong> : {{ plural(line.unexplained, 'manquant', 'manquants') }}</span>
                    <span class="stock-discrepancies__date">inventaire du {{ formatDateTime(line.checkedAt) }}</span>
                    <ConfirmButton
                        variant="ghost"
                        label="Classer l'écart"
                        :message="`L'écart de « ${line.label} » ne sera plus signalé comme une commande manquante.`"
                        confirm-label="Classer"
                        @confirm="emit('dismiss', line)"
                    />
                </li>
            </ul>
        </div>
    </section>
</template>

<style scoped>
.stock-discrepancies {
    display: flex;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-warning);
    border-left-width: 0.25rem;
    border-radius: var(--radius);
    background: var(--color-warning-soft);
}

.stock-discrepancies__icon { flex: none; color: var(--color-warning); }
.stock-discrepancies__content { flex: 1; }
.stock-discrepancies__title { margin: 0; color: var(--color-warning); font-size: 1rem; }
.stock-discrepancies__hint { margin: var(--space-1) 0 var(--space-2); color: var(--color-text); font-size: 0.9rem; }
.stock-discrepancies__lines { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }
.stock-discrepancies__line { display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap; }
.stock-discrepancies__date { color: var(--color-muted); font-size: 0.8rem; }
</style>
