<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { RotateCcw, TriangleAlert } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import Notice from '../ui/Notice.vue';
import ReferenceTokens from './ReferenceTokens.vue';
import ServiceOptions from './ServiceOptions.vue';

const PREVIEW_DELAY = 250;

const { t } = useI18n();

const props = defineProps({
    format: { type: Object, required: true },
    preview: { type: Function, required: true },
    change: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const template = ref(props.format.template);
const scope = ref('future');
const example = ref(props.format.example);
const error = ref(null);
const saving = ref(false);
const input = ref(null);

const renumbering = computed(() => scope.value === 'existing');
const scopes = computed(() => [
    { value: 'future', label: 'settings.references.scope.future.label', description: 'settings.references.scope.future.description' },
    {
        value: 'existing',
        label: 'settings.references.scope.existing.label',
        description: `settings.references.scope.existing.description.${props.format.kind}`,
        count: props.format.existing,
    },
]);

let timer = null;
let latest = 0;

watch(template, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => refreshExample(value), PREVIEW_DELAY);
});

async function refreshExample(value) {
    const call = ++latest;
    try {
        const shown = await props.preview(props.format.kind, value);
        if (call !== latest) return;
        example.value = shown;
        error.value = null;
    } catch (failure) {
        if (call !== latest) return;
        example.value = null;
        error.value = failure.message;
    }
}

async function insert(placeholder) {
    const field = input.value;
    const start = field?.selectionStart ?? template.value.length;
    const end = field?.selectionEnd ?? start;
    template.value = `${template.value.slice(0, start)}${placeholder}${template.value.slice(end)}`;
    await nextTick();
    field?.focus();
    field?.setSelectionRange(start + placeholder.length, start + placeholder.length);
}

async function onSubmit() {
    saving.value = true;
    error.value = null;
    try {
        const { renamed } = await props.change(props.format.kind, template.value, renumbering.value);
        emit('saved', renamed);
    } catch (failure) {
        error.value = failure.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="reference-format-form" novalidate @submit.prevent="renumbering || onSubmit()">
        <fieldset class="form-lock" :disabled="saving">
            <FormField :label="t('settings.references.form.template')" :error="error" :hint="t('settings.references.form.templateHint')">
                <input ref="input" v-model="template" type="text" class="reference-format-form__input" autocomplete="off" spellcheck="false">
            </FormField>
            <p class="reference-format-form__example" aria-live="polite">
                {{ t('settings.references.example') }} <strong>{{ example ?? '—' }}</strong>
            </p>

            <FormField as="group" :label="t('settings.references.form.tokens')" :hint="t('settings.references.form.tokensHint')">
                <ReferenceTokens :tokens="format.tokens" @insert="insert" />
            </FormField>
            <button v-if="template !== format.defaultTemplate" type="button" class="reference-format-form__restore" @click="template = format.defaultTemplate">
                <RotateCcw size="0.875rem" aria-hidden="true" /> {{ t('settings.references.form.restore') }} <code>{{ format.defaultTemplate }}</code>
            </button>

            <FormField v-if="format.existing > 0" as="group" :label="t('settings.references.form.scope')">
                <ServiceOptions v-model="scope" :label="t('settings.references.form.scope')" :options="scopes" />
            </FormField>
            <Notice v-if="renumbering && format.kind === 'product'" class="reference-format-form__warning">
                <TriangleAlert size="1rem" aria-hidden="true" /> {{ t('settings.references.form.skuWarning') }}
            </Notice>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('ui.cancel') }}</BaseButton>
                <ConfirmButton
                    v-if="renumbering"
                    :label="t('settings.references.form.save')"
                    :confirm-label="t('settings.references.form.renumber')"
                    :message="t('settings.references.form.renumberMessage')"
                    variant="primary"
                    @confirm="onSubmit"
                />
                <BaseButton v-else type="submit" :loading="saving">{{ t('settings.references.form.save') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.reference-format-form { display: flex; flex-direction: column; gap: var(--space-4); }
.reference-format-form__input { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
.reference-format-form__example { margin: calc(-1 * var(--space-2)) 0 0; color: var(--color-muted); font-size: var(--font-size-md); }
.reference-format-form__example strong { color: var(--color-ink); font-weight: 500; overflow-wrap: anywhere; }

.reference-format-form__restore {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    align-self: flex-start;
    padding: 0;
    border: 0;
    background: none;
    color: var(--color-accent);
    font: inherit;
    font-size: var(--font-size-sm);
    cursor: pointer;
}

.reference-format-form__restore code { color: var(--color-muted); }
.reference-format-form__warning svg { flex: none; color: var(--color-warning); }

.reference-format-form__warning {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    color: var(--color-ink);
    font-size: var(--font-size-sm);
    line-height: 1.4;
}
</style>
