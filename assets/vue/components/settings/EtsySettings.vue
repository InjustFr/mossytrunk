<script setup>
import { reactive, ref, watch } from 'vue';
import { Store } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    etsy: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'disconnect']);

const callbackUrl = `${window.location.origin}/parametres/etsy/retour`;
const form = reactive({ keystring: '', sharedSecret: '' });
const errors = ref({});
const saving = ref(false);

watch(() => props.etsy, (etsy) => {
    form.keystring = etsy.keystring ?? '';
    form.sharedSecret = '';
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ keystring: form.keystring, sharedSecret: form.sharedSecret || null });
        emit('saved');
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="etsy-settings">
        <p v-if="etsy.connected" class="etsy-settings__shop">
            <Store size="1.25rem" aria-hidden="true" />
            <span><strong>{{ etsy.shopName }}</strong> est connectée. Ses commandes payées s'importent depuis la page Commandes.</span>
        </p>

        <form class="etsy-settings__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <p class="etsy-settings__intro">
                    Créez une application sur etsy.com/developers, déclarez-y l'adresse de retour ci-dessous, puis collez ses clés.
                    Le secret est chiffré et n'est jamais réaffiché. Changer de clés déconnecte la boutique.
                </p>
                <p class="etsy-settings__callback">Adresse de retour <code>{{ callbackUrl }}</code></p>
                <p v-if="errors.form" class="etsy-settings__error" role="alert">{{ errors.form }}</p>
                <FormField label="Keystring" :error="errors.keystring">
                    <input v-model="form.keystring" type="text" autocomplete="off" required>
                </FormField>
                <FormField
                    label="Shared secret"
                    :error="errors.sharedSecret"
                    :hint="etsy.sharedSecretConfigured ? `Secret enregistré (${etsy.sharedSecretHint}). Laissez vide pour le conserver.` : 'Affiché sous la keystring de votre application Etsy.'"
                >
                    <input v-model="form.sharedSecret" type="password" autocomplete="off" :placeholder="etsy.sharedSecretHint ?? ''">
                </FormField>
                <div class="etsy-settings__actions">
                    <BaseButton type="submit" :variant="etsy.keystring ? 'secondary' : 'primary'" :loading="saving">Enregistrer les clés</BaseButton>
                </div>
            </fieldset>
        </form>

        <div v-if="etsy.keystring && etsy.sharedSecretConfigured" class="etsy-settings__actions etsy-settings__connection">
            <ConfirmButton
                v-if="etsy.connected"
                variant="ghost"
                label="Déconnecter la boutique"
                confirm-label="Déconnecter"
                message="MossyTrunk n'aura plus accès à la boutique. Les commandes déjà importées sont conservées."
                @confirm="emit('disconnect')"
            />
            <BaseButton v-else href="/parametres/etsy/connexion" data-turbo="false">Connecter ma boutique Etsy</BaseButton>
        </div>
    </div>
</template>

<style scoped>
.etsy-settings { display: flex; flex-direction: column; gap: var(--space-4); max-width: 40rem; }
.etsy-settings__shop { display: flex; align-items: flex-start; gap: var(--space-2); margin: 0; }
.etsy-settings__shop svg { flex: none; color: var(--color-accent); }
.etsy-settings__form { display: flex; flex-direction: column; gap: var(--space-3); }
.etsy-settings__intro { margin: 0; color: var(--color-muted); }
.etsy-settings__callback { margin: 0; font-size: 0.9rem; }
.etsy-settings__callback code { padding: 0.125rem var(--space-1); border-radius: 0.25rem; background: var(--color-bg); color: var(--color-ink); user-select: all; overflow-wrap: anywhere; }
.etsy-settings__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.etsy-settings__connection { padding-top: var(--space-3); border-top: 0.0625rem solid var(--color-border); }
.etsy-settings__error { margin: 0; color: var(--color-danger); }
</style>
