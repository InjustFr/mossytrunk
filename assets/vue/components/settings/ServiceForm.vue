<script setup>
import { computed, reactive, ref } from 'vue';
import { Copy } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import IconButton from '../ui/IconButton.vue';
import ServiceOptions from './ServiceOptions.vue';
import { SALES_CONTEXTS, UNKNOWN_ITEMS } from '../../composables/useServices.js';
import { useToast } from '../../composables/useToast.js';

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

const callbackUrl = computed(() => `${window.location.origin}/parametres/${props.service.key}/retour`);

function hint(field) {
    const value = connection?.values[field.name];
    if (field.secret && value?.configured) {
        return `Clé enregistrée (${value.hint}). Laissez vide pour la garder.`;
    }
    return field.hint;
}

async function copyCallback() {
    await navigator.clipboard.writeText(callbackUrl.value);
    toast.success('Adresse de retour copiée.');
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
            <p v-if="errors.form" class="service-form__error" role="alert">{{ errors.form }}</p>

            <section class="service-form__section" aria-labelledby="service-form-access">
                <h3 id="service-form-access" class="service-form__heading">Accès</h3>
                <div v-if="service.authorizes" class="service-form__callback">
                    <p class="service-form__instructions">{{ service.instructions }}</p>
                    <div class="service-form__url">
                        <code>{{ callbackUrl }}</code>
                        <IconButton :icon="Copy" label="Copier l'adresse de retour" @click="copyCallback" />
                    </div>
                </div>
                <FormField v-for="field in service.fields" :key="field.name" :label="field.label" :error="errors[field.name]" :hint="hint(field)">
                    <input
                        v-model="fields[field.name]"
                        :type="field.secret ? 'password' : 'text'"
                        :maxlength="field.maxLength"
                        :placeholder="field.secret ? (connection?.values[field.name]?.hint ?? '') : ''"
                        autocomplete="off"
                        :required="field.required"
                    >
                </FormField>
            </section>

            <section class="service-form__section" aria-labelledby="service-form-import">
                <h3 id="service-form-import" class="service-form__heading">Import</h3>
                <ServiceOptions v-model="options.salesContext" label="Où rattacher les ventes" :options="SALES_CONTEXTS" />
                <ServiceOptions v-model="options.unknownItems" label="Article inconnu" :options="UNKNOWN_ITEMS" />
            </section>

            <div class="service-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ connection ? 'Enregistrer' : `Ajouter ${service.label}` }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.service-form .form-lock { display: flex; flex-direction: column; gap: var(--space-5); min-width: 0; margin: 0; padding: 0; border: none; }
.service-form__section { display: flex; flex-direction: column; gap: var(--space-3); }

.service-form__heading {
    margin: 0;
    padding-bottom: var(--space-2);
    border-bottom: 0.0625rem solid var(--color-border);
    font-family: var(--font-display);
    font-size: 1.125rem;
    font-weight: 400;
    color: var(--color-ink);
}

.service-form__callback { display: flex; flex-direction: column; gap: var(--space-2); }
.service-form__instructions { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.service-form__url { display: flex; align-items: center; gap: var(--space-2); }

.service-form__url code {
    flex: 1;
    min-width: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-bg);
    color: var(--color-ink);
    font-size: 0.85rem;
    overflow-wrap: anywhere;
    user-select: all;
}

.service-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }

.service-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
