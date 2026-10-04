<script setup>
import { reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import { useFormSubmit } from '../../composables/useFormSubmit.js';

const props = defineProps({
    collection: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ name: props.collection?.name ?? '', description: props.collection?.description ?? '' });
const { saving, errors, run } = useFormSubmit('name');

function onSubmit() {
    return run(async () => {
        await props.submit({ name: form.name, description: form.description || null });
        emit('saved', form.name);
    });
}
</script>

<template>
    <form class="collection-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormSection>
                <FormField :label="t('designs.collectionForm.name')" :error="errors.name">
                    <input v-model="form.name" type="text">
                </FormField>
                <FormField :label="t('designs.collectionForm.description')" :error="errors.description" optional>
                    <textarea v-model="form.description" rows="3" />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('common.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ collection ? t('common.save') : t('designs.collectionForm.create') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.collection-form { display: flex; flex-direction: column; gap: var(--space-5); }
</style>
