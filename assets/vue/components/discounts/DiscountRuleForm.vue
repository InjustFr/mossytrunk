<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Plus, Trash2 } from '@lucide/vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import FormField from '../ui/FormField.vue';
import IconButton from '../ui/IconButton.vue';
import { regularPrice, savingOn } from '../../composables/useRulePrice.js';
import { formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    rule: { type: Object, default: null },
    products: { type: Array, required: true },
    types: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const ACTIONS = [
    { value: 'fixedPrice', label: 'Prix fixe' },
    { value: 'amountOff', label: 'Remise en €' },
    { value: 'percentOff', label: 'Remise en %' },
];

const emptyCondition = () => ({ kind: 'type', id: '', quantity: 1 });
const emptyForm = () => ({ name: '', conditions: [emptyCondition()], actionKind: 'fixedPrice', amount: null, percent: null, startsOn: '', endsOn: '' });
const form = reactive(emptyForm());
const errors = ref({});
const saving = ref(false);
const isEditing = computed(() => props.rule !== null);

watch(() => props.rule, (rule) => {
    Object.assign(form, rule
        ? {
            name: rule.name,
            conditions: rule.conditions.map(({ kind, id, quantity }) => ({ kind, id, quantity })),
            actionKind: rule.action.kind,
            amount: rule.action.kind === 'percentOff' ? null : rule.action.value,
            percent: rule.action.kind === 'percentOff' ? rule.action.value / 100 : null,
            startsOn: rule.startsOn ?? '',
            endsOn: rule.endsOn ?? '',
        }
        : emptyForm());
    errors.value = {};
}, { immediate: true });

const productOptions = computed(() => props.products.map((product) => ({ value: product.id, label: product.name })));
const typeOptions = computed(() => props.types.map((type) => ({ value: type.id, label: type.name })));
const optionsFor = (condition) => (condition.kind === 'type' ? typeOptions.value : productOptions.value);

const action = computed(() => ({
    kind: form.actionKind,
    value: form.actionKind === 'percentOff' ? Math.round((form.percent ?? 0) * 100) : (form.amount ?? 0),
}));

const pricing = computed(() => {
    const regular = regularPrice(props.products, form.conditions);
    if (!regular) {
        return null;
    }
    return {
        regular,
        customer: {
            min: regular.min - savingOn(regular.min, action.value),
            max: regular.max - savingOn(regular.max, action.value),
        },
    };
});

const range = ({ min, max }) => (min === max ? formatCents(min) : `${formatCents(min)} à ${formatCents(max)}`);

function setKind(condition, kind) {
    if (kind && kind !== condition.kind) {
        condition.kind = kind;
        condition.id = '';
    }
}

function removeCondition(index) {
    form.conditions.splice(index, 1);
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            name: form.name,
            conditions: form.conditions.map((condition) => ({ kind: condition.kind, id: condition.id, quantity: Number(condition.quantity) || 0 })),
            action: action.value,
            startsOn: form.startsOn || null,
            endsOn: form.endsOn || null,
        });
        emit('saved', form.name);
        if (!isEditing.value) {
            Object.assign(form, emptyForm());
        }
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="discount-rule-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="discount-rule-form__error" role="alert">{{ errors.form }}</p>

            <FormField label="Nom" :error="errors.name" hint="Affiché sur les commandes, ex. « 2 prints et 1 sticker pour 15 € »">
                <input v-model="form.name" type="text">
            </FormField>

            <FormField as="group" label="Conditions" :error="errors.conditions" hint="Toutes les conditions doivent être réunies. Un produit compte avec toutes ses variantes.">
                <ol class="discount-rule-form__conditions">
                    <li v-for="(condition, index) in form.conditions" :key="index" class="discount-rule-form__condition" :data-test="`condition-${index}`">
                        <BaseNumberField v-model="condition.quantity" :min="1" :label="`Quantité de la condition ${index + 1}`" />
                        <ToggleGroupRoot
                            :model-value="condition.kind"
                            type="single"
                            class="discount-rule-form__kinds"
                            :aria-label="`Cible de la condition ${index + 1}`"
                            @update:model-value="(kind) => setKind(condition, kind)"
                        >
                            <ToggleGroupItem value="type" class="discount-rule-form__kind">Type</ToggleGroupItem>
                            <ToggleGroupItem value="product" class="discount-rule-form__kind">Produit</ToggleGroupItem>
                        </ToggleGroupRoot>
                        <BaseCombobox
                            v-model="condition.id"
                            :options="optionsFor(condition)"
                            :aria-label="condition.kind === 'type' ? `Type de la condition ${index + 1}` : `Produit de la condition ${index + 1}`"
                            :placeholder="condition.kind === 'type' ? 'Choisir un type…' : 'Rechercher un produit…'"
                        />
                        <IconButton
                            :icon="Trash2"
                            :label="`Retirer la condition ${index + 1}`"
                            :disabled="form.conditions.length === 1"
                            @click="removeCondition(index)"
                        />
                        <span v-if="errors[`conditions[${index}].id`]" class="discount-rule-form__condition-error" role="alert">{{ errors[`conditions[${index}].id`] }}</span>
                    </li>
                </ol>
                <BaseButton variant="ghost" class="discount-rule-form__add" @click="form.conditions.push(emptyCondition())">
                    <Plus size="1rem" aria-hidden="true" /> Ajouter une condition
                </BaseButton>
            </FormField>

            <FormField as="group" label="Action" :error="errors['action.value'] ?? errors['action.kind']">
                <div class="discount-rule-form__action">
                    <ToggleGroupRoot
                        :model-value="form.actionKind"
                        type="single"
                        class="discount-rule-form__kinds"
                        aria-label="Type d'action"
                        @update:model-value="(kind) => kind && (form.actionKind = kind)"
                    >
                        <ToggleGroupItem v-for="option in ACTIONS" :key="option.value" :value="option.value" class="discount-rule-form__kind">{{ option.label }}</ToggleGroupItem>
                    </ToggleGroupRoot>
                    <BaseNumberField
                        v-if="form.actionKind === 'percentOff'"
                        v-model="form.percent"
                        :min="0"
                        :max="100"
                        :step="0.5"
                        label="Pourcentage de remise"
                    />
                    <BaseMoneyField v-else v-model="form.amount" :aria-label="form.actionKind === 'fixedPrice' ? 'Prix des articles' : 'Montant de la remise'" />
                </div>
            </FormField>

            <p v-if="pricing" class="discount-rule-form__hint" data-test="discount-rule-pricing">
                Prix normal : <strong>{{ range(pricing.regular) }}</strong>
                → prix client : <strong>{{ range(pricing.customer) }}</strong>
            </p>

            <div class="discount-rule-form__row">
                <FormField as="group" label="Valable du" :error="errors.startsOn" hint="Vide : dès maintenant">
                    <BaseDatePicker v-model="form.startsOn" aria-label="Début de validité" />
                </FormField>
                <FormField as="group" label="Au" :error="errors.endsOn" hint="Vide : sans fin">
                    <BaseDatePicker v-model="form.endsOn" aria-label="Fin de validité" />
                </FormField>
            </div>

            <div class="discount-rule-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ isEditing ? 'Enregistrer' : 'Créer la remise' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.discount-rule-form { display: flex; flex-direction: column; gap: var(--space-3); }
.discount-rule-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.discount-rule-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.discount-rule-form__hint { margin: 0; color: var(--color-muted); font-size: 0.9rem; }

.discount-rule-form__conditions { display: flex; flex-direction: column; gap: var(--space-2); margin: 0; padding: 0; list-style: none; }
.discount-rule-form__condition {
    display: grid;
    grid-template-columns: 7rem auto minmax(0, 1fr) auto;
    align-items: center;
    gap: var(--space-2);
}
.discount-rule-form__condition-error { grid-column: 1 / -1; color: var(--color-danger); font-size: 0.85rem; }
.discount-rule-form__add { align-self: flex-start; }

.discount-rule-form__action { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; gap: var(--space-2); }

.discount-rule-form__kinds { display: flex; gap: var(--space-1); }
.discount-rule-form__kind {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    color: var(--color-text);
    font-size: 0.85rem;
    cursor: pointer;
    white-space: nowrap;
    transition: background var(--transition), border-color var(--transition), color var(--transition);
}
.discount-rule-form__kind:hover { border-color: var(--color-ink); }
.discount-rule-form__kind[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }

.discount-rule-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}

@media (max-width: 43.75rem) {
    .discount-rule-form__condition { grid-template-columns: 6rem minmax(0, 1fr) auto; }
    .discount-rule-form__condition > :nth-child(3) { grid-column: 1 / -1; grid-row: 2; }
    .discount-rule-form__action,
    .discount-rule-form__row { grid-template-columns: 1fr; }
}
</style>
