<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    design: { type: Object, default: null },
    collections: { type: Array, required: true },
    gabarits: { type: Array, default: () => [] },
    collectionId: { type: String, default: '' },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ name: '', collectionId: '', notes: '', gabaritIds: [] });
const errors = ref({});
const saving = ref(false);

const collectionOptions = computed(() => [{ value: '', label: t('designs.noCollection') }, ...props.collections.map((c) => ({ value: c.id, label: c.name }))]);

watch(() => [props.design, props.collectionId], () => {
    Object.assign(form, props.design
        ? { name: props.design.name, collectionId: props.design.collection?.id ?? '', notes: props.design.notes ?? '', gabaritIds: [] }
        : { name: '', collectionId: props.collectionId, notes: '', gabaritIds: [] });
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        const result = await props.submit({ name: form.name, collectionId: form.collectionId || null, notes: form.notes || null, gabaritIds: form.gabaritIds });
        emit('saved', { name: form.name, id: result?.id ?? props.design?.id });
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
    <form class="design-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <FormField :label="t('designs.form.name')" :error="errors.name" :hint="t('designs.form.nameHint')">
                <input v-model="form.name" type="text">
            </FormField>
            <FormField as="group" :label="t('designs.form.collection')" :error="errors.collectionId">
                <BaseSelect v-model="form.collectionId" :options="collectionOptions" :aria-label="t('designs.form.collection')" />
            </FormField>
            <FormField v-if="!design && gabarits.length" as="group" :label="t('designs.form.declineOn')" :hint="t('designs.form.declineOnHint')">
                <ToggleGroupRoot v-model="form.gabaritIds" type="multiple" class="design-form__gabarits" :aria-label="t('designs.form.gabarits')">
                    <ToggleGroupItem v-for="gabarit in gabarits" :key="gabarit.id" :value="gabarit.id" class="design-form__gabarit">{{ gabarit.name }}</ToggleGroupItem>
                </ToggleGroupRoot>
            </FormField>
            <FormField :label="t('designs.form.notes')" :error="errors.notes">
                <textarea v-model="form.notes" rows="3" :placeholder="t('designs.form.notesPlaceholder')" />
            </FormField>
            <div class="design-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('designs.form.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ design ? t('designs.form.save') : t('designs.form.start') }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.design-form { display: flex; flex-direction: column; gap: var(--space-3); }
.design-form__gabarits { display: flex; flex-wrap: wrap; gap: var(--space-2); }

.design-form__gabarit {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.85rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.design-form__gabarit:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.design-form__gabarit[data-state="on"] { background: var(--color-accent); border-color: var(--color-accent); color: var(--color-surface); }
.design-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
