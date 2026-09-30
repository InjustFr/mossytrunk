<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
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
            <FormSection>
                <FormField as="group" :label="t('orders.form.date')">
                    <BaseDatePicker v-model="draft.placedAt.value" with-time />
                </FormField>
                <Transition name="order-form__fade">
                    <FormField v-if="draft.preview.value" as="group" :label="t('orders.form.event')">
                        <p v-if="draft.preview.value.event" class="order-form__fact">{{ draft.preview.value.event.name }}</p>
                        <p v-else class="order-form__fact order-form__fact--missing" role="alert">
                            {{ t('orders.form.noEvent') }} <a href="/events">{{ t('orders.form.createEvent') }}</a>
                        </p>
                    </FormField>
                </Transition>
            </FormSection>

            <FormSection :title="t('orders.form.items')">
                <OrderLinePicker :products="products" @add="draft.add" />
                <OrderDraftLines
                    :lines="draft.lines"
                    :products="products"
                    @quantity="draft.setQuantity"
                    @remove="draft.remove"
                />
            </FormSection>

            <p v-if="error || draft.previewError.value" class="order-form__error" role="alert">{{ error ?? draft.previewError.value }}</p>

            <div class="order-form__checkout">
                <OrderTotals
                    v-if="draft.preview.value"
                    :subtotal="draft.preview.value.subtotal"
                    :discounts="draft.preview.value.discounts"
                    :total="draft.preview.value.total"
                />
                <FormActions sticky>
                    <BaseButton type="submit" :loading="saving" :disabled="draft.lines.length === 0">{{ t('orders.form.submit') }}</BaseButton>
                </FormActions>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.order-form { flex: 1; display: flex; flex-direction: column; gap: var(--space-5); }
.order-form__checkout { display: flex; flex-direction: column; gap: var(--space-4); margin-top: auto; }

.order-form__fact { margin: 0; padding-top: 0.5625rem; font-size: 0.875rem; font-weight: 500; color: var(--color-ink); }
.order-form__fact--missing { font-weight: 400; color: var(--color-danger); }
.order-form__fact--missing a { color: inherit; }

.order-form__error {
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}

.order-form__fade-enter-active,
.order-form__fade-leave-active { transition: opacity var(--transition); }
.order-form__fade-enter-from,
.order-form__fade-leave-to { opacity: 0; }
</style>
