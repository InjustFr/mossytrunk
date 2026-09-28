<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import ProductPicker from './ProductPicker.vue';
import TypePicker from './TypePicker.vue';
import { centsToEuros, eurosToCents, formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    rule: { type: Object, default: null },
    products: { type: Array, required: true },
    types: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const emptyForm = () => ({ name: '', bundleSize: 3, bundlePrice: '', typeIds: [], productIds: [] });
const form = reactive(emptyForm());
const errors = ref({});
const saving = ref(false);
const isEditing = computed(() => props.rule !== null);

watch(() => props.rule, (rule) => {
    Object.assign(form, rule
        ? { name: rule.name, bundleSize: rule.bundleSize, bundlePrice: centsToEuros(rule.bundlePrice), typeIds: rule.types.map((t) => t.id), productIds: rule.products.map((p) => p.id) }
        : emptyForm());
    errors.value = {};
}, { immediate: true });

// Helps the user check the bundle is actually cheaper: price of N units bought separately.
const regularPrice = computed(() => {
    const prices = props.products
        .filter((p) => form.productIds.includes(p.id) || (p.typeId !== null && form.typeIds.includes(p.typeId)))
        .map((p) => p.sellingPrice);
    if (prices.length === 0 || !form.bundleSize) {
        return null;
    }
    return { min: Math.min(...prices) * form.bundleSize, max: Math.max(...prices) * form.bundleSize };
});

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            name: form.name,
            productIds: form.productIds,
            typeIds: form.typeIds,
            bundleSize: Number(form.bundleSize) || 0,
            bundlePrice: eurosToCents(form.bundlePrice) ?? 0,
        });
        emit('saved', form.name);
        if (!isEditing.value) {
            Object.assign(form, emptyForm());
        }
    } catch (error) {
        errors.value = Object.keys(error.fieldErrors ?? {}).length ? error.fieldErrors : { form: error.message };
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="discount-rule-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="discount-rule-form__error" role="alert">{{ errors.form }}</p>

            <FormField label="Nom" :error="errors.name" hint="Affiché sur les commandes, ex. « 3 stickers pour 10 € »">
                <input v-model="form.name" type="text">
            </FormField>

            <div class="discount-rule-form__row">
                <FormField label="Articles par lot" :error="errors.bundleSize">
                    <input v-model.number="form.bundleSize" type="number" min="2" step="1">
                </FormField>
                <FormField label="Prix du lot (€)" :error="errors.bundlePrice">
                    <input v-model="form.bundlePrice" type="text" inputmode="decimal">
                </FormField>
            </div>

            <p v-if="regularPrice" class="discount-rule-form__hint">
                Prix normal de {{ form.bundleSize }} articles :
                <strong>{{ formatCents(regularPrice.min) }}</strong>
                <template v-if="regularPrice.max !== regularPrice.min"> à <strong>{{ formatCents(regularPrice.max) }}</strong></template>
            </p>

            <FormField as="group" label="Types concernés" :error="errors.typeIds" hint="Tous les produits de ces types, même ceux ajoutés plus tard. Les types peuvent être mélangés dans un lot.">
                <TypePicker v-model="form.typeIds" :types="types" />
            </FormField>

            <FormField as="group" label="Produits concernés en plus" :error="errors.productIds" hint="Toutes les variantes d'un produit sont concernées.">
                <ProductPicker v-model="form.productIds" :products="products" :covered-type-ids="form.typeIds" />
            </FormField>

            <div class="discount-rule-form__actions">
                <BaseButton type="submit" :loading="saving">{{ isEditing ? 'Enregistrer' : 'Créer la remise' }}</BaseButton>
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.discount-rule-form { display: flex; flex-direction: column; gap: var(--space-3); }
.discount-rule-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.discount-rule-form__actions { display: flex; gap: var(--space-2); }
.discount-rule-form__hint { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.discount-rule-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
