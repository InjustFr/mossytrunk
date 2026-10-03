<script setup>
import { computed, ref } from 'vue';
import { Plus, Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import FormActions from '../ui/FormActions.vue';
import IconButton from '../ui/IconButton.vue';
import ServiceOptions from './ServiceOptions.vue';
import { SEPARATIONS } from '../../composables/useNotebookTemplate.js';

const props = defineProps({
    template: { type: Object, required: true },
    save: { type: Function, required: true },
});
const emit = defineEmits(['saved']);
const { t } = useI18n();

const separation = ref(props.template.separation);
const abbreviations = ref(props.template.abbreviations.map((abbreviation) => ({ ...abbreviation })));
const saving = ref(false);
const error = ref(null);

const options = computed(() => SEPARATIONS.map((value) => ({
    value,
    label: `notebook.template.separation.${value}.label`,
    description: `notebook.template.separation.${value}.description`,
})));

const addAbbreviation = () => {
    abbreviations.value = [...abbreviations.value, { short: '', full: '' }];
};
const removeAbbreviation = (index) => {
    abbreviations.value = abbreviations.value.filter((_, position) => position !== index);
};

async function onSubmit() {
    saving.value = true;
    error.value = null;
    try {
        await props.save({
            separation: separation.value,
            abbreviations: abbreviations.value.filter((abbreviation) => abbreviation.short.trim() !== '' || abbreviation.full.trim() !== ''),
        });
        emit('saved');
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="notebook-template-form" @submit.prevent="onSubmit">
        <ServiceOptions v-model="separation" :label="t('notebook.template.separationLabel')" :options="options" />

        <fieldset class="notebook-template-form__abbreviations">
            <legend class="notebook-template-form__legend">{{ t('notebook.template.abbreviations') }}</legend>
            <p class="notebook-template-form__hint">{{ t('notebook.template.abbreviationsHint') }}</p>
            <ul v-if="abbreviations.length" class="notebook-template-form__list">
                <li v-for="(abbreviation, index) in abbreviations" :key="index" class="notebook-template-form__row">
                    <input v-model="abbreviation.short" type="text" maxlength="50" class="notebook-template-form__input notebook-template-form__input--short" :aria-label="t('notebook.template.short', { number: index + 1 })" :placeholder="t('notebook.template.shortPlaceholder')">
                    <span aria-hidden="true">→</span>
                    <input v-model="abbreviation.full" type="text" maxlength="100" class="notebook-template-form__input" :aria-label="t('notebook.template.full', { number: index + 1 })" :placeholder="t('notebook.template.fullPlaceholder')">
                    <IconButton :icon="Trash2" variant="danger" :label="t('notebook.template.remove', { number: index + 1 })" @click="removeAbbreviation(index)" />
                </li>
            </ul>
            <BaseButton variant="ghost" @click="addAbbreviation"><Plus size="1rem" aria-hidden="true" /> {{ t('notebook.template.add') }}</BaseButton>
        </fieldset>

        <p v-if="error" class="notebook-template-form__error" role="alert">{{ error }}</p>
        <FormActions>
            <BaseButton type="submit" :loading="saving">{{ t('notebook.template.save') }}</BaseButton>
        </FormActions>
    </form>
</template>

<style scoped>
.notebook-template-form { display: flex; flex-direction: column; gap: var(--space-4); }
.notebook-template-form__abbreviations { margin: 0; padding: 0; border: none; }
.notebook-template-form__legend { padding: 0; color: var(--color-ink); font-weight: 600; }
.notebook-template-form__hint { margin: var(--space-1) 0 var(--space-3); color: var(--color-muted); font-size: 0.85rem; }
.notebook-template-form__list { display: flex; flex-direction: column; gap: var(--space-2); margin: 0 0 var(--space-2); padding: 0; list-style: none; }
.notebook-template-form__row { display: flex; align-items: center; gap: var(--space-2); }
.notebook-template-form__input { flex: 1; min-width: 0; min-height: 2.125rem; padding: var(--space-1) var(--space-3); border: 0.0625rem solid var(--color-border-strong); border-radius: var(--radius); font: inherit; }
.notebook-template-form__input--short { flex: 0 0 8rem; }
.notebook-template-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
</style>
