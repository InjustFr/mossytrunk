<script setup>
import { computed, reactive, ref } from 'vue';
import { Check } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import FormError from '../ui/FormError.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import QuantityGap from '../ui/QuantityGap.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { useFormSubmit } from '../../composables/useFormSubmit.js';

const { t } = useI18n();

const props = defineProps({
    order: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['received', 'cancel']);

const counts = reactive(Object.fromEntries(props.order.lines.map((line) => [line.id, null])));
const step = ref(0);
const { saving, errors, run } = useFormSubmit('form', { byField: false });

const lines = computed(() => props.order.lines);
const reviewing = computed(() => step.value === lines.value.length);
const current = computed(() => lines.value[step.value] ?? null);
const counted = (line) => counts[line.id] !== null && counts[line.id] !== undefined;
const allCounted = computed(() => lines.value.every(counted));
const delta = (line) => (counted(line) ? counts[line.id] - line.orderedQuantity : null);
const realUnitCost = (line) => (counted(line) && counts[line.id] > 0 ? Math.round(Math.round(line.landedCost / counts[line.id]) * props.order.exchangeRate) : null);

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
    await run(async () => {
        await props.submit(lines.value.map((line) => ({ lineId: line.id, received: counts[line.id] })));
        emit('received');
    });
}
</script>

<template>
    <div class="reception">
        <ol class="reception__steps" :aria-label="t('purchasing.reception.steps')">
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
                    <span class="reception__step-label">{{ t('purchasing.reception.review') }}</span>
                </button>
            </li>
        </ol>

        <section v-if="current" class="reception__panel" :aria-label="t('purchasing.reception.lineOf', { step: step + 1, total: lines.length })">
            <p class="reception__progress">{{ t('purchasing.reception.lineOf', { step: step + 1, total: lines.length }) }}</p>
            <h3 class="reception__label">{{ current.label }}</h3>

            <dl class="reception__figures">
                <div>
                    <dt>{{ t('purchasing.reception.ordered') }}</dt>
                    <dd>{{ current.orderedQuantity }}</dd>
                </div>
                <div>
                    <dt>{{ t('purchasing.reception.totalCost') }}</dt>
                    <dd>
                        <MoneyAmount :cents="current.landedCost" :currency="order.currency" />
                        <span v-if="current.landedCost !== current.totalPrice" class="reception__planned">{{ t('purchasing.reception.includingShares') }}</span>
                    </dd>
                </div>
                <div>
                    <dt>{{ t('purchasing.reception.unitCost') }}</dt>
                    <dd>
                        <MoneyAmount :cents="realUnitCost(current) ?? current.plannedUnitCost" />
                        <span v-if="realUnitCost(current) !== null && realUnitCost(current) !== current.plannedUnitCost" class="reception__planned">
                            {{ t('purchasing.reception.planned') }} <MoneyAmount :cents="current.plannedUnitCost" />
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="reception__count">
                <BaseButton variant="secondary" @click="conform">{{ t('purchasing.reception.allArrived', { count: current.orderedQuantity }) }}</BaseButton>
                <span class="reception__or">{{ t('purchasing.reception.or') }}</span>
                <label class="reception__received">
                    <span>{{ t('purchasing.reception.receivedQuantity') }}</span>
                    <BaseNumberField :key="current.id" v-model="counts[current.id]" :min="0" :label="t('purchasing.reception.receivedQuantity')" @keydown.enter.prevent="next" />
                </label>
                <StatusBadge v-if="delta(current) > 0" tone="warning" class="reception__verdict">{{ t('purchasing.reception.more', { count: delta(current) }) }}</StatusBadge>
                <StatusBadge v-else-if="delta(current) < 0" tone="danger" class="reception__verdict">{{ t('purchasing.reception.less', { count: -delta(current) }) }}</StatusBadge>
                <StatusBadge v-else-if="delta(current) === 0" tone="success" class="reception__verdict">{{ t('purchasing.reception.conform') }}</StatusBadge>
            </div>
            <p v-if="counted(current) && counts[current.id] === 0" class="reception__hint">{{ t('purchasing.reception.nothingHint') }}</p>

            <div class="actions-row">
                <BaseButton variant="ghost" @click="step === 0 ? emit('cancel') : step--">{{ step === 0 ? t('common.cancel') : t('purchasing.reception.previous') }}</BaseButton>
                <BaseButton :disabled="!counted(current)" @click="next">{{ step === lines.length - 1 ? t('purchasing.reception.check') : t('purchasing.reception.next') }}</BaseButton>
            </div>
        </section>

        <section v-else class="reception__panel" :aria-label="t('purchasing.reception.review')">
            <h3 class="reception__label">{{ t('purchasing.reception.reviewTitle') }}</h3>
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>{{ t('purchasing.reception.product') }}</th>
                        <th class="compact-table__number">{{ t('purchasing.reception.ordered') }}</th>
                        <th class="compact-table__number">{{ t('purchasing.reception.received') }}</th>
                        <th class="compact-table__number">{{ t('purchasing.reception.realUnitCost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in lines" :key="line.id">
                        <td>{{ line.label }}</td>
                        <td class="compact-table__number">{{ line.orderedQuantity }}</td>
                        <td class="compact-table__number">
                            {{ counts[line.id] }}
                            <QuantityGap :gap="delta(line)" />
                        </td>
                        <td class="compact-table__number">
                            <MoneyAmount v-if="realUnitCost(line) !== null" :cents="realUnitCost(line)" />
                            <span v-else class="reception__muted">{{ t('purchasing.reception.nothingReceived') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="reception__hint">{{ t('purchasing.reception.reviewHint') }}</p>
            <FormError v-if="errors.form">{{ errors.form }}</FormError>
            <div class="actions-row">
                <BaseButton variant="ghost" @click="step--">{{ t('purchasing.reception.previous') }}</BaseButton>
                <BaseButton :loading="saving" @click="validate">{{ t('purchasing.reception.validate') }}</BaseButton>
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
    font-size: var(--font-size-md);
    text-align: left;
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}

.reception__step:hover:not(:disabled) { background: var(--color-bg); }
.reception__step:disabled { cursor: default; opacity: 0.5; }
.reception__step:focus-visible { outline-offset: 0.0625rem; }
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
    font-size: var(--font-size-2xs);
    font-variant-numeric: tabular-nums;
}

.reception__step--done .reception__step-mark { border-color: var(--color-accent); background: var(--color-accent); color: var(--color-surface); }
.reception__step-label { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.reception__step-delta { font-size: var(--font-size-xs); font-variant-numeric: tabular-nums; }
.reception__step-delta--more { color: var(--color-warning); }
.reception__step-delta--less { color: var(--color-danger); }

.reception__panel { display: flex; flex-direction: column; gap: var(--space-4); padding: var(--space-5); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); background: var(--color-surface); }
.reception__progress { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }
.reception__label { margin: calc(-1 * var(--space-3)) 0 0; font-family: var(--font-display); font-size: 1.75rem; font-weight: 400; line-height: 1.2; }

.reception__figures { display: flex; gap: var(--space-6); margin: 0; }
.reception__figures dt { color: var(--color-muted); font-size: var(--font-size-sm); }
.reception__figures dd { margin: 0; font-size: 1.35rem; font-variant-numeric: tabular-nums; }
.reception__planned { display: block; color: var(--color-muted); font-size: var(--font-size-sm); }

.reception__count { display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap; padding: var(--space-4); border-radius: var(--radius); background: var(--color-bg); }
.reception__or { padding-bottom: var(--space-2); color: var(--color-muted); }
.reception__received { display: flex; flex-direction: column; gap: var(--space-1); width: 9rem; font-size: var(--font-size-md); }
.reception__verdict { margin-bottom: var(--space-2); }

.reception__hint { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }
.reception__muted { color: var(--color-subtle); }


@media (max-width: 48rem) {
    .reception { grid-template-columns: 1fr; }
}
</style>
