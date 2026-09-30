<script setup>
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';

const props = defineProps({
    collection: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ name: '', description: '' });
const errors = ref({});
const saving = ref(false);

watch(() => props.collection, (collection) => {
    Object.assign(form, { name: collection?.name ?? '', description: collection?.description ?? '' });
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ name: form.name, description: form.description || null });
        emit('saved', form.name);
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { name: error.message };
        }
    } finally {
        saving.value = false;
    }
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
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('designs.collectionForm.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ collection ? t('designs.collectionForm.save') : t('designs.collectionForm.create') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.collection-form { display: flex; flex-direction: column; gap: var(--space-5); }
</style>
