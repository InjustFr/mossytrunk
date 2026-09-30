<script setup>
import { reactive, ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import TypeColorPicker from './TypeColorPicker.vue';

const props = defineProps({
    type: { type: Object, default: null },
    defaultColor: { type: String, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const form = reactive({ name: props.type?.name ?? '', color: props.type?.color ?? props.defaultColor });
const errors = ref({});
const saving = ref(false);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ name: form.name, color: form.color });
        emit('saved', form.name.trim());
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { form: error.message };
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="product-type-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="product-type-form__error" role="alert">{{ errors.form }}</p>

            <FormField label="Nom" :error="errors.name" :hint="type ? `Code ${type.code} (fixe)` : 'Ex. Print, Sticker…'">
                <input v-model="form.name" type="text" maxlength="100">
            </FormField>

            <FormField as="group" label="Couleur" :error="errors.color">
                <TypeColorPicker v-model="form.color" />
            </FormField>

            <div class="product-type-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ type ? 'Enregistrer' : 'Créer le type' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-type-form { display: flex; flex-direction: column; gap: var(--space-3); }
.product-type-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
.product-type-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
