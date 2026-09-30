<script setup>
import { useI18n } from 'vue-i18n';
import { Link2 } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';

defineProps({
    label: { type: String, required: true },
    count: { type: Number, required: true },
});
const emit = defineEmits(['open']);
const { t } = useI18n();
</script>

<template>
    <div class="items-notice" role="status">
        <Link2 class="items-notice__icon" size="1.25rem" aria-hidden="true" />
        <p class="items-notice__text">
            <strong>{{ t('import.notice.count', { service: label }, count) }}</strong>
            {{ t('import.notice.text') }}
        </p>
        <BaseButton variant="secondary" @click="emit('open')">{{ t('import.notice.link') }}</BaseButton>
    </div>
</template>

<style scoped>
.items-notice {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-warning);
    border-left-width: 0.25rem;
    border-radius: var(--radius);
    background: var(--color-warning-soft);
}

.items-notice__icon { flex: none; color: var(--color-warning); }
.items-notice__text { flex: 1; margin: 0; }
.items-notice__text strong { color: var(--color-warning); }

@media (max-width: 40rem) {
    .items-notice { flex-direction: column; align-items: flex-start; }
}
</style>
