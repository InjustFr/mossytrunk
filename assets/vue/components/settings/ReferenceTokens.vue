<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    tokens: { type: Array, required: true },
});
const emit = defineEmits(['insert']);

const placeholder = (token) => `{${token}}`;
</script>

<template>
    <ul class="reference-tokens">
        <li v-for="token in tokens" :key="token">
            <button
                type="button"
                class="reference-tokens__token"
                :aria-label="t('settings.references.form.insert', { label: t(`settings.references.tokens.${token}`) })"
                @click="emit('insert', placeholder(token))"
            >
                <span>{{ t(`settings.references.tokens.${token}`) }}</span>
                <code class="reference-tokens__code">{{ placeholder(token) }}</code>
            </button>
        </li>
    </ul>
</template>

<style scoped>
.reference-tokens { display: flex; flex-wrap: wrap; gap: var(--space-2); margin: 0; padding: 0; list-style: none; }

.reference-tokens__token {
    display: inline-flex;
    align-items: baseline;
    gap: var(--space-2);
    padding: var(--space-1) var(--space-2);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    color: var(--color-ink);
    font: inherit;
    font-size: 0.8125rem;
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.reference-tokens__token:hover { border-color: var(--color-accent); background: var(--color-accent-soft); }
.reference-tokens__code { color: var(--color-muted); font-size: 0.75rem; }
</style>
