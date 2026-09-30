<script setup>
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();
</script>

<template>
    <AuthLayout :title="t('auth.forgotPassword.title')">
        <AuthMessage v-if="sent" variant="success">
            {{ t('auth.forgotPassword.sent', { email }) }}
        </AuthMessage>
        <template v-else>
            <p class="forgot-password__intro">{{ t('auth.forgotPassword.intro') }}</p>
            <AuthMessage v-if="error">{{ error }}</AuthMessage>
            <form class="forgot-password__form" method="post" action="/password/forgot" data-turbo="false">
                <input type="hidden" name="_csrf_token" :value="csrfToken">
                <FormField :label="t('auth.email')">
                    <input type="email" name="email" :value="email" autocomplete="email" required autofocus>
                </FormField>
                <BaseButton type="submit">{{ t('auth.forgotPassword.submit') }}</BaseButton>
            </form>
        </template>

        <template #footer>
            <a href="/login">{{ t('auth.backToLogin') }}</a>
        </template>
    </AuthLayout>
</template>

<style scoped>
.forgot-password__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.forgot-password__form { display: flex; flex-direction: column; gap: var(--space-4); }
</style>
