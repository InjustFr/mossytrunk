<script setup>
import { reactive, ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import VariantsInput from './VariantsInput.vue';
import { eurosToCents } from '../../composables/useMoney.js';
import { useProductTypes } from '../../composables/useProductTypes.js';

// Only the ticked changes are applied to the selected products.
const props = defineProps({
    count: { type: Number, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);
const { types } = useProductTypes();

const form = reactive({
    changeSellingPrice: false, sellingPrice: '',
    changeBuyingPrice: false, buyingPrice: '',
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
            sellingPrice: form.changeSellingPrice ? eurosToCents(form.sellingPrice) ?? -1 : null,
            buyingPrice: form.changeBuyingPrice ? eurosToCents(form.buyingPrice) ?? -1 : null,
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
            <p class="product-batch-form__intro">Les changements cochés s'appliquent aux {{ count }} produit(s) sélectionné(s).</p>
            <p v-if="errors.form" class="product-batch-form__error" role="alert">{{ errors.form }}</p>

            <div class="product-batch-form__option">
                <label class="product-batch-form__toggle"><input v-model="form.changeSellingPrice" type="checkbox"> Changer le prix de vente</label>
                <FormField v-if="form.changeSellingPrice" label="Nouveau prix de vente (€)" :error="errors.sellingPrice">
                    <input v-model="form.sellingPrice" type="text" inputmode="decimal">
                </FormField>
            </div>

            <div class="product-batch-form__option">
                <label class="product-batch-form__toggle"><input v-model="form.changeBuyingPrice" type="checkbox"> Changer le prix d'achat</label>
                <FormField v-if="form.changeBuyingPrice" label="Nouveau prix d'achat (€)" :error="errors.buyingPrice">
                    <input v-model="form.buyingPrice" type="text" inputmode="decimal">
                </FormField>
            </div>

            <div class="product-batch-form__option">
                <label class="product-batch-form__toggle"><input v-model="form.changeType" type="checkbox"> Changer le type</label>
                <FormField v-if="form.changeType" label="Nouveau type">
                    <select v-model="form.typeId">
                        <option value="">Sans type</option>
                        <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                </FormField>
            </div>

            <FormField as="group" label="Variantes à ajouter" :error="errors.addVariants" hint="Ignorées si le produit les a déjà.">
                <VariantsInput v-model="form.addVariants" input-label="Variante à ajouter" />
            </FormField>
            <FormField as="group" label="Variantes à retirer">
                <VariantsInput v-model="form.removeVariants" input-label="Variante à retirer" />
            </FormField>

            <div class="product-batch-form__actions">
                <BaseButton type="submit" :loading="saving">Appliquer à {{ count }} produit(s)</BaseButton>
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-batch-form { display: flex; flex-direction: column; gap: var(--space-4); }
.product-batch-form__intro { margin: 0; color: var(--color-muted); }
.product-batch-form__option { display: flex; flex-direction: column; gap: var(--space-2); }
.product-batch-form__toggle { display: flex; align-items: center; gap: var(--space-2); font-weight: 600; cursor: pointer; }
.product-batch-form__toggle input { accent-color: var(--color-accent); }
.product-batch-form__actions { display: flex; gap: var(--space-2); }
.product-batch-form__error { margin: 0; padding: var(--space-2) var(--space-3); background: var(--color-danger-soft); color: var(--color-danger); border-radius: var(--radius); }
</style>
