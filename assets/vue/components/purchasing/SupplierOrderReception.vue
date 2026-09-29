<script setup>
import { computed, reactive, ref } from 'vue';
import { Check } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import StatusBadge from '../ui/StatusBadge.vue';

const props = defineProps({
    order: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['received', 'cancel']);

const counts = reactive(Object.fromEntries(props.order.lines.map((line) => [line.id, null])));
const step = ref(0);
const saving = ref(false);
const error = ref(null);

const lines = computed(() => props.order.lines);
const reviewing = computed(() => step.value === lines.value.length);
const current = computed(() => lines.value[step.value] ?? null);
const counted = (line) => counts[line.id] !== null && counts[line.id] !== undefined;
const allCounted = computed(() => lines.value.every(counted));
const delta = (line) => (counted(line) ? counts[line.id] - line.orderedQuantity : null);
const realUnitCost = (line) => (counted(line) && counts[line.id] > 0 ? Math.round(line.landedCost / counts[line.id]) : null);

function conform() {
    counts[current.value.id] = current.value.orderedQuantity;
    next();
}

function next() {
    if (!current.value || counted(current.value)) {
        step.value = Math.min(step.value + 1, lines.value.length);
    }
}

async function validate() {
    saving.value = true;
    error.value = null;
    try {
        await props.submit(lines.value.map((line) => ({ lineId: line.id, received: counts[line.id] })));
        emit('received');
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="reception">
        <ol class="reception__steps" aria-label="Lignes à contrôler">
            <li v-for="(line, index) in lines" :key="line.id">
                <button
                    type="button"
                    :class="['reception__step', { 'reception__step--current': index === step, 'reception__step--done': counted(line) }]"
                    :aria-current="index === step ? 'step' : undefined"
                    @click="step = index"
                >
                    <span class="reception__step-mark">
                        <Check v-if="counted(line)" size="0.75rem" :stroke-width="3" aria-hidden="true" />
                        <template v-else>{{ index + 1 }}</template>
                    </span>
                    <span class="reception__step-label">{{ line.label }}</span>
                    <span v-if="delta(line)" :class="['reception__step-delta', delta(line) > 0 ? 'reception__step-delta--more' : 'reception__step-delta--less']">
                        {{ delta(line) > 0 ? `+${delta(line)}` : delta(line) }}
                    </span>
                </button>
            </li>
            <li>
                <button
                    type="button"
                    :class="['reception__step', { 'reception__step--current': reviewing }]"
                    :disabled="!allCounted"
                    :aria-current="reviewing ? 'step' : undefined"
                    @click="step = lines.length"
                >
                    <span class="reception__step-mark"><Check size="0.75rem" :stroke-width="3" aria-hidden="true" /></span>
                    <span class="reception__step-label">Vérifier et valider</span>
                </button>
            </li>
        </ol>

        <section v-if="current" class="reception__panel" :aria-label="`Ligne ${step + 1} sur ${lines.length}`">
            <p class="reception__progress">Ligne {{ step + 1 }} sur {{ lines.length }}</p>
            <h3 class="reception__label">{{ current.label }}</h3>

            <dl class="reception__figures">
                <div>
                    <dt>Commandé</dt>
                    <dd>{{ current.orderedQuantity }}</dd>
                </div>
                <div>
                    <dt>Coût total</dt>
                    <dd>
                        <MoneyAmount :cents="current.landedCost" />
                        <span v-if="current.landedCost !== current.totalPrice" class="reception__planned">dont remise et livraison</span>
                    </dd>
                </div>
                <div>
                    <dt>Coût unitaire</dt>
                    <dd>
                        <MoneyAmount :cents="realUnitCost(current) ?? current.plannedUnitCost" />
                        <span v-if="realUnitCost(current) !== null && realUnitCost(current) !== current.plannedUnitCost" class="reception__planned">
                            prévu <MoneyAmount :cents="current.plannedUnitCost" />
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="reception__count">
                <BaseButton variant="secondary" @click="conform">Tout est arrivé ({{ current.orderedQuantity }})</BaseButton>
                <span class="reception__or">ou</span>
                <label class="reception__received">
                    <span>Quantité reçue</span>
                    <BaseNumberField :key="current.id" v-model="counts[current.id]" :min="0" label="Quantité reçue" @keydown.enter.prevent="next" />
                </label>
                <StatusBadge v-if="delta(current) > 0" tone="warning">{{ delta(current) }} en plus</StatusBadge>
                <StatusBadge v-else-if="delta(current) < 0" tone="danger">{{ -delta(current) }} en moins</StatusBadge>
                <StatusBadge v-else-if="delta(current) === 0" tone="success">Conforme</StatusBadge>
            </div>
            <p v-if="counted(current) && counts[current.id] === 0" class="reception__hint">Rien reçu : ce produit n'entrera pas en stock et son coût ne sera pas réparti.</p>

            <div class="reception__nav">
                <BaseButton variant="ghost" @click="step === 0 ? emit('cancel') : step--">{{ step === 0 ? 'Annuler' : 'Précédent' }}</BaseButton>
                <BaseButton :disabled="!counted(current)" @click="next">{{ step === lines.length - 1 ? 'Vérifier' : 'Suivant' }}</BaseButton>
            </div>
        </section>

        <section v-else class="reception__panel" aria-label="Vérifier et valider">
            <h3 class="reception__label">Vérifier la réception</h3>
            <table class="reception__summary">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="reception__number">Commandé</th>
                        <th class="reception__number">Reçu</th>
                        <th class="reception__number">Coût unitaire réel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in lines" :key="line.id">
                        <td>{{ line.label }}</td>
                        <td class="reception__number">{{ line.orderedQuantity }}</td>
                        <td class="reception__number">
                            {{ counts[line.id] }}
                            <StatusBadge v-if="delta(line) > 0" tone="warning">+{{ delta(line) }}</StatusBadge>
                            <StatusBadge v-else-if="delta(line) < 0" tone="danger">{{ delta(line) }}</StatusBadge>
                        </td>
                        <td class="reception__number">
                            <MoneyAmount v-if="realUnitCost(line) !== null" :cents="realUnitCost(line)" />
                            <span v-else class="reception__muted">Rien reçu</span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="reception__hint">Les quantités reçues entrent en stock au coût réel. La commande ne pourra plus être modifiée.</p>
            <p v-if="error" class="reception__error" role="alert">{{ error }}</p>
            <div class="reception__nav">
                <BaseButton variant="ghost" @click="step--">Précédent</BaseButton>
                <BaseButton :loading="saving" @click="validate">Valider la réception</BaseButton>
            </div>
        </section>
    </div>
</template>

<style scoped>
.reception { display: grid; grid-template-columns: minmax(12rem, 16rem) minmax(0, 1fr); gap: var(--space-5); align-items: start; }

.reception__steps { display: flex; flex-direction: column; gap: 0.125rem; margin: 0; padding: 0; list-style: none; }

.reception__step {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    width: 100%;
    padding: var(--space-2);
    border: none;
    border-radius: var(--radius);
    background: none;
    color: var(--color-muted);
    font: inherit;
    font-size: 0.9rem;
    text-align: left;
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.reception__step:hover:not(:disabled) { background: var(--color-bg); }
.reception__step:disabled { cursor: default; opacity: 0.5; }
.reception__step:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.0625rem; }
.reception__step--current { background: var(--color-accent-soft); color: var(--color-ink); font-weight: 600; }
.reception__step--done { color: var(--color-text); }

.reception__step-mark {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 1.375rem;
    height: 1.375rem;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 50%;
    background: var(--color-surface);
    font-size: 0.72rem;
    font-variant-numeric: tabular-nums;
}

.reception__step--done .reception__step-mark { border-color: var(--color-accent); background: var(--color-accent); color: var(--color-surface); }
.reception__step-label { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.reception__step-delta { font-size: 0.75rem; font-variant-numeric: tabular-nums; }
.reception__step-delta--more { color: var(--color-warning); }
.reception__step-delta--less { color: var(--color-danger); }

.reception__panel { display: flex; flex-direction: column; gap: var(--space-4); padding: var(--space-5); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-surface); }
.reception__progress { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
.reception__label { margin: calc(-1 * var(--space-3)) 0 0; font-family: var(--font-display); font-size: 1.75rem; font-weight: 400; line-height: 1.2; }

.reception__figures { display: flex; gap: var(--space-6); margin: 0; }
.reception__figures dt { color: var(--color-muted); font-size: 0.8rem; }
.reception__figures dd { margin: 0; font-size: 1.35rem; font-variant-numeric: tabular-nums; }
.reception__planned { display: block; color: var(--color-muted); font-size: 0.8rem; }

.reception__count { display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap; padding: var(--space-4); border-radius: var(--radius); background: var(--color-bg); }
.reception__or { padding-bottom: var(--space-2); color: var(--color-muted); }
.reception__received { display: flex; flex-direction: column; gap: var(--space-1); width: 9rem; font-size: 0.85rem; }
.reception__count .status-badge { margin-bottom: var(--space-2); }

.reception__nav { display: flex; justify-content: flex-end; gap: var(--space-2); }
.reception__hint { margin: 0; color: var(--color-muted); font-size: 0.85rem; }
.reception__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
.reception__muted { color: var(--color-subtle); }

.reception__summary { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.reception__summary th { padding: var(--space-1) var(--space-2); border-bottom: 0.0625rem solid var(--color-border); color: var(--color-muted); font-size: 0.7rem; font-weight: 600; letter-spacing: 0.04rem; text-align: left; text-transform: uppercase; }
.reception__summary td { padding: var(--space-2); border-bottom: 0.0625rem solid var(--color-border); }
.reception__number { text-align: right; font-variant-numeric: tabular-nums; }
.reception__summary th.reception__number { text-align: right; }

@media (max-width: 48rem) {
    .reception { grid-template-columns: 1fr; }
}
</style>
