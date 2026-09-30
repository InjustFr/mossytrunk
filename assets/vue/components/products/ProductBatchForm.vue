<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';
import VariantsInput from './VariantsInput.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';

const props = defineProps({
    count: { type: Number, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { t } = useI18n();
const { types } = useProductTypes();
const typeOptions = computed(() => [{ value: '', label: t('products.untyped') }, ...types.value.map((type) => ({ value: type.id, label: type.name }))]);

const form = reactive({
    changeSellingPrice: false, sellingPrice: null,
    changeType: false, typeId: '',
    addVariants: [], removeVariants: [],
});
const errors = ref({});
const saving = ref(false);

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        const result = await props.submit({
            sellingPrice: form.changeSellingPrice ? form.sellingPrice ?? -1 : null,
            changeType: form.changeType,
            typeId: form.changeType && form.typeId ? form.typeId : null,
            addVariants: form.addVariants,
            removeVariants: form.removeVariants,
        });
        emit('saved', result.updated);
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="product-batch-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p class="product-batch-form__intro">{{ t('products.batch.intro', count) }}</p>
            <p v-if="errors.form" class="product-batch-form__error" role="alert">{{ errors.form }}</p>

            <div class="product-batch-form__option">
                <label class="product-batch-form__toggle"><BaseCheckbox v-model="form.changeSellingPrice" /> {{ t('products.batch.changeSellingPrice') }}</label>
                <FormField v-if="form.changeSellingPrice" :label="t('products.batch.newSellingPrice')" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" />
                </FormField>
            </div>

            <div class="product-batch-form__option">
                <label class="product-batch-form__toggle"><BaseCheckbox v-model="form.changeType" /> {{ t('products.batch.changeType') }}</label>
                <FormField v-if="form.changeType" :label="t('products.batch.newType')">
                    <BaseSelect v-model="form.typeId" :options="typeOptions" />
                </FormField>
            </div>

            <FormField as="group" :label="t('products.batch.addVariants')" :error="errors.addVariants" :hint="t('products.batch.addVariantsHint')">
                <VariantsInput v-model="form.addVariants" :input-label="t('products.batch.addVariant')" />
            </FormField>
            <FormField as="group" :label="t('products.batch.removeVariants')">
                <VariantsInput v-model="form.removeVariants" :input-label="t('products.batch.removeVariant')" />
            </FormField>

            <div class="product-batch-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('products.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ t('products.batch.apply', count) }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-batch-form { display: flex; flex-direction: column; gap: var(--space-4); }
.product-batch-form__intro { margin: 0; color: var(--color-muted); }
.product-batch-form__option { display: flex; flex-direction: column; gap: var(--space-2); }
.product-batch-form__toggle { display: flex; align-items: center; gap: var(--space-2); font-weight: 600; cursor: pointer; }
.product-batch-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.product-batch-form__error { margin: 0; padding: var(--space-2) var(--space-3); background: var(--color-danger-soft); color: var(--color-danger); border-radius: var(--radius); }
</style>
