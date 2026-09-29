<script setup>
import { reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    collection: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

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
            <FormField label="Nom de la collection" :error="errors.name">
                <input v-model="form.name" type="text">
            </FormField>
            <FormField label="Description" :error="errors.description">
                <textarea v-model="form.description" rows="3" />
            </FormField>
            <div class="collection-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ collection ? 'Enregistrer' : 'Créer la collection' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.collection-form { display: flex; flex-direction: column; gap: var(--space-3); }
.collection-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
