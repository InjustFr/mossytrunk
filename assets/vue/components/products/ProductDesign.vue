<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Palette } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FieldError from '../ui/FieldError.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import { useFormSubmit } from '../../composables/useFormSubmit.js';

const props = defineProps({
    product: { type: Object, required: true },
    design: { type: Object, default: null },
    gabarits: { type: Array, required: true },
    designs: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['designed']);
const { t } = useI18n();

const mode = ref(null);
const form = reactive({ gabaritId: '', designId: '' });
const { saving, errors, run } = useFormSubmit();

const gabaritOptions = computed(() => props.gabarits.map((g) => ({ value: g.id, label: g.typeName ? `${g.name} (${g.typeName})` : g.name })));
const designOptions = computed(() => props.designs.map((d) => ({ value: d.id, label: d.name })));
const suggestedGabarit = computed(() => props.gabarits.find((g) => g.typeId && g.typeId === props.product.typeId)?.id ?? '');

watch(mode, () => {
    Object.assign(form, { gabaritId: suggestedGabarit.value, designId: '' });
    errors.value = {};
});

function onSubmit() {
    return run(async () => {
        const { designId } = await props.submit({ gabaritId: form.gabaritId, designId: mode.value === 'attach' ? form.designId : null });
        emit('designed', designId);
    });
}
</script>

<template>
    <div class="product-design">
        <a v-if="design" :href="`/designs/${design.id}`" class="product-design__link">
            <Palette size="1.25rem" aria-hidden="true" />
            <span>
                <strong>{{ design.name }}</strong>
                <span class="product-design__hint">{{ t('products.design.openHint') }}</span>
            </span>
        </a>
        <template v-else>
            <p class="product-design__intro">{{ t('products.design.intro') }}</p>
            <div v-if="mode === null" class="product-design__choices">
                <BaseButton variant="secondary" :disabled="gabarits.length === 0" @click="mode = 'create'">{{ t('products.design.create') }}</BaseButton>
                <BaseButton variant="ghost" :disabled="gabarits.length === 0 || designs.length === 0" @click="mode = 'attach'">{{ t('products.design.attach') }}</BaseButton>
            </div>
            <i18n-t v-if="gabarits.length === 0" keypath="products.design.noGabarit" tag="p" class="product-design__hint" scope="global">
                <template #link><a href="/designs">{{ t('products.design.designsPage') }}</a></template>
            </i18n-t>
            <form v-if="mode" class="product-design__form" novalidate @submit.prevent="onSubmit">
                <FieldError v-if="errors.form">{{ errors.form }}</FieldError>
                <FormSection>
                    <FormField v-if="mode === 'attach'" as="group" :label="t('products.design.design')" :error="errors.designId">
                        <BaseSelect v-model="form.designId" :options="designOptions" :aria-label="t('products.design.design')" :placeholder="t('products.design.chooseDesign')" />
                    </FormField>
                    <FormField as="group" :label="t('products.design.gabarit')" :error="errors.gabaritId">
                        <BaseSelect v-model="form.gabaritId" :options="gabaritOptions" :aria-label="t('products.design.gabaritLabel')" :placeholder="t('products.design.chooseGabarit')" />
                    </FormField>
                </FormSection>
                <FormActions>
                    <BaseButton variant="ghost" @click="mode = null">{{ t('common.cancel') }}</BaseButton>
                    <BaseButton type="submit" :loading="saving">{{ t(mode === 'create' ? 'products.design.createSubmit' : 'products.design.attachSubmit') }}</BaseButton>
                </FormActions>
            </form>
        </template>
    </div>
</template>

<style scoped>
.product-design { display: flex; flex-direction: column; gap: var(--space-3); }
.product-design__link { display: flex; align-items: flex-start; gap: var(--space-3); padding: var(--space-3); border: 0.0625rem solid var(--color-border); border-radius: var(--radius); color: var(--color-text); text-decoration: none; transition: border-color var(--transition); }
.product-design__link:hover { border-color: var(--color-ink); }
.product-design__link svg { flex: none; color: var(--color-accent); }
.product-design__link strong { display: block; font-family: var(--font-display); font-size: 1.2rem; font-weight: 400; }
.product-design__intro { margin: 0; color: var(--color-muted); font-size: var(--font-size-md); }
.product-design__hint { display: block; color: var(--color-muted); font-size: var(--font-size-sm); }
.product-design__choices { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.product-design__form { display: flex; flex-direction: column; gap: var(--space-4); }
</style>
