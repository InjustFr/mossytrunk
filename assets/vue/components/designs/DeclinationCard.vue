<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Trash2 } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import ConfirmButton from '../ui/ConfirmButton.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import ProductTag from '../products/ProductTag.vue';
import VariantPicker from '../products/VariantPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';

const props = defineProps({
    declination: { type: Object, required: true },
    locked: { type: Boolean, default: false },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['tick', 'withdraw']);
const { t } = useI18n();
const { types, variantsOf } = useProductTypes();

const form = reactive({ productName: '', sellingPrice: null, variants: [] });
const errors = ref({});
const saving = ref(false);

watch(() => props.declination, (declination) => {
    Object.assign(form, { productName: declination.productName, sellingPrice: declination.sellingPrice, variants: [...declination.variants] });
}, { immediate: true });

const dirty = computed(() => form.productName !== props.declination.productName
    || form.sellingPrice !== props.declination.sellingPrice
    || form.variants.join('|') !== props.declination.variants.join('|'));
const done = (adaptation) => props.declination.doneAdaptations.includes(adaptation);
const typePrefix = computed(() => (props.declination.gabarit.prefixesNames ? props.declination.gabarit.typeName : null));
const typeColor = computed(() => types.value.find((type) => type.id === props.declination.gabarit.typeId)?.color ?? null);
const displayName = computed(() => (form.productName.trim() ? [typePrefix.value, form.productName.trim()].filter(Boolean).join(' ') : ''));

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({ ...form, sellingPrice: form.sellingPrice ?? -1 });
    } catch (error) {
        errors.value = error.fieldErrors ?? { productName: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <article :class="['declination', { 'declination--ready': declination.ready }]" :aria-label="declination.gabarit.name">
        <header class="declination__header">
            <h3 class="declination__gabarit">{{ declination.gabarit.name }}</h3>
            <a v-if="declination.productId" :href="`/products/${declination.productId}`" class="declination__product-link">{{ t('designs.declination.seeProduct') }}</a>
            <StatusBadge v-else-if="declination.ready" tone="success">{{ t('designs.declination.ready') }}</StatusBadge>
            <StatusBadge v-else tone="warning">{{ t('designs.declination.toAdapt', { count: declination.adaptations.length - declination.doneAdaptations.length }) }}</StatusBadge>
            <ConfirmButton
                v-if="!locked"
                :icon="Trash2"
                :label="t('designs.declination.withdraw', { name: declination.gabarit.name })"
                :message="t('designs.declination.withdrawMessage')"
                @confirm="emit('withdraw')"
            />
        </header>

        <section v-if="declination.adaptations.length" class="declination__checklist" :aria-label="t('designs.declination.checklist', { name: declination.gabarit.name })">
            <label v-for="adaptation in declination.adaptations" :key="adaptation" :class="['declination__adaptation', { 'declination__adaptation--done': done(adaptation) }]">
                <BaseCheckbox :model-value="done(adaptation)" :disabled="locked" @update:model-value="emit('tick', adaptation, $event)" />
                {{ adaptation }}
            </label>
        </section>
        <p v-else class="declination__none">{{ t('designs.declination.noAdaptations') }}</p>

        <ProductTag v-if="locked" :name="declination.displayName" :price="declination.sellingPrice" :variants="declination.variants" :color="typeColor" />
        <form v-else class="declination__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <ProductTag :name="displayName" :price="form.sellingPrice" :variants="form.variants" :color="typeColor" />
                <FormSection>
                    <FormField :label="t('designs.declination.productName')" :error="errors.productName">
                        <input v-model="form.productName" type="text">
                    </FormField>
                    <FormField :label="t('designs.declination.sellingPrice')" :error="errors.sellingPrice">
                        <BaseMoneyField v-model="form.sellingPrice" class="declination__price" />
                    </FormField>
                    <FormField as="group" :label="t('designs.declination.variants')" :error="errors.variants">
                        <VariantPicker v-model="form.variants" :options="variantsOf(declination.gabarit.typeId)" />
                    </FormField>
                </FormSection>
                <FormActions v-if="dirty">
                    <BaseButton type="submit" variant="secondary" :loading="saving">{{ t('designs.declination.save') }}</BaseButton>
                </FormActions>
            </fieldset>
        </form>
    </article>
</template>

<style scoped>
.declination { display: flex; flex-direction: column; gap: var(--space-4); padding: var(--space-4); border: 0.0625rem solid var(--color-border); border-top: 0.25rem solid var(--color-warning); border-radius: var(--radius); background: var(--color-surface); }
.declination--ready { border-top-color: var(--color-accent); }
.declination__header { display: flex; align-items: center; gap: var(--space-2); }
.declination__gabarit { flex: 1; margin: 0; font-size: 1.1rem; }
.declination__checklist { display: flex; flex-direction: column; gap: var(--space-1); padding: var(--space-3); border-radius: var(--radius); background: var(--color-bg); }
.declination__adaptation { display: flex; align-items: center; gap: var(--space-2); cursor: pointer; }
.declination__adaptation--done { color: var(--color-muted); text-decoration: line-through; }
.declination__product-link { font-size: 0.875rem; white-space: nowrap; }
.declination__none { margin: 0; color: var(--color-subtle); font-size: 0.875rem; }
.declination__form { display: flex; flex-direction: column; gap: var(--space-4); }
.declination__price { max-width: 11rem; }
</style>
