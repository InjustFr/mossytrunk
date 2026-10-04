<script setup>
import { X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import Callout from '../ui/Callout.vue';
defineProps({
    problem: { type: Object, required: true },
});
const emit = defineEmits(['dismiss']);
const { t } = useI18n();
</script>

<template>
    <Callout tone="danger" role="alert" data-test="import-problem">
        <div class="import-problem__content">
            <p class="import-problem__message">{{ problem.message }}</p>
            <p v-if="problem.dates.length" class="import-problem__detail">
                {{ t('import.problem.datesWithoutEvent') }} <strong>{{ problem.dates.join(', ') }}</strong>
                — <a href="/events">{{ t('import.problem.createEvent') }}</a>
            </p>
        </div>
        <button type="button" class="import-problem__close" :aria-label="t('import.problem.close')" @click="emit('dismiss')"><X size="1rem" aria-hidden="true" /></button>
    </Callout>
</template>

<style scoped>
.import-problem__content { flex: 1; }
.import-problem__message { margin: 0; font-weight: 600; color: var(--color-danger); }
.import-problem__detail { margin: var(--space-1) 0 0; }

.import-problem__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1;
    color: var(--color-muted);
}
</style>
