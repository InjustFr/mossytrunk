<script setup>
import { SwitchRoot, SwitchThumb } from 'reka-ui';
</script>

<template>
    <SwitchRoot class="switch">
        <SwitchThumb class="switch__thumb" />
    </SwitchRoot>
</template>

<style scoped>
.switch {
    position: relative;
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    width: 2.25rem;
    height: 1.25rem;
    padding: 0.125rem;
    border: none;
    border-radius: 62.4375rem;
    background: var(--color-border-strong);
    cursor: pointer;
    transition: background var(--transition);
}

.switch:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.switch[data-state="checked"] { background: var(--color-accent); }

.switch__thumb {
    display: block;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--color-surface);
    box-shadow: 0 0.0625rem 0.125rem rgb(17 17 17 / 20%);
    transition: transform var(--transition);
}

.switch__thumb[data-state="checked"] { transform: translateX(1rem); }
</style>
