<script setup>
import { Label } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import FieldError from './FieldError.vue';

defineProps({
    label: { type: String, required: true },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    optional: { type: Boolean, default: false },
    as: { type: String, default: 'label' },
});

const { t } = useI18n();
const id = `form-field-${Math.random().toString(36).slice(2, 9)}`;
</script>

<template>
    <Label v-if="as === 'label'" :class="['form-field', { 'form-field--invalid': error }]">
        <span class="form-field__label">{{ label }}<span v-if="optional" class="form-field__optional">{{ t('ui.form.optional') }}</span></span>
        <span class="form-field__control">
            <slot />
            <FieldError v-if="error">{{ error }}</FieldError>
            <span v-else-if="hint" class="form-field__hint">{{ hint }}</span>
        </span>
    </Label>
    <div v-else :class="['form-field', { 'form-field--invalid': error }]" role="group" :aria-labelledby="id">
        <span :id="id" class="form-field__label">{{ label }}<span v-if="optional" class="form-field__optional">{{ t('ui.form.optional') }}</span></span>
        <div class="form-field__control">
            <slot />
            <FieldError v-if="error">{{ error }}</FieldError>
            <span v-else-if="hint" class="form-field__hint">{{ hint }}</span>
        </div>
    </div>
</template>

<style scoped>
.form-field { display: flex; flex-direction: column; gap: var(--space-1); }
.form-field__control { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; }

.form-field__label { font-weight: 500; font-size: var(--font-size-md); color: var(--color-text); }
.form-field__optional { margin-left: var(--space-2); font-weight: 400; color: var(--color-subtle); }

@container form (min-width: 28rem) {
    .form-field {
        display: grid;
        grid-template-columns: 8.5rem minmax(0, 1fr);
        align-items: start;
        gap: var(--space-4);
    }

    .form-field__label { padding-top: 0.5625rem; line-height: 1.25; }
    .form-field__optional { display: block; margin: 0.125rem 0 0; font-size: var(--font-size-sm); }
}

.form-field--invalid :deep(:is(.control, .form-field__control > input)) { border-color: var(--color-danger); }

.form-field__hint { color: var(--color-muted); font-size: var(--font-size-sm); line-height: 1.4; }
</style>
