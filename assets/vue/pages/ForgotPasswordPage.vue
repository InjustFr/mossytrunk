<script setup>
import AuthLayout from '../layouts/AuthLayout.vue';
import AuthMessage from '../components/auth/AuthMessage.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import FormField from '../components/ui/FormField.vue';

defineProps({
    email: { type: String, default: '' },
    sent: { type: Boolean, default: false },
    error: { type: String, default: null },
    csrfToken: { type: String, required: true },
});
</script>

<template>
    <AuthLayout title="Mot de passe oublié">
        <AuthMessage v-if="sent" variant="success">
            Si un compte existe pour {{ email }}, un email contenant un lien pour choisir un nouveau mot de passe vient d'être envoyé.
            Le lien est valable une heure.
        </AuthMessage>
        <template v-else>
            <p class="forgot-password__intro">Indiquez votre email : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
            <AuthMessage v-if="error">{{ error }}</AuthMessage>
            <form class="forgot-password__form" method="post" action="/password/forgot" data-turbo="false">
                <input type="hidden" name="_csrf_token" :value="csrfToken">
                <FormField label="Email">
                    <input type="email" name="email" :value="email" autocomplete="email" required autofocus>
                </FormField>
                <BaseButton type="submit">Envoyer le lien</BaseButton>
            </form>
        </template>

        <template #footer>
            <a href="/login">Retour à la connexion</a>
        </template>
    </AuthLayout>
</template>

<style scoped>
.forgot-password__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.forgot-password__form { display: flex; flex-direction: column; gap: var(--space-4); }
</style>
