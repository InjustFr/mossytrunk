<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    services: { type: Array, required: true },
});
const emit = defineEmits(['choose']);
</script>

<template>
    <ul class="service-picker" :aria-label="t('settings.picker.label')">
        <li v-for="service in props.services" :key="service.key" class="service-picker__slot">
            <button
                type="button"
                class="service-tag"
                :disabled="Boolean(service.connection)"
                :aria-describedby="`service-tag-${service.key}`"
                @click="emit('choose', service)"
            >
                <span class="service-tag__card">
                    <span class="service-tag__name">{{ service.label }}</span>
                    <span :id="`service-tag-${service.key}`" class="service-tag__summary">{{ service.connection ? t('settings.picker.alreadyAdded') : t(service.summary) }}</span>
                </span>
            </button>
        </li>
    </ul>
</template>

<style scoped>
.service-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
    gap: var(--space-5) var(--space-4);
    margin: 0;
    padding: var(--space-6) var(--space-4) var(--space-4);
    border-radius: var(--radius);
    background: var(--color-bg);
    list-style: none;
}

.service-picker__slot { display: flex; }

.service-tag {
    --tag-cut: 0.875rem;
    --tag-hole: 0.4375rem;
    position: relative;
    flex: 1;
    padding: 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    text-align: left;
    cursor: pointer;
    filter: drop-shadow(0 0 0.0625rem var(--color-border-strong));
    transform-origin: 1.375rem 1.375rem;
    transition: transform var(--transition), filter var(--transition);
}

.service-tag::before {
    content: '';
    position: absolute;
    bottom: calc(100% - 1.375rem);
    left: 1.375rem;
    width: 0.125rem;
    height: 2.5rem;
    border-radius: 0.0625rem;
    background: var(--color-accent-strong);
    transform: rotate(14deg);
    transform-origin: bottom;
}

.service-tag__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    min-height: 8rem;
    padding: var(--space-6) var(--space-4) var(--space-4);
    background:
        radial-gradient(circle at 1.375rem 1.375rem, transparent var(--tag-hole), var(--color-surface) calc(var(--tag-hole) + 0.03rem));
    clip-path: polygon(var(--tag-cut) 0, calc(100% - var(--tag-cut)) 0, 100% var(--tag-cut), 100% 100%, 0 100%, 0 var(--tag-cut));
}

.service-tag__name {
    font-family: var(--font-display);
    font-size: 1.375rem;
    line-height: 1.1;
    color: var(--color-ink);
}

.service-tag__summary { color: var(--color-muted); font-size: 0.875rem; line-height: 1.4; }

.service-tag:not(:disabled):hover,
.service-tag:not(:disabled):focus-visible {
    transform: rotate(-2.5deg);
    filter: drop-shadow(0 0 0.0625rem var(--color-accent)) drop-shadow(0 0.375rem 0.5rem rgb(17 17 17 / 8%));
}

.service-tag:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.25rem; border-radius: 0.125rem; }

.service-tag:disabled { cursor: default; opacity: 0.55; }
.service-tag:disabled::before { background: var(--color-border-strong); }

@media (prefers-reduced-motion: reduce) {
    .service-tag { transition: none; }
    .service-tag:not(:disabled):hover,
    .service-tag:not(:disabled):focus-visible { transform: none; }
}
</style>
