<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import TypeSelect from './TypeSelect.vue';
import VariantsInput from './VariantsInput.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';
import { centsToEuros, eurosToCents } from '../../composables/useMoney.js';

const props = defineProps({
    // Product being edited, or null to create one.
    product: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const emptyForm = () => ({ reference: '', typeId: '', name: '', sellingPrice: '', buyingPrice: '0,00', variants: [] });
const form = reactive(emptyForm());
const errors = ref({});
const saving = ref(false);

const isEditing = computed(() => props.product !== null);
const { types } = useProductTypes();

// Products are shown as "{type} {name}" everywhere, e.g. type Print + name "Forêt" = "Print Forêt".
const displayName = computed(() => {
    const type = types.value.find((t) => t.id === form.typeId);
    return [type?.name, form.name.trim()].filter(Boolean).join(' ');
});

watch(() => props.product, (product) => {
    Object.assign(form, product
        ? {
            reference: product.reference,
            typeId: product.typeId ?? '',
            name: product.name,
            sellingPrice: centsToEuros(product.sellingPrice),
            buyingPrice: centsToEuros(product.buyingPrice),
            variants: [...product.variants],
        }
        : emptyForm());
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            reference: form.reference,
            typeId: form.typeId || null,
            name: form.name,
            sellingPrice: eurosToCents(form.sellingPrice) ?? -1,
            buyingPrice: eurosToCents(form.buyingPrice) ?? 0,
            variants: form.variants,
        });
        emit('saved', displayName.value);
        if (!isEditing.value) {
            Object.assign(form, emptyForm());
        }
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
    <form class="product-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="product-form__error" role="alert">{{ errors.form }}</p>

            <div class="product-form__row">
                <FormField label="Référence" :error="errors.reference">
                    <input v-model="form.reference" type="text" required>
                </FormField>
                <FormField label="Type" :error="errors.typeId">
                    <TypeSelect v-model="form.typeId" />
                </FormField>
            </div>

            <FormField label="Nom" :error="errors.name" :hint="displayName ? `Affiché « ${displayName} »` : 'Ex. « Forêt » pour un Print Forêt'">
                <input v-model="form.name" type="text" required>
            </FormField>

            <div class="product-form__row">
                <FormField label="Prix de vente (€)" :error="errors.sellingPrice">
                    <input v-model="form.sellingPrice" type="text" inputmode="decimal" required>
                </FormField>
                <FormField label="Prix d'achat (€)" :error="errors.buyingPrice" hint="0 si inconnu">
                    <input v-model="form.buyingPrice" type="text" inputmode="decimal">
                </FormField>
            </div>

            <FormField label="Variantes" :error="errors.variants" hint="Couleur, taille, design… Laisser vide pour un produit unique.">
                <VariantsInput v-model="form.variants" />
            </FormField>

            <div class="product-form__actions">
                <BaseButton type="submit" :loading="saving">{{ isEditing ? 'Enregistrer' : 'Ajouter le produit' }}</BaseButton>
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-form { display: flex; flex-direction: column; gap: var(--space-3); }
.product-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.product-form__actions { display: flex; gap: var(--space-2); }
.product-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
