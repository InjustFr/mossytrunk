<script setup>
import { RadioGroupRoot } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import RadioCard from '../ui/RadioCard.vue';

const { t } = useI18n();

defineProps({
    label: { type: String, required: true },
    options: { type: Array, required: true },
});
const model = defineModel({ type: String, required: true });

const describe = (option) => (option.count === undefined ? t(option.description) : t(option.description, { count: option.count }, option.count));
</script>

<template>
    <RadioGroupRoot v-model="model" class="service-options" :aria-label="label">
        <RadioCard v-for="option in options" :key="option.value" :value="option.value">
            <span class="service-options__text">
                <span class="service-options__name">{{ t(option.label) }}</span>
                <span class="service-options__description">{{ describe(option) }}</span>
            </span>
        </RadioCard>
    </RadioGroupRoot>
</template>

<style scoped>
.service-options { display: flex; flex-direction: column; gap: var(--space-2); }

.service-options__text { display: flex; flex-direction: column; gap: 0.125rem; }
.service-options__name { font-size: var(--font-size-md); font-weight: 500; color: var(--color-ink); }
.service-options__description { color: var(--color-muted); font-size: var(--font-size-sm); line-height: 1.4; }
</style>
