<script setup>
import { computed, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import { useFormSubmit } from '../../composables/useFormSubmit.js';

const props = defineProps({
    design: { type: Object, default: null },
    collections: { type: Array, required: true },
    gabarits: { type: Array, default: () => [] },
    collectionId: { type: String, default: '' },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive(props.design
    ? { name: props.design.name, collectionId: props.design.collection?.id ?? '', notes: props.design.notes ?? '', gabaritIds: [] }
    : { name: '', collectionId: props.collectionId, notes: '', gabaritIds: [] });
const { saving, errors, run } = useFormSubmit('name');

const collectionOptions = computed(() => [{ value: '', label: t('designs.noCollection') }, ...props.collections.map((c) => ({ value: c.id, label: c.name }))]);

function onSubmit() {
    return run(async () => {
        const result = await props.submit({ name: form.name, collectionId: form.collectionId || null, notes: form.notes || null, gabaritIds: form.gabaritIds });
        emit('saved', { name: form.name, id: result?.id ?? props.design?.id });
    });
}
</script>

<template>
    <form class="design-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormSection>
                <FormField :label="t('designs.form.name')" :error="errors.name" :hint="t('designs.form.nameHint')">
                    <input v-model="form.name" type="text">
                </FormField>
                <FormField as="group" :label="t('designs.form.collection')" :error="errors.collectionId" optional>
                    <BaseSelect v-model="form.collectionId" :options="collectionOptions" :aria-label="t('designs.form.collection')" />
                </FormField>
                <FormField v-if="!design && gabarits.length" as="group" :label="t('designs.form.declineOn')" optional>
                    <ToggleGroupRoot v-model="form.gabaritIds" type="multiple" class="design-form__gabarits" :aria-label="t('designs.form.gabarits')">
                        <ToggleGroupItem v-for="gabarit in gabarits" :key="gabarit.id" :value="gabarit.id" class="chip chip--accent">{{ gabarit.name }}</ToggleGroupItem>
                    </ToggleGroupRoot>
                </FormField>
                <FormField :label="t('designs.form.notes')" :error="errors.notes" optional>
                    <textarea v-model="form.notes" rows="3" :placeholder="t('designs.form.notesPlaceholder')" />
                </FormField>
            </FormSection>
            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('common.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ design ? t('common.save') : t('designs.form.start') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.design-form { display: flex; flex-direction: column; gap: var(--space-5); }
.design-form__gabarits { display: flex; flex-wrap: wrap; gap: var(--space-2); padding-top: 0.125rem; }
</style>
