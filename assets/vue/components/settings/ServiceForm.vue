<script setup>
import { computed, reactive, ref } from 'vue';
import { Copy } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import FormActions from '../ui/FormActions.vue';
import FormError from '../ui/FormError.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import IconButton from '../ui/IconButton.vue';
import ServiceOptions from './ServiceOptions.vue';
import { SALES_CONTEXTS, UNKNOWN_ITEMS } from '../../composables/useServices.js';
import { useToast } from '../../composables/useToast.js';

const { t } = useI18n();

const props = defineProps({
    service: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const toast = useToast();
const connection = props.service.connection;
const fields = reactive(Object.fromEntries(props.service.fields.map((field) => [field.name, field.secret ? '' : (connection?.values[field.name]?.value ?? '')])));
const options = reactive({
    salesContext: connection?.salesContext ?? props.service.defaultSalesContext,
    unknownItems: connection?.unknownItems ?? props.service.defaultUnknownItems,
});
const errors = ref({});
const saving = ref(false);

const callbackUrl = computed(() => `${window.location.origin}/settings/${props.service.key}/callback`);

function hint(field) {
    const value = connection?.values[field.name];
    if (field.secret && value?.configured) {
        return t('settings.form.secretKept', { hint: value.hint });
    }
    return field.hint ? t(field.hint) : field.hint;
}

async function copyCallback() {
    await navigator.clipboard.writeText(callbackUrl.value);
    toast.success(t('settings.form.callbackCopied'));
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ fields: { ...fields }, ...options });
        emit('saved');
    } catch (error) {
        const fieldErrors = Object.fromEntries(Object.entries(error.fieldErrors ?? {}).map(([path, message]) => [path.replace(/^\[|\]$/g, ''), message]));
        errors.value = Object.keys(fieldErrors).length ? fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="service-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormError v-if="errors.form">{{ errors.form }}</FormError>

            <FormSection :title="t('settings.form.access')" :description="service.authorizes ? t(service.instructions) : null">
                <FormField v-if="service.authorizes" as="group" :label="t('settings.form.callbackUrl')">
                    <div class="service-form__url">
                        <code>{{ callbackUrl }}</code>
                        <IconButton :icon="Copy" :label="t('settings.form.copyCallback')" @click="copyCallback" />
                    </div>
                </FormField>
                <FormField v-for="field in service.fields" :key="field.name" :label="t(field.label)" :error="errors[field.name]" :hint="hint(field)">
                    <input
                        v-model="fields[field.name]"
                        :type="field.secret ? 'password' : 'text'"
                        :maxlength="field.maxLength"
                        :placeholder="field.secret ? (connection?.values[field.name]?.hint ?? '') : ''"
                        autocomplete="off"
                        :required="field.required"
                    >
                </FormField>
            </FormSection>

            <FormSection :title="t('settings.form.import')">
                <FormField as="group" :label="t('settings.form.salesContext')">
                    <ServiceOptions v-model="options.salesContext" :label="t('settings.form.salesContext')" :options="SALES_CONTEXTS" />
                </FormField>
                <FormField as="group" :label="t('settings.form.unknownItems')">
                    <ServiceOptions v-model="options.unknownItems" :label="t('settings.form.unknownItems')" :options="UNKNOWN_ITEMS" />
                </FormField>
            </FormSection>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('settings.form.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ connection ? t('settings.form.save') : t('settings.form.add', { label: service.label }) }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.service-form { display: flex; flex-direction: column; gap: var(--space-5); }
.service-form__url { display: flex; align-items: center; gap: var(--space-2); }

.service-form__url code {
    flex: 1;
    min-width: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-bg);
    color: var(--color-ink);
    font-size: 0.8125rem;
    overflow-wrap: anywhere;
    user-select: all;
}
</style>
