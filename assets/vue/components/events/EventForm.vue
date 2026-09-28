<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseDateRangePicker from '../ui/BaseDateRangePicker.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    event: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const emptyForm = () => ({ name: '', location: '', startDate: '', endDate: '' });
const form = reactive(emptyForm());
const errors = ref({});
const saving = ref(false);
const isEditing = computed(() => props.event !== null);

watch(() => props.event, (event) => {
    Object.assign(form, event
        ? { name: event.name, location: event.location, startDate: event.startDate, endDate: event.endDate }
        : emptyForm());
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ ...form });
        emit('saved', form.name);
        if (!isEditing.value) {
            Object.assign(form, emptyForm());
        }
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="event-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="event-form__error" role="alert">{{ errors.form }}</p>

            <FormField label="Nom" :error="errors.name">
                <input v-model="form.name" type="text" required>
            </FormField>
            <FormField label="Lieu" :error="errors.location">
                <input v-model="form.location" type="text" required>
            </FormField>
            <FormField as="group" label="Dates" :error="errors.startDate ?? errors.endDate">
                <BaseDateRangePicker v-model:start="form.startDate" v-model:end="form.endDate" :invalid="Boolean(errors.startDate ?? errors.endDate)" />
            </FormField>

            <div class="event-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ isEditing ? 'Enregistrer' : 'Créer l\'événement' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.event-form { display: flex; flex-direction: column; gap: var(--space-3); }
.event-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.event-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
