<script setup>
import { ref } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import FormField from '../ui/FormField.vue';
import OrderDraftLines from './OrderDraftLines.vue';
import OrderLinePicker from './OrderLinePicker.vue';
import OrderTotals from './OrderTotals.vue';
import { useOrderDraft } from '../../composables/useOrderDraft.js';

const props = defineProps({
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['placed']);

const draft = useOrderDraft();
const saving = ref(false);
const error = ref(null);

async function onSubmit() {
    if (draft.lines.length === 0) {
        error.value = 'Ajoutez au moins un produit.';
        return;
    }
    saving.value = true;
    error.value = null;
    try {
        const order = await props.submit(draft.payload());
        draft.reset();
        emit('placed', order);
    } catch (e) {
        error.value = e.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="order-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <h2 class="order-form__title">Nouvelle commande</h2>

            <FormField label="Date">
                <input v-model="draft.placedAt.value" type="datetime-local" required>
            </FormField>

            <p v-if="draft.preview.value?.event" class="order-form__event">
                Rattachée à <strong>{{ draft.preview.value.event.name }}</strong>
            </p>
            <p v-else-if="draft.preview.value" class="order-form__event order-form__event--missing" role="alert">
                Aucun événement à cette date. <a href="/evenements">Créer un événement</a>
            </p>

            <OrderLinePicker :products="products" @add="draft.add" />

            <OrderDraftLines
                v-if="draft.lines.length > 0"
                :lines="draft.lines"
                :products="products"
                @quantity="draft.setQuantity"
                @remove="draft.remove"
            />

            <OrderTotals
                v-if="draft.preview.value"
                :subtotal="draft.preview.value.subtotal"
                :discounts="draft.preview.value.discounts"
                :total="draft.preview.value.total"
            />

            <p v-if="error || draft.previewError.value" class="order-form__error" role="alert">{{ error ?? draft.previewError.value }}</p>

            <BaseButton type="submit" :loading="saving" :disabled="draft.lines.length === 0">Enregistrer la commande</BaseButton>
        </fieldset>
    </form>
</template>

<style scoped>
.order-form { display: flex; flex-direction: column; gap: var(--space-3); }
.order-form__title { margin: 0; }

.order-form__event {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-accent-soft);
    font-size: 0.9rem;
}

.order-form__event--missing { background: var(--color-danger-soft); color: var(--color-danger); }

.order-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
</style>
