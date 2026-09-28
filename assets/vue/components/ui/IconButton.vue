<script setup>
import { TooltipArrow, TooltipContent, TooltipPortal, TooltipRoot, TooltipTrigger } from 'reka-ui';

defineOptions({ inheritAttrs: false });

defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    variant: { type: String, default: 'default' },
});
</script>

<template>
    <TooltipRoot>
        <TooltipTrigger as-child>
            <button type="button" :class="['icon-button', `icon-button--${variant}`]" :aria-label="label" v-bind="$attrs">
                <component :is="icon" size="1.0625rem" :stroke-width="1.75" aria-hidden="true" />
            </button>
        </TooltipTrigger>
        <TooltipPortal>
            <TooltipContent class="icon-button__tooltip" side="top" :side-offset="4">
                {{ label }}
                <TooltipArrow class="icon-button__arrow" :width="8" :height="4" />
            </TooltipContent>
        </TooltipPortal>
    </TooltipRoot>
</template>

<style scoped>
.icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 0.0625rem solid transparent;
    border-radius: var(--radius);
    background: none;
    color: var(--color-muted);
    cursor: pointer;
    transition: color var(--transition), background var(--transition), border-color var(--transition);
}

.icon-button:hover { color: var(--color-ink); background: var(--color-bg); border-color: var(--color-border); }
.icon-button:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.icon-button--danger:hover { color: var(--color-danger); background: var(--color-danger-soft); border-color: transparent; }
</style>

<style>
.icon-button__tooltip {
    z-index: 70;
    padding: var(--space-1) var(--space-2);
    border-radius: calc(var(--radius) - 0.125rem);
    background: var(--color-ink);
    color: #fff;
    font-size: 0.8rem;
    animation: icon-button-tooltip-in var(--transition);
}

.icon-button__arrow { fill: var(--color-ink); }

@keyframes icon-button-tooltip-in { from { opacity: 0; } }
</style>
