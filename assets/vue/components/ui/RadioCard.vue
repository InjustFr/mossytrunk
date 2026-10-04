<script setup>
import { RadioGroupIndicator, RadioGroupItem } from 'reka-ui';
</script>

<template>
    <RadioGroupItem class="radio-card">
        <span class="radio-card__ring"><RadioGroupIndicator class="radio-card__dot" /></span>
        <slot />
    </RadioGroupItem>
</template>

<style scoped>
.radio-card {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border);
    border-radius: var(--radius);
    background: var(--color-surface);
    text-align: left;
    cursor: pointer;
    transition: border-color var(--transition), background var(--transition);
}

.radio-card:hover { border-color: var(--color-border-strong); }
.radio-card[data-state='checked'] { border-color: var(--color-accent); background: var(--color-accent-soft); }

.radio-card__ring {
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

.radio-card[data-state='checked'] .radio-card__ring { border-color: var(--color-accent); }
.radio-card__dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: var(--color-accent); }
</style>
