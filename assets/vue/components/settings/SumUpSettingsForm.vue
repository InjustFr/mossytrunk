<script setup>
import { reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    sumUp: { type: Object, required: true },
    submit: { type: Function, required: true },
    removeApiKey: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'removed']);

const form = reactive({ merchantCode: '', apiKey: '' });
const errors = ref({});
const saving = ref(false);

watch(() => props.sumUp, (sumUp) => {
    form.merchantCode = sumUp.merchantCode ?? '';
    form.apiKey = '';
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ merchantCode: form.merchantCode, apiKey: form.apiKey || null });
        emit('saved');
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}

async function onRemove() {
    await props.removeApiKey();
    emit('removed');
}
</script>

<template>
    <form class="sumup-settings" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p class="sumup-settings__intro">
                Utilisés par l'import des commandes SumUp. La clé API est chiffrée et n'est jamais réaffichée.
            </p>
            <p v-if="errors.form" class="sumup-settings__error" role="alert">{{ errors.form }}</p>

            <FormField label="Code marchand" :error="errors.merchantCode">
                <input v-model="form.merchantCode" type="text" autocomplete="off" required>
            </FormField>
            <FormField
                label="Clé API"
                :error="errors.apiKey"
                :hint="sumUp.apiKeyConfigured ? `Clé enregistrée (${sumUp.apiKeyHint}). Laissez vide pour la conserver.` : 'Clé secrète sup_sk_… (Tableau de bord SumUp → Clés API).'"
            >
                <input v-model="form.apiKey" type="password" autocomplete="off" :placeholder="sumUp.apiKeyHint ?? ''">
            </FormField>

            <div class="sumup-settings__actions">
                <ConfirmButton v-if="sumUp.apiKeyConfigured" label="Supprimer la clé" @confirm="onRemove" />
                <BaseButton type="submit" :loading="saving">Enregistrer</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.sumup-settings { display: flex; flex-direction: column; gap: var(--space-3); max-width: 32rem; }
.sumup-settings__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.sumup-settings__actions { display: flex; justify-content: flex-end; gap: var(--space-2); align-items: center; }
.sumup-settings__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
