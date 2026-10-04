<script setup>
import { computed, reactive } from 'vue';
import { ToggleGroupItem } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import ChoiceGroup from '../ui/ChoiceGroup.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import { COST_KINDS } from '../../composables/useChannelCosts.js';
import { useFormSubmit } from '../../composables/useFormSubmit.js';

const props = defineProps({
    cost: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({
    label: props.cost?.label ?? '',
    kind: props.cost?.kind ?? COST_KINDS.percent,
    cents: props.cost?.kind === COST_KINDS.fixed ? props.cost.amount : null,
    percent: props.cost?.kind === COST_KINDS.percent ? props.cost.amount / 100 : null,
});
const { saving, errors, run } = useFormSubmit();
const percentFormat = { style: 'unit', unit: 'percent', maximumFractionDigits: 2 };
const amount = computed(() => (form.kind === COST_KINDS.percent
    ? (Number.isFinite(form.percent) ? Math.round(form.percent * 100) : -1)
    : form.cents ?? -1));

async function onSubmit() {
    await run(async () => {
        await props.submit({ label: form.label, kind: form.kind, amount: amount.value });
        emit('saved', form.label.trim());
    });
}
</script>

<template>
    <form class="channel-cost-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>
            <FormSection>
                <FormField :label="t('channels.costs.label')" :error="errors.label">
                    <input v-model="form.label" type="text" required maxlength="100" :placeholder="t('channels.costs.labelPlaceholder')">
                </FormField>
                <FormField as="group" :label="t('channels.costs.kind')" :error="errors.amount">
                    <div class="channel-cost-form__amount">
                        <BaseNumberField
                            v-if="form.kind === COST_KINDS.percent"
                            v-model="form.percent"
                            :min="0"
                            :step="0.1"
                            :format-options="percentFormat"
                            :label="t('channels.costs.amount')"
                            class="channel-cost-form__field"
                        />
                        <BaseMoneyField v-else v-model="form.cents" :aria-label="t('channels.costs.amount')" class="channel-cost-form__field" />
                        <ChoiceGroup v-model="form.kind" class="segmented" :aria-label="t('channels.costs.kind')">
                            <ToggleGroupItem v-for="kind in Object.values(COST_KINDS)" :key="kind" :value="kind" class="segmented__item">{{ t(`channels.costs.kinds.${kind}`) }}</ToggleGroupItem>
                        </ChoiceGroup>
                    </div>
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('common.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t(cost ? 'common.save' : 'channels.costs.add') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.channel-cost-form { display: flex; flex-direction: column; gap: var(--space-5); }
.channel-cost-form__amount { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-2); }
.channel-cost-form__field { max-width: 9rem; }


</style>
