<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Pencil } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import EmptyState from '../ui/EmptyState.vue';
import FormField from '../ui/FormField.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import TypeSelect from '../products/TypeSelect.vue';
import VariantPicker from '../products/VariantPicker.vue';
import VariantsInput from '../products/VariantsInput.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';

const props = defineProps({
    gabarits: { type: Array, required: true },
    save: { type: Function, required: true },
});
const emit = defineEmits(['saved']);
const { t } = useI18n();
const { variantsOf } = useProductTypes();

const empty = () => ({ name: '', typeId: '', sellingPrice: null, variants: [], adaptations: [] });
const editingId = ref(null);
const form = reactive(empty());
const errors = ref({});
const saving = ref(false);

function edit(gabarit) {
    editingId.value = gabarit?.id ?? null;
    Object.assign(form, gabarit
        ? { name: gabarit.name, typeId: gabarit.typeId, sellingPrice: gabarit.sellingPrice, variants: [...gabarit.variants], adaptations: [...gabarit.adaptations] }
        : empty());
    errors.value = {};
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.save(editingId.value, { ...form, typeId: form.typeId || null, sellingPrice: form.sellingPrice ?? -1 });
        emit('saved', form.name);
        edit(null);
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { name: error.message };
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="gabarit-manager">
        <EmptyState v-if="gabarits.length === 0">{{ t('designs.gabarits.empty') }}</EmptyState>
        <ul v-else class="gabarit-manager__list">
            <li v-for="gabarit in gabarits" :key="gabarit.id" :class="['gabarit-manager__item', { 'gabarit-manager__item--editing': gabarit.id === editingId }]">
                <div class="gabarit-manager__summary">
                    <strong>{{ gabarit.name }}</strong>
                    <span class="gabarit-manager__meta">
                        {{ gabarit.typeName }}, <MoneyAmount :cents="gabarit.sellingPrice" />
                        <template v-if="gabarit.variants.length">, {{ gabarit.variants.join(', ') }}</template>
                    </span>
                    <span v-if="gabarit.adaptations.length" class="gabarit-manager__meta">{{ t('designs.gabarits.toAdapt', { adaptations: gabarit.adaptations.join(', ') }) }}</span>
                </div>
                <IconButton :icon="Pencil" :label="t('designs.gabarits.edit', { name: gabarit.name })" @click="edit(gabarit)" />
            </li>
        </ul>

        <form class="gabarit-manager__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <h3 class="gabarit-manager__title">{{ editingId ? t('designs.gabarits.editTitle') : t('designs.gabarits.newTitle') }}</h3>
                <FormField :label="t('designs.gabarits.name')" :error="errors.name" :hint="t('designs.gabarits.nameHint')">
                    <input v-model="form.name" type="text">
                </FormField>
                <FormField as="group" :label="t('designs.gabarits.type')" :error="errors.typeId">
                    <TypeSelect v-model="form.typeId" />
                </FormField>
                <FormField :label="t('designs.gabarits.sellingPrice')" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" />
                </FormField>
                <FormField as="group" :label="t('designs.gabarits.variants')" :error="errors.variants">
                    <VariantPicker v-model="form.variants" :options="variantsOf(form.typeId)" />
                </FormField>
                <FormField as="group" :label="t('designs.gabarits.adaptations')" :error="errors.adaptations" :hint="t('designs.gabarits.adaptationsHint')">
                    <VariantsInput v-model="form.adaptations" :input-label="t('designs.gabarits.newAdaptation')" :placeholder="t('designs.gabarits.adaptationPlaceholder')" :item-label="t('designs.gabarits.adaptationItem')" />
                </FormField>
                <div class="gabarit-manager__actions">
                    <BaseButton v-if="editingId" variant="ghost" @click="edit(null)">{{ t('designs.gabarits.cancel') }}</BaseButton>
                    <BaseButton type="submit" :loading="saving">{{ editingId ? t('designs.gabarits.save') : t('designs.gabarits.add') }}</BaseButton>
                </div>
            </fieldset>
        </form>
    </div>
</template>

<style scoped>
.gabarit-manager { display: flex; flex-direction: column; gap: var(--space-4); }
.gabarit-manager__list { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.gabarit-manager__item { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 0.0625rem solid var(--color-border); }
.gabarit-manager__item--editing { background: var(--color-accent-soft); }
.gabarit-manager__summary { display: flex; flex-direction: column; }
.gabarit-manager__meta { color: var(--color-muted); font-size: 0.85rem; }
.gabarit-manager__form { display: flex; flex-direction: column; gap: var(--space-3); }
.gabarit-manager__title { margin: 0; font-size: 1rem; }
.gabarit-manager__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
