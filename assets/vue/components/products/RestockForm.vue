<script setup>
import { computed, reactive, ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

const props = defineProps({
    product: { type: Object, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const form = reactive({ variant: props.product.variants[0] ?? '', quantity: 1, totalPaid: null });
const errors = ref({});
const saving = ref(false);

const variantOptions = computed(() => props.product.variants.map((variant) => ({ value: variant, label: variant })));
const unitCost = computed(() => (form.quantity > 0 && form.totalPaid !== null ? Math.round(form.totalPaid / form.quantity) : null));

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            productId: props.product.id,
            variant: form.variant || null,
            quantity: form.quantity ?? 0,
            totalPaid: form.totalPaid ?? -1,
        });
        emit('saved', { quantity: form.quantity, variant: form.variant || null });
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
    <form class="restock-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="restock-form__error" role="alert">{{ errors.form }}</p>

            <FormField v-if="product.variants.length" as="group" label="Variante" :error="errors.variant">
                <BaseSelect v-model="form.variant" :options="variantOptions" aria-label="Variante" />
            </FormField>

            <div class="restock-form__row">
                <FormField as="group" label="Quantité reçue" :error="errors.quantity">
                    <BaseNumberField v-model="form.quantity" :min="1" label="Quantité reçue" />
                </FormField>
                <FormField label="Prix payé au total (€)" :error="errors.totalPaid">
                    <BaseMoneyField v-model="form.totalPaid" />
                </FormField>
            </div>

            <p class="restock-form__unit">
                Coût unitaire
                <strong v-if="unitCost !== null"><MoneyAmount :cents="unitCost" /></strong>
                <span v-else>—</span>
            </p>

            <div class="restock-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">Ajouter au stock</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.restock-form { display: flex; flex-direction: column; gap: var(--space-3); }
.restock-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.restock-form__unit { display: flex; justify-content: space-between; margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-bg); color: var(--color-muted); }
.restock-form__unit strong { color: var(--color-ink); }
.restock-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.restock-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
</style>
