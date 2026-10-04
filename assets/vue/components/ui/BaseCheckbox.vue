<script setup>
import { Check } from '@lucide/vue';
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui';
</script>

<template>
    <CheckboxRoot class="checkbox">
        <CheckboxIndicator class="checkbox__indicator">
            <Check size="0.75rem" :stroke-width="3" aria-hidden="true" />
        </CheckboxIndicator>
    </CheckboxRoot>
</template>

<style scoped>
.checkbox {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 1.125rem;
    height: 1.125rem;
    padding: 0;
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius-sm);
    background: var(--color-surface);
    color: var(--color-on-accent);
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition);
}

.checkbox:hover:not([data-disabled]) { border-color: var(--color-ink); }
.checkbox[data-state="checked"] { background: var(--color-accent); border-color: var(--color-accent); }
.checkbox[data-disabled] { opacity: 0.6; cursor: not-allowed; }
.checkbox__indicator { display: inline-flex; }
</style>
