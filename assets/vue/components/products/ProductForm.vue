<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import FormField from '../ui/FormField.vue';
import TypeSelect from './TypeSelect.vue';
import VariantsInput from './VariantsInput.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';

const props = defineProps({
    product: { type: Object, default: null },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const emptyForm = () => ({ typeId: '', name: '', sellingPrice: null, variants: [], lowStockThreshold: 10 });
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
            typeId: product.typeId ?? '',
            name: product.name,
            sellingPrice: product.sellingPrice,
            variants: [...product.variants],
            lowStockThreshold: product.lowStockThreshold,
        }
        : emptyForm());
    errors.value = {};
}, { immediate: true });

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            typeId: form.typeId || null,
            name: form.name,
            sellingPrice: form.sellingPrice ?? -1,
            variants: form.variants,
            lowStockThreshold: form.lowStockThreshold ?? 0,
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

            <p v-if="isEditing" class="product-form__reference">Référence <strong>{{ product.reference }}</strong></p>

            <FormField as="group" label="Type" :error="errors.typeId">
                <TypeSelect v-model="form.typeId" />
            </FormField>

            <FormField label="Nom" :error="errors.name" :hint="displayName ? `Affiché « ${displayName} » — la référence est générée automatiquement` : 'Ex. « Forêt » pour un Print Forêt'">
                <input v-model="form.name" type="text" required>
            </FormField>

            <div class="product-form__row">
                <FormField label="Prix de vente (€)" :error="errors.sellingPrice">
                    <BaseMoneyField v-model="form.sellingPrice" />
                </FormField>
                <div class="product-form__cost">
                    <span class="product-form__cost-label">Prix d'achat</span>
                    <strong v-if="isEditing && product.buyingPrice > 0"><MoneyAmount :cents="product.buyingPrice" /></strong>
                    <span v-else class="product-form__cost-unknown">Pas encore acheté</span>
                    <span class="product-form__cost-hint">Mis à jour par les réapprovisionnements et les commandes fournisseurs.</span>
                </div>
            </div>

            <FormField as="group" label="Alerte stock bas" :error="errors.lowStockThreshold" hint="Le produit est signalé quand son stock (par variante) descend à ce seuil.">
                <BaseNumberField v-model="form.lowStockThreshold" :min="0" label="Alerte stock bas" />
            </FormField>

            <FormField as="group" label="Variantes" :error="errors.variants" hint="Couleur, taille, design… Laisser vide pour un produit unique.">
                <VariantsInput v-model="form.variants" />
            </FormField>

            <div class="product-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ isEditing ? 'Enregistrer' : 'Ajouter le produit' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.product-form { display: flex; flex-direction: column; gap: var(--space-3); }
.product-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.product-form__cost { display: flex; flex-direction: column; gap: var(--space-1); }
.product-form__cost-label { font-weight: 500; }
.product-form__cost-unknown { color: var(--color-muted); }
.product-form__cost-hint { color: var(--color-muted); font-size: 0.8rem; }
.product-form__reference { margin: 0; color: var(--color-muted); font-size: 0.9rem; }
.product-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.product-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
