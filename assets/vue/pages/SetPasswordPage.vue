<script setup>
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();
</script>

<template>
    <AuthLayout :title="invitation ? t('auth.setPassword.welcome') : t('auth.setPassword.title')">
        <template v-if="linkError">
            <AuthMessage>{{ linkError }}</AuthMessage>
            <a href="/password/forgot">{{ t('auth.setPassword.newLink') }}</a>
        </template>
        <template v-else>
            <p class="set-password__intro">
                {{ invitation ? t('auth.setPassword.introInvitation') : t('auth.setPassword.intro') }}
            </p>
            <AuthMessage v-if="error">{{ error }}</AuthMessage>
            <form class="set-password__form" method="post" action="/password/set" data-turbo="false">
                <input type="hidden" name="_csrf_token" :value="csrfToken">
                <FormField :label="t('auth.password')" :hint="t('auth.setPassword.minLength', { count: minLength })">
                    <input type="password" name="password" autocomplete="new-password" :minlength="minLength" required autofocus>
                </FormField>
                <FormField :label="t('auth.setPassword.confirmation')">
                    <input type="password" name="confirmation" autocomplete="new-password" :minlength="minLength" required>
                </FormField>
                <BaseButton type="submit">{{ t('auth.setPassword.submit') }}</BaseButton>
            </form>
        </template>

        <template #footer>
            <a href="/login">{{ t('auth.backToLogin') }}</a>
        </template>
    </AuthLayout>
</template>

<style scoped>
.set-password__intro { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.set-password__form { display: flex; flex-direction: column; gap: var(--space-4); }
</style>
