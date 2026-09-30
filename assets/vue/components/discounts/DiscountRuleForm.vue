<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Plus, Trash2 } from '@lucide/vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
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
const { t } = useI18n();

const ACTIONS = [
    { value: 'fixedPrice', label: 'discounts.form.fixedPrice' },
    { value: 'amountOff', label: 'discounts.form.amountOff' },
    { value: 'percentOff', label: 'discounts.form.percentOff' },
];

const emptyCondition = () => ({ kind: 'type', id: '', variant: '', quantity: 1 });
const emptyForm = () => ({ name: '', conditions: [emptyCondition()], actionKind: 'fixedPrice', amount: null, percent: null, startsOn: '', endsOn: '' });
const form = reactive(emptyForm());
const errors = ref({});
const saving = ref(false);
const isEditing = computed(() => props.rule !== null);

watch(() => props.rule, (rule) => {
    Object.assign(form, rule
        ? {
            name: rule.name,
            conditions: rule.conditions.map(({ kind, id, variant, quantity }) => ({ kind, id, variant: variant ?? '', quantity })),
            actionKind: rule.action.kind,
            amount: rule.action.kind === 'percentOff' ? null : rule.action.value,
            percent: rule.action.kind === 'percentOff' ? rule.action.value / 100 : null,
            startsOn: rule.startsOn ?? '',
            endsOn: rule.endsOn ?? '',
        }
        : emptyForm());
    errors.value = {};
}, { immediate: true });

const productOptions = computed(() => props.products.map((product) => ({ value: product.id, label: product.displayName })));
const typeOptions = computed(() => props.types.map((type) => ({ value: type.id, label: type.name })));
const optionsFor = (condition) => (condition.kind === 'type' ? typeOptions.value : productOptions.value);
const targetOf = (condition) => (condition.kind === 'type' ? props.types : props.products).find((target) => target.id === condition.id);
const variantsOf = (condition) => targetOf(condition)?.variants ?? [];
const variantOptions = (condition) => [
    { value: '', label: t('discounts.form.allVariants') },
    ...variantsOf(condition).map((variant) => ({ value: variant, label: variant })),
];

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

const range = ({ min, max }) => (min === max ? formatCents(min) : t('discounts.range', { min: formatCents(min), max: formatCents(max) }));

function setKind(condition, kind) {
    if (kind && kind !== condition.kind) {
        condition.kind = kind;
        condition.id = '';
        condition.variant = '';
    }
}

function setTarget(condition, id) {
    if (id !== condition.id) {
        condition.id = id;
        condition.variant = '';
    }
}

const conditionError = (index) => errors.value[`conditions[${index}].id`] ?? errors.value[`conditions[${index}].variant`];

function removeCondition(index) {
    form.conditions.splice(index, 1);
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            name: form.name,
            conditions: form.conditions.map((condition) => ({ kind: condition.kind, id: condition.id, variant: condition.variant || null, quantity: Number(condition.quantity) || 0 })),
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

            <FormField :label="t('discounts.form.name')" :error="errors.name" :hint="t('discounts.form.nameHint')">
                <input v-model="form.name" type="text">
            </FormField>

            <FormField as="group" :label="t('discounts.form.conditions')" :error="errors.conditions" :hint="t('discounts.form.conditionsHint')">
                <ol class="discount-rule-form__conditions">
                    <li v-for="(condition, index) in form.conditions" :key="index" class="discount-rule-form__condition" :data-test="`condition-${index}`">
                        <BaseNumberField v-model="condition.quantity" :min="1" :label="t('discounts.form.conditionQuantity', { number: index + 1 })" />
                        <ToggleGroupRoot
                            :model-value="condition.kind"
                            type="single"
                            class="discount-rule-form__kinds"
                            :aria-label="t('discounts.form.conditionTarget', { number: index + 1 })"
                            @update:model-value="(kind) => setKind(condition, kind)"
                        >
                            <ToggleGroupItem value="type" class="discount-rule-form__kind">{{ t('discounts.form.type') }}</ToggleGroupItem>
                            <ToggleGroupItem value="product" class="discount-rule-form__kind">{{ t('discounts.form.product') }}</ToggleGroupItem>
                        </ToggleGroupRoot>
                        <div class="discount-rule-form__target">
                            <BaseCombobox
                                :model-value="condition.id"
                                :options="optionsFor(condition)"
                                :aria-label="condition.kind === 'type' ? t('discounts.form.conditionType', { number: index + 1 }) : t('discounts.form.conditionProduct', { number: index + 1 })"
                                :placeholder="condition.kind === 'type' ? t('discounts.form.chooseType') : t('discounts.form.searchProduct')"
                                @update:model-value="(id) => setTarget(condition, id ?? '')"
                            />
                        </div>
                        <BaseSelect
                            v-if="variantsOf(condition).length"
                            v-model="condition.variant"
                            class="discount-rule-form__variant"
                            :options="variantOptions(condition)"
                            :aria-label="t('discounts.form.conditionVariant', { number: index + 1 })"
                        />
                        <IconButton
                            class="discount-rule-form__remove"
                            :icon="Trash2"
                            :label="t('discounts.form.removeCondition', { number: index + 1 })"
                            :disabled="form.conditions.length === 1"
                            @click="removeCondition(index)"
                        />
                        <span v-if="conditionError(index)" class="discount-rule-form__condition-error" role="alert">{{ conditionError(index) }}</span>
                    </li>
                </ol>
                <BaseButton variant="ghost" class="discount-rule-form__add" @click="form.conditions.push(emptyCondition())">
                    <Plus size="1rem" aria-hidden="true" /> {{ t('discounts.form.addCondition') }}
                </BaseButton>
            </FormField>

            <FormField as="group" :label="t('discounts.form.action')" :error="errors['action.value'] ?? errors['action.kind']">
                <div class="discount-rule-form__action">
                    <ToggleGroupRoot
                        :model-value="form.actionKind"
                        type="single"
                        class="discount-rule-form__kinds"
                        :aria-label="t('discounts.form.actionKind')"
                        @update:model-value="(kind) => kind && (form.actionKind = kind)"
                    >
                        <ToggleGroupItem v-for="option in ACTIONS" :key="option.value" :value="option.value" class="discount-rule-form__kind">{{ t(option.label) }}</ToggleGroupItem>
                    </ToggleGroupRoot>
                    <BaseNumberField
                        v-if="form.actionKind === 'percentOff'"
                        v-model="form.percent"
                        :min="0"
                        :max="100"
                        :step="0.5"
                        :label="t('discounts.form.percent')"
                    />
                    <BaseMoneyField v-else v-model="form.amount" :aria-label="form.actionKind === 'fixedPrice' ? t('discounts.form.itemsPrice') : t('discounts.form.discountAmount')" />
                </div>
            </FormField>

            <p v-if="pricing" class="discount-rule-form__hint" data-test="discount-rule-pricing">
                <i18n-t keypath="discounts.form.pricing" scope="global">
                    <template #regular><strong>{{ range(pricing.regular) }}</strong></template>
                    <template #customer><strong>{{ range(pricing.customer) }}</strong></template>
                </i18n-t>
            </p>

            <div class="discount-rule-form__row">
                <FormField as="group" :label="t('discounts.form.startsOn')" :error="errors.startsOn" :hint="t('discounts.form.startsOnHint')">
                    <BaseDatePicker v-model="form.startsOn" :aria-label="t('discounts.form.validFrom')" />
                </FormField>
                <FormField as="group" :label="t('discounts.form.endsOn')" :error="errors.endsOn" :hint="t('discounts.form.endsOnHint')">
                    <BaseDatePicker v-model="form.endsOn" :aria-label="t('discounts.form.validUntil')" />
                </FormField>
            </div>

            <div class="discount-rule-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('discounts.form.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ isEditing ? t('discounts.form.save') : t('discounts.form.create') }}</BaseButton>
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
.discount-rule-form__condition :deep(.discount-rule-form__remove) { grid-column: 4; grid-row: 1; }
.discount-rule-form__condition :deep(.discount-rule-form__variant) { grid-column: 3; grid-row: 2; }
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
    .discount-rule-form__condition :deep(.discount-rule-form__remove) { grid-column: 3; grid-row: 1; }
    .discount-rule-form__target { grid-column: 1 / -1; grid-row: 2; }
    .discount-rule-form__condition :deep(.discount-rule-form__variant) { grid-column: 1 / -1; grid-row: 3; }
    .discount-rule-form__action,
    .discount-rule-form__row { grid-template-columns: 1fr; }
}
</style>
