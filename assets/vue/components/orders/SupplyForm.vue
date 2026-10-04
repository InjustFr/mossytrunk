<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';

const props = defineProps({
    supplies: { type: Array, required: true },
    intro: { type: String, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ supplyId: props.supplies[0]?.id ?? '', variant: null, quantity: 1 });
const errors = ref({});
const saving = ref(false);

const supplyOptions = computed(() => props.supplies.map((supply) => ({ value: supply.id, label: supply.name })));
const variants = computed(() => props.supplies.find((supply) => supply.id === form.supplyId)?.variants ?? []);
const variantOptions = computed(() => variants.value.map((variant) => ({ value: variant, label: variant })));

watch(variants, (list) => {
    form.variant = list[0] ?? null;
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        const result = await props.submit({ supplyId: form.supplyId, variant: form.variant, quantity: form.quantity ?? 0 });
        emit('saved', result?.updated ?? 1);
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="supply-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>
            <FormSection :description="intro">
                <FormField as="group" :label="t('orders.supplies.supply')" :error="errors.supplyId">
                    <BaseSelect v-model="form.supplyId" :options="supplyOptions" :aria-label="t('orders.supplies.supply')" class="supply-form__select" />
                </FormField>
                <FormField v-if="variants.length" as="group" :label="t('orders.supplies.variant')" :error="errors.variant">
                    <BaseSelect v-model="form.variant" :options="variantOptions" :aria-label="t('orders.supplies.variant')" class="supply-form__select" />
                </FormField>
                <FormField as="group" :label="t('orders.supplies.quantity')" :error="errors.quantity">
                    <BaseNumberField v-model="form.quantity" :min="1" :label="t('orders.supplies.quantity')" class="supply-form__quantity" />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('orders.supplies.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('orders.supplies.submit') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.supply-form { display: flex; flex-direction: column; gap: var(--space-5); }
.supply-form__select { max-width: 18rem; }
.supply-form__quantity { max-width: 8rem; }
</style>
