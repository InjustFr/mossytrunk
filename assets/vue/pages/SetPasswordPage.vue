<script setup>
import AuthLayout from '../layouts/AuthLayout.vue';
import AuthMessage from '../components/auth/AuthMessage.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import FormField from '../components/ui/FormField.vue';

defineProps({
    invitation: { type: Boolean, default: false },
    linkError: { type: String, default: null },
    error: { type: String, default: null },
    minLength: { type: Number, required: true },
    csrfToken: { type: String, required: true },
});
</script>

<template>
    <AuthLayout :title="invitation ? 'Bienvenue' : 'Nouveau mot de passe'">
        <template v-if="linkError">
            <AuthMessage>{{ linkError }}</AuthMessage>
            <a href="/password/forgot">Recevoir un nouveau lien</a>
        </template>
        <template v-else>
            <p class="set-password__intro">
                {{ invitation ? 'Choisissez le mot de passe de votre compte.' : 'Choisissez votre nouveau mot de passe.' }}
            </p>
            <AuthMessage v-if="error">{{ error }}</AuthMessage>
            <form class="set-password__form" method="post" action="/password/set" data-turbo="false">
                <input type="hidden" name="_csrf_token" :value="csrfToken">
                <FormField label="Mot de passe" :hint="`Au moins ${minLength} caractères.`">
                    <input type="password" name="password" autocomplete="new-password" :minlength="minLength" required autofocus>
                </FormField>
                <FormField label="Confirmation">
                    <input type="password" name="confirmation" autocomplete="new-password" :minlength="minLength" required>
                </FormField>
                <BaseButton type="submit">Enregistrer le mot de passe</BaseButton>
            </form>
        </template>

        <template #footer>
            <a href="/login">Retour à la connexion</a>
        </template>
    </AuthLayout>
</template>

<style scoped>
.set-password__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.set-password__form { display: flex; flex-direction: column; gap: var(--space-4); }
</style>
