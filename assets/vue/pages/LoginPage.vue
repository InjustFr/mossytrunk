<script setup>
import AuthLayout from '../layouts/AuthLayout.vue';
import AuthMessage from '../components/auth/AuthMessage.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import FormField from '../components/ui/FormField.vue';

defineProps({
    lastEmail: { type: String, default: '' },
    error: { type: String, default: null },
    notice: { type: String, default: null },
    csrfToken: { type: String, required: true },
});
</script>

<template>
    <AuthLayout title="Connexion">
        <AuthMessage v-if="notice" variant="success">{{ notice }}</AuthMessage>
        <AuthMessage v-if="error">{{ error }}</AuthMessage>

        <form class="login-form" method="post" action="/connexion" data-turbo="false">
            <input type="hidden" name="_csrf_token" :value="csrfToken">
            <FormField label="Email">
                <input type="email" name="email" :value="lastEmail" autocomplete="email" required autofocus>
            </FormField>
            <FormField label="Mot de passe">
                <input type="password" name="password" autocomplete="current-password" required>
            </FormField>
            <label class="login-form__remember">
                <input type="checkbox" name="_remember_me">
                Se souvenir de moi
            </label>
            <BaseButton type="submit">Se connecter</BaseButton>
        </form>

        <template #footer>
            <a href="/mot-de-passe/oublie">Mot de passe oublié ?</a>
        </template>
    </AuthLayout>
</template>

<style scoped>
.login-form { display: flex; flex-direction: column; gap: var(--space-4); }
.login-form__remember { display: flex; align-items: center; gap: var(--space-2); font-size: 0.9rem; color: var(--color-muted); }
</style>
