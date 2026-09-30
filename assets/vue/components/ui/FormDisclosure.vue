<script setup>
import { ChevronRight } from '@lucide/vue';
import { CollapsibleContent, CollapsibleRoot, CollapsibleTrigger } from 'reka-ui';

defineProps({
    title: { type: String, required: true },
    summary: { type: String, default: null },
});
const open = defineModel('open', { type: Boolean, default: false });
</script>

<template>
    <CollapsibleRoot v-model:open="open" class="form-disclosure">
        <CollapsibleTrigger class="form-disclosure__trigger">
            <ChevronRight class="form-disclosure__chevron" size="1rem" aria-hidden="true" />
            <span class="form-disclosure__title">{{ title }}</span>
            <span v-if="summary && !open" class="form-disclosure__summary">{{ summary }}</span>
        </CollapsibleTrigger>
        <CollapsibleContent class="form-disclosure__content">
            <div class="form-disclosure__fields"><slot /></div>
        </CollapsibleContent>
    </CollapsibleRoot>
</template>

<style scoped>
.form-disclosure { container: form / inline-size; }

.form-disclosure__trigger {
    display: flex;
    align-items: baseline;
    gap: var(--space-2);
    width: 100%;
    padding: var(--space-1) 0;
    border: none;
    background: none;
    color: var(--color-text);
    font-size: 0.875rem;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
}

.form-disclosure__trigger:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; border-radius: var(--radius); }
.form-disclosure__chevron { align-self: center; flex-shrink: 0; color: var(--color-muted); transition: transform var(--transition); }
.form-disclosure__trigger[data-state="open"] .form-disclosure__chevron { transform: rotate(90deg); }
.form-disclosure__summary { overflow: hidden; color: var(--color-muted); font-weight: 400; text-overflow: ellipsis; white-space: nowrap; }
.form-disclosure__fields { display: flex; flex-direction: column; gap: var(--space-4); padding-top: var(--space-4); }

.form-disclosure__content { overflow: hidden; }
.form-disclosure__content[data-state="open"] { animation: form-disclosure-open var(--transition); }
.form-disclosure__content[data-state="closed"] { animation: form-disclosure-close var(--transition); }

@keyframes form-disclosure-open { from { height: 0; } to { height: var(--reka-collapsible-content-height); } }
@keyframes form-disclosure-close { from { height: var(--reka-collapsible-content-height); } to { height: 0; } }

@media (prefers-reduced-motion: reduce) {
    .form-disclosure__chevron { transition: none; }
    .form-disclosure__content[data-state] { animation: none; }
}
</style>
