<script setup>
import { RadioGroupIndicator, RadioGroupItem, RadioGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    label: { type: String, required: true },
    options: { type: Array, required: true },
});
const model = defineModel({ type: String, required: true });
</script>

<template>
    <RadioGroupRoot v-model="model" class="service-options" :aria-label="label">
        <RadioGroupItem v-for="option in options" :key="option.value" :value="option.value" class="service-options__item">
            <span class="service-options__radio"><RadioGroupIndicator class="service-options__dot" /></span>
            <span class="service-options__text">
                <span class="service-options__name">{{ t(option.label) }}</span>
                <span class="service-options__description">{{ t(option.description) }}</span>
            </span>
        </RadioGroupItem>
    </RadioGroupRoot>
</template>

<style scoped>
.service-options { display: flex; flex-direction: column; gap: var(--space-2); }

.service-options__item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: inherit;
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.service-options__item:hover { border-color: var(--color-border-strong); }
.service-options__item[data-state='checked'] { border-color: var(--color-accent); background: var(--color-accent-soft); }
.service-options__item:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }

.service-options__radio {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    margin-top: 0.125rem;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 50%;
    background: var(--color-surface);
}

.service-options__item[data-state='checked'] .service-options__radio { border-color: var(--color-accent); }
.service-options__dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: var(--color-accent); }

.service-options__text { display: flex; flex-direction: column; gap: 0.125rem; }
.service-options__name { font-size: 0.875rem; font-weight: 500; color: var(--color-ink); }
.service-options__description { color: var(--color-muted); font-size: 0.8125rem; line-height: 1.4; }
</style>
