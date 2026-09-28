<script setup>
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: null },
    hint: { type: String, default: null },
});
</script>

<template>
    <label :class="['form-field', { 'form-field--invalid': error }]">
        <span class="form-field__label">{{ label }}</span>
        <slot />
        <span v-if="error" class="form-field__error" role="alert">{{ error }}</span>
        <span v-else-if="hint" class="form-field__hint">{{ hint }}</span>
    </label>
</template>

<style scoped>
.form-field { display: flex; flex-direction: column; gap: var(--space-1); }

.form-field__label { font-weight: 600; font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-muted); }

.form-field :deep(input),
.form-field :deep(select),
.form-field :deep(textarea) {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    min-height: 38px;
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.form-field :deep(input:focus),
.form-field :deep(select:focus),
.form-field :deep(textarea:focus) {
    outline: none;
    border-color: var(--color-accent);
    box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.form-field--invalid :deep(input),
.form-field--invalid :deep(select) { border-color: var(--color-danger); }

.form-field__error { color: var(--color-danger); font-size: 0.85rem; }
.form-field__hint { color: var(--color-muted); font-size: 0.85rem; }
</style>
