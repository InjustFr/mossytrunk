<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import ServiceOptions from '../settings/ServiceOptions.vue';

const props = defineProps({
    channel: { type: Object, default: null },
    services: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const NONE = '__none__';
const KINDS = ['market', 'online'].map((kind) => ({ value: kind, label: `channels.kinds.${kind}`, description: `channels.kindHints.${kind}` }));
const form = reactive({ name: props.channel?.name ?? '', kind: props.channel?.kind ?? 'online', service: props.channel?.service ?? NONE });
const errors = ref({});
const saving = ref(false);

const serviceOptions = computed(() => [
    { value: NONE, label: t('channels.noService') },
    ...props.services.map((service) => ({ value: service.key, label: service.label })),
]);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ name: form.name, kind: form.kind, service: form.service === NONE ? null : form.service });
        emit('saved', form.name.trim());
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="sales-channel-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="sales-channel-form__error" role="alert">{{ errors.form }}</p>
            <FormSection>
                <FormField :label="t('channels.name')" :error="errors.name">
                    <input v-model="form.name" type="text" required maxlength="100" :placeholder="t('channels.namePlaceholder')">
                </FormField>
                <FormField as="group" :label="t('channels.kind')" :error="errors.kind">
                    <ServiceOptions v-model="form.kind" :options="KINDS" :label="t('channels.kind')" />
                </FormField>
                <FormField as="group" :label="t('channels.service')" :error="errors.service" :hint="t('channels.serviceHint')">
                    <BaseSelect v-model="form.service" :options="serviceOptions" :aria-label="t('channels.service')" />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('channels.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t(channel ? 'channels.save' : 'channels.create') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.sales-channel-form { display: flex; flex-direction: column; gap: var(--space-5); }
.sales-channel-form__error { margin: 0; padding: var(--space-2) var(--space-3); background: var(--color-danger-soft); color: var(--color-danger); border-radius: var(--radius); }
</style>
