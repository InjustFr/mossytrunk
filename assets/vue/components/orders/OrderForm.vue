<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
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

const { t } = useI18n();
const draft = useOrderDraft();
const saving = ref(false);
const error = ref(null);

async function onSubmit() {
    if (draft.lines.length === 0) {
        error.value = t('orders.form.noLines');
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
            <FormField as="group" :label="t('orders.form.date')">
                <BaseDatePicker v-model="draft.placedAt.value" with-time />
            </FormField>

            <p v-if="draft.preview.value?.event" class="order-form__event">
                <i18n-t keypath="orders.form.attachedTo" scope="global"><template #event><strong>{{ draft.preview.value.event.name }}</strong></template></i18n-t>
            </p>
            <p v-else-if="draft.preview.value" class="order-form__event order-form__event--missing" role="alert">
                {{ t('orders.form.noEvent') }} <a href="/events">{{ t('orders.form.createEvent') }}</a>
            </p>

            <OrderLinePicker :products="products" @add="draft.add" />

            <OrderDraftLines
                v-if="draft.lines.length > 0"
                :lines="draft.lines"
                :products="products"
                @quantity="draft.setQuantity"
                @remove="draft.remove"
            />

            <p v-if="error || draft.previewError.value" class="order-form__error" role="alert">{{ error ?? draft.previewError.value }}</p>

            <footer class="order-form__footer">
                <OrderTotals
                    v-if="draft.preview.value"
                    :subtotal="draft.preview.value.subtotal"
                    :discounts="draft.preview.value.discounts"
                    :total="draft.preview.value.total"
                />
                <div class="order-form__actions">
                    <BaseButton type="submit" :loading="saving" :disabled="draft.lines.length === 0">{{ t('orders.form.submit') }}</BaseButton>
                </div>
            </footer>
        </fieldset>
    </form>
</template>

<style scoped>
.order-form { flex: 1; display: flex; flex-direction: column; gap: var(--space-3); }
.order-form__actions { display: flex; justify-content: flex-end; }

.order-form__footer {
    position: sticky;
    bottom: calc(-1 * var(--space-5));
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    margin: auto calc(-1 * var(--space-5)) calc(-1 * var(--space-5));
    padding: var(--space-4) var(--space-5);
    border-top: 0.0625rem solid var(--color-border);
    background: var(--color-surface);
}

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
