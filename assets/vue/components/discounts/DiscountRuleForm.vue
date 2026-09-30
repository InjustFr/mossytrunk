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
import FormActions from '../ui/FormActions.vue';
import FormDisclosure from '../ui/FormDisclosure.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import IconButton from '../ui/IconButton.vue';
import { regularPrice, savingOn } from '../../composables/useRulePrice.js';
import { formatCents } from '../../composables/useMoney.js';
import { formatDate } from '../../composables/useDate.js';

const props = defineProps({
    rule: { type: Object, default: null },
    products: { type: Array, required: true },
    types: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const ACTIONS = [
    { value: 'fixedPrice', label: 'discounts.form.fixedPrice', valueLabel: 'discounts.form.itemsPrice' },
    { value: 'amountOff', label: 'discounts.form.amountOff', valueLabel: 'discounts.form.discountAmount' },
    { value: 'percentOff', label: 'discounts.form.percentOff', valueLabel: 'discounts.form.percent' },
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

const offered = (targets, condition) => targets.filter((target) => !target.archived || target.id === condition.id);
const productOptions = (condition) => offered(props.products, condition).map((product) => ({ value: product.id, label: product.displayName }));
const typeOptions = (condition) => offered(props.types, condition).map((type) => ({ value: type.id, label: type.name }));
const optionsFor = (condition) => (condition.kind === 'type' ? typeOptions(condition) : productOptions(condition));
const targetOf = (condition) => (condition.kind === 'type' ? props.types : props.products).find((target) => target.id === condition.id);
const archivedVariantsOf = (condition) => (condition.kind === 'type'
    ? targetOf(condition)?.archivedVariants ?? []
    : (targetOf(condition)?.variants ?? []).filter((variant) => !targetOf(condition).activeVariants.includes(variant)));
const variantsOf = (condition) => (targetOf(condition)?.variants ?? [])
    .filter((variant) => variant === condition.variant || !archivedVariantsOf(condition).includes(variant));
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

const valueLabel = computed(() => t(ACTIONS.find((option) => option.value === form.actionKind).valueLabel));

const periodOpen = ref(false);
const periodSummary = computed(() => {
    if (form.startsOn && form.endsOn) {
        return t('discounts.list.between', { start: formatDate(form.startsOn), end: formatDate(form.endsOn) });
    }
    if (form.startsOn) {
        return t('discounts.list.from', { start: formatDate(form.startsOn) });
    }
    return form.endsOn ? t('discounts.list.until', { end: formatDate(form.endsOn) }) : t('discounts.form.always');
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

            <FormSection>
                <FormField :label="t('discounts.form.name')" :error="errors.name">
                    <input v-model="form.name" type="text" :placeholder="t('discounts.form.namePlaceholder')">
                </FormField>
            </FormSection>

            <FormSection :title="t('discounts.form.conditions')" :description="t('discounts.form.conditionsHint')">
                <ol class="discount-rule-form__conditions">
                    <li v-for="(condition, index) in form.conditions" :key="index" class="discount-rule-form__condition" :data-test="`condition-${index}`">
                        <BaseNumberField v-model="condition.quantity" class="discount-rule-form__quantity" :min="1" :label="t('discounts.form.conditionQuantity', { number: index + 1 })" />
                        <span class="discount-rule-form__times" aria-hidden="true">×</span>
                        <ToggleGroupRoot
                            :model-value="condition.kind"
                            type="single"
                            class="discount-rule-form__kinds discount-rule-form__condition-kinds"
                            :aria-label="t('discounts.form.conditionTarget', { number: index + 1 })"
                            @update:model-value="(kind) => setKind(condition, kind)"
                        >
                            <ToggleGroupItem value="type" class="discount-rule-form__kind">{{ t('discounts.form.type') }}</ToggleGroupItem>
                            <ToggleGroupItem value="product" class="discount-rule-form__kind">{{ t('discounts.form.product') }}</ToggleGroupItem>
                        </ToggleGroupRoot>
                        <IconButton
                            class="discount-rule-form__remove"
                            :icon="Trash2"
                            :label="t('discounts.form.removeCondition', { number: index + 1 })"
                            :disabled="form.conditions.length === 1"
                            @click="removeCondition(index)"
                        />
                        <div :class="['discount-rule-form__target', { 'discount-rule-form__target--with-variant': variantsOf(condition).length }]">
                            <BaseCombobox
                                :model-value="condition.id"
                                :options="optionsFor(condition)"
                                :aria-label="condition.kind === 'type' ? t('discounts.form.conditionType', { number: index + 1 }) : t('discounts.form.conditionProduct', { number: index + 1 })"
                                :placeholder="condition.kind === 'type' ? t('discounts.form.chooseType') : t('discounts.form.searchProduct')"
                                @update:model-value="(id) => setTarget(condition, id ?? '')"
                            />
                            <BaseSelect
                                v-if="variantsOf(condition).length"
                                v-model="condition.variant"
                                :options="variantOptions(condition)"
                                :aria-label="t('discounts.form.conditionVariant', { number: index + 1 })"
                            />
                        </div>
                        <span v-if="conditionError(index)" class="discount-rule-form__condition-error" role="alert">{{ conditionError(index) }}</span>
                    </li>
                </ol>
                <p v-if="errors.conditions" class="discount-rule-form__condition-error" role="alert">{{ errors.conditions }}</p>
                <BaseButton variant="ghost" class="discount-rule-form__add" @click="form.conditions.push(emptyCondition())">
                    <Plus size="1rem" aria-hidden="true" /> {{ t('discounts.form.addCondition') }}
                </BaseButton>
            </FormSection>

            <FormSection :title="t('discounts.form.action')">
                <ToggleGroupRoot
                    :model-value="form.actionKind"
                    type="single"
                    class="discount-rule-form__kinds"
                    :aria-label="t('discounts.form.actionKind')"
                    @update:model-value="(kind) => kind && (form.actionKind = kind)"
                >
                    <ToggleGroupItem v-for="option in ACTIONS" :key="option.value" :value="option.value" class="discount-rule-form__kind">{{ t(option.label) }}</ToggleGroupItem>
                </ToggleGroupRoot>
                <FormField v-if="form.actionKind === 'percentOff'" as="group" :label="valueLabel" :error="errors['action.value'] ?? errors['action.kind']">
                    <span class="discount-rule-form__value">
                        <BaseNumberField v-model="form.percent" :min="0" :max="100" :step="0.5" :label="valueLabel" />
                        <span class="discount-rule-form__unit" aria-hidden="true">%</span>
                    </span>
                </FormField>
                <FormField v-else :label="valueLabel" :error="errors['action.value'] ?? errors['action.kind']">
                    <BaseMoneyField v-model="form.amount" class="discount-rule-form__value" />
                </FormField>
                <FormField v-if="pricing" as="group" :label="t('discounts.form.customerPrice')">
                    <p class="discount-rule-form__fact" data-test="discount-rule-pricing">
                        <i18n-t keypath="discounts.form.pricing" scope="global">
                            <template #customer><strong>{{ range(pricing.customer) }}</strong></template>
                            <template #regular>{{ range(pricing.regular) }}</template>
                        </i18n-t>
                    </p>
                </FormField>
            </FormSection>

            <FormDisclosure v-model:open="periodOpen" :title="t('discounts.form.period')" :summary="periodSummary">
                <FormField as="group" optional :label="t('discounts.form.startsOn')" :error="errors.startsOn">
                    <BaseDatePicker v-model="form.startsOn" :aria-label="t('discounts.form.validFrom')" />
                </FormField>
                <FormField as="group" optional :label="t('discounts.form.endsOn')" :error="errors.endsOn">
                    <BaseDatePicker v-model="form.endsOn" :aria-label="t('discounts.form.validUntil')" />
                </FormField>
            </FormDisclosure>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('discounts.form.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ isEditing ? t('discounts.form.save') : t('discounts.form.create') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.discount-rule-form { display: flex; flex-direction: column; gap: var(--space-5); }

.discount-rule-form__conditions { display: flex; flex-direction: column; gap: var(--space-4); margin: 0; padding: 0; list-style: none; }
.discount-rule-form__condition {
    display: grid;
    grid-template-columns: 6.5rem auto minmax(0, 1fr) auto;
    grid-template-areas:
        "quantity times kinds remove"
        ". . target target";
    align-items: center;
    gap: var(--space-2);
}
.discount-rule-form__quantity { grid-area: quantity; }
.discount-rule-form__times { grid-area: times; color: var(--color-muted); }
.discount-rule-form__condition-kinds { grid-area: kinds; }
.discount-rule-form__condition :deep(.discount-rule-form__remove) { grid-area: remove; }
.discount-rule-form__target { grid-area: target; display: grid; grid-template-columns: minmax(0, 1fr); gap: var(--space-2); }
.discount-rule-form__target--with-variant { grid-template-columns: minmax(0, 1fr) auto; }
.discount-rule-form__condition-error { grid-column: 1 / -1; margin: 0; color: var(--color-danger); font-size: 0.85rem; }
.discount-rule-form__add { align-self: flex-start; }

.discount-rule-form__value { display: flex; align-items: center; gap: var(--space-2); max-width: 11rem; }
.discount-rule-form__unit { color: var(--color-muted); }
.discount-rule-form__fact { margin: 0; padding-top: 0.5625rem; font-size: 0.875rem; color: var(--color-muted); }
.discount-rule-form__fact strong { color: var(--color-ink); font-weight: 600; }

.discount-rule-form__kinds { display: flex; flex-wrap: wrap; gap: var(--space-1); }
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

@container form (max-width: 28rem) {
    .discount-rule-form__condition { grid-template-columns: 6.5rem auto minmax(0, 1fr) auto; }
    .discount-rule-form__target { grid-column: 1 / -1; }
    .discount-rule-form__target--with-variant { grid-template-columns: minmax(0, 1fr); }
}
</style>
