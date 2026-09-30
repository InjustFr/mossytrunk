<script setup>
import { reactive, ref, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import TypeColorPicker from './TypeColorPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { useSuggestion } from '../../composables/useSuggestion.js';

const props = defineProps({
    type: { type: Object, default: null },
    defaultColor: { type: String, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();

const form = reactive({ name: props.type?.name ?? '', color: props.type?.color ?? props.defaultColor, code: props.type?.code ?? '' });
const errors = ref({});
const saving = ref(false);

const { suggestCode } = useProductTypes();
const codeSuggestion = useSuggestion(toRef(form, 'code'), () => form.name, suggestCode, { follow: !props.type });

function onCodeInput() {
    form.code = form.code.toUpperCase();
    codeSuggestion.edited();
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ name: form.name, color: form.color, code: form.code });
        emit('saved', form.name.trim());
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { form: error.message };
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="product-type-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="product-type-form__error" role="alert">{{ errors.form }}</p>

            <FormField :label="t('products.types.name')" :error="errors.name" :hint="t('products.types.nameExample')">
                <input v-model="form.name" type="text" maxlength="100">
            </FormField>

            <FormField :label="t('products.types.code')" :error="errors.code" :hint="t('products.types.codeHint')">
                <input v-model="form.code" type="text" maxlength="8" autocomplete="off" class="product-type-form__code" @input="onCodeInput">
            </FormField>

            <FormField as="group" :label="t('products.types.color')" :error="errors.color">
                <TypeColorPicker v-model="form.color" />
            </FormField>

            <div class="product-type-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t(type ? 'products.save' : 'products.types.create') }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-type-form { display: flex; flex-direction: column; gap: var(--space-3); }
.product-type-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
.product-type-form__code { text-transform: uppercase; }
.product-type-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
