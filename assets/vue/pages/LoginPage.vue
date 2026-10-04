<script setup>
import { useI18n } from 'vue-i18n';
import AuthLayout from '../layouts/AuthLayout.vue';
import AuthMessage from '../components/auth/AuthMessage.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCheckbox from '../components/ui/BaseCheckbox.vue';
import FormField from '../components/ui/FormField.vue';

defineProps({
    lastEmail: { type: String, default: '' },
    error: { type: String, default: null },
    notice: { type: String, default: null },
    csrfToken: { type: String, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AuthLayout :title="t('auth.login.title')">
        <AuthMessage v-if="notice" variant="success">{{ notice }}</AuthMessage>
        <AuthMessage v-if="error">{{ error }}</AuthMessage>

        <form class="login-form" method="post" action="/login" data-turbo="false">
            <input type="hidden" name="_csrf_token" :value="csrfToken">
            <FormField :label="t('auth.email')">
                <input type="email" name="email" :value="lastEmail" autocomplete="email" required autofocus>
            </FormField>
            <FormField :label="t('auth.password')">
                <input type="password" name="password" autocomplete="current-password" required>
            </FormField>
            <label class="login-form__remember">
                <BaseCheckbox name="_remember_me" />
                {{ t('auth.login.rememberMe') }}
            </label>
            <BaseButton type="submit">{{ t('auth.login.submit') }}</BaseButton>
        </form>

        <template #footer>
            <a href="/password/forgot">{{ t('auth.login.forgotPassword') }}</a>
        </template>
    </AuthLayout>
</template>

<style scoped>
.login-form { display: flex; flex-direction: column; gap: var(--space-4); }
.login-form__remember { display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-size-md); color: var(--color-muted); }
</style>
