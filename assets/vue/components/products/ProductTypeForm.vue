<script setup>
import { computed, reactive, ref, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseSwitch from '../ui/BaseSwitch.vue';
import FormActions from '../ui/FormActions.vue';
import FormDisclosure from '../ui/FormDisclosure.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import TypeColorPicker from './TypeColorPicker.vue';
import TypeVariantsEditor from './TypeVariantsEditor.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { useSuggestion } from '../../composables/useSuggestion.js';
import { useToast } from '../../composables/useToast.js';

const props = defineProps({
    type: { type: Object, default: null },
    defaultColor: { type: String, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel', 'renamed']);
const { t } = useI18n();

const form = reactive({
    name: props.type?.name ?? '',
    color: props.type?.color ?? props.defaultColor,
    code: props.type?.code ?? '',
    variants: [...(props.type?.variants ?? [])],
    archivedVariants: [...(props.type?.archivedVariants ?? [])],
    prefixesNames: props.type?.prefixesNames ?? true,
});
const errors = ref({});
const saving = ref(false);
const savedVariants = ref([...(props.type?.variants ?? [])]);
const toast = useToast();
const moreOpen = ref(false);

const { suggestCode, renameVariant } = useProductTypes();
const codeSuggestion = useSuggestion(toRef(form, 'code'), () => form.name, suggestCode, { follow: !props.type });

function onCodeInput() {
    form.code = form.code.toUpperCase();
    codeSuggestion.edited();
}

const typeName = computed(() => form.name.trim() || t('products.types.exampleType'));
const example = computed(() => (form.prefixesNames
    ? t('products.types.prefixedExample', { type: typeName.value })
    : t('products.types.plainExample')));
const exampleName = computed(() => (form.prefixesNames
    ? `${typeName.value} ${t('products.types.exampleProduct')}`
    : t('products.types.exampleProduct')));
const moreSummary = computed(() => [form.code, t('products.types.displayedAs', { name: exampleName.value })].filter(Boolean).join(', '));
const renamesEverywhere = computed(() => props.type !== null && savedVariants.value.length > 0);

async function renameSaved(from, to) {
    if (!savedVariants.value.includes(from)) return;
    await renameVariant(props.type.id, from, to);
    savedVariants.value = savedVariants.value.map((variant) => (variant === from ? to : variant));
    toast.success(t('products.toast.variantRenamed', { from, to }));
    emit('renamed');
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ ...form });
        emit('saved', form.name.trim());
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { form: error.message };
        }
        if (errors.value.code) {
            moreOpen.value = true;
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

            <FormSection>
                <FormField :label="t('products.types.name')" :error="errors.name">
                    <input v-model="form.name" type="text" maxlength="100" :placeholder="t('products.types.nameExample')">
                </FormField>
                <FormField as="group" :label="t('products.types.color')" :error="errors.color">
                    <TypeColorPicker v-model="form.color" />
                </FormField>
                <FormField as="group" :label="t('products.types.variants.label')" :error="errors.variants" :hint="renamesEverywhere ? t('products.types.variants.hint') : null">
                    <TypeVariantsEditor v-model="form.variants" v-model:archived="form.archivedVariants" :rename="renameSaved" />
                </FormField>
            </FormSection>

            <FormDisclosure v-model:open="moreOpen" :title="t('products.types.more')" :summary="moreSummary">
                <FormField :label="t('products.types.code')" :error="errors.code" :hint="t('products.types.codeHint')">
                    <input v-model="form.code" type="text" maxlength="8" autocomplete="off" class="product-type-form__code" @input="onCodeInput">
                </FormField>
                <FormField as="group" :label="t('products.types.display')" :hint="example">
                    <label class="product-type-form__switch"><BaseSwitch v-model="form.prefixesNames" /> {{ t('products.types.prefixesNames') }}</label>
                </FormField>
            </FormDisclosure>

            <FormActions>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t(type ? 'products.save' : 'products.types.create') }}</BaseButton>
            </FormActions>
        </fieldset>
    </form>
</template>

<style scoped>
.product-type-form { display: flex; flex-direction: column; gap: var(--space-5); }
.product-type-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
.product-type-form .product-type-form__code { max-width: 11rem; text-transform: uppercase; }
.product-type-form__switch { display: flex; align-items: center; gap: var(--space-2); min-height: 2.375rem; font-size: 0.875rem; cursor: pointer; }
</style>
