<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseModal from '../ui/BaseModal.vue';
import ProductForm from '../products/ProductForm.vue';
import { useProducts } from '../../composables/useProducts.js';
import { useToast } from '../../composables/useToast.js';
import { X } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import FormActions from '../ui/FormActions.vue';
import FormField from '../ui/FormField.vue';
import FormSection from '../ui/FormSection.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import PurchaseLinePicker from './PurchaseLinePicker.vue';
import SupplierSelect from './SupplierSelect.vue';
import { landedCosts } from '../../composables/usePurchasing.js';

const { t } = useI18n();

const props = defineProps({
    order: { type: Object, default: null },
    products: { type: Array, required: true },
    suppliers: { type: Array, required: true },
    saveSupplier: { type: Function, required: true },
    submit: { type: Function, required: true },
    reloadProducts: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const today = () => new Date().toLocaleDateString('sv-SE');
const blank = () => ({ supplierId: '', orderedOn: today(), supplierReference: '', lines: [], discount: 0, deliveryFees: 0 });
const form = reactive(blank());
const errors = ref({});
const saving = ref(false);
const received = computed(() => props.order?.status === 'received');
const picker = ref(null);
const productOpen = ref(false);
const { create: createProduct } = useProducts();
const toast = useToast();
let createdId = null;

async function submitProduct(payload) {
    createdId = (await createProduct(payload)).id;
}

async function onProductCreated(name) {
    productOpen.value = false;
    toast.success(t('purchasing.form.productCreated', { name }));
    await props.reloadProducts();
    picker.value?.choose(createdId);
}

watch(() => props.order, (order) => {
    Object.assign(form, order
        ? {
            supplierId: order.supplier.id,
            orderedOn: order.orderedOn,
            supplierReference: order.supplierReference ?? '',
            lines: order.lines.map(({ productId, variant, label, orderedQuantity, totalPrice, receivedQuantity }) => ({ productId, variant, label, quantity: orderedQuantity, totalPrice, received: receivedQuantity })),
            discount: order.discount,
            deliveryFees: order.deliveryFees,
        }
        : blank());
    errors.value = {};
}, { immediate: true });

const subtotal = computed(() => form.lines.reduce((sum, line) => sum + (line.totalPrice ?? 0), 0));
const total = computed(() => subtotal.value - (form.discount ?? 0) + (form.deliveryFees ?? 0));
const landed = computed(() => landedCosts(form.lines, form.discount, form.deliveryFees));
const unitCost = (line, index) => (line.quantity > 0 ? Math.round(landed.value[index] / line.quantity) : 0);

function addLine(line) {
    const existing = form.lines.find((l) => l.productId === line.productId && l.variant === line.variant);
    if (existing) {
        existing.quantity += line.quantity;
        existing.totalPrice = (existing.totalPrice ?? 0) + line.totalPrice;
        if (received.value) existing.received = (existing.received ?? 0) + line.quantity;
        return;
    }
    form.lines.push(received.value ? { ...line, received: line.quantity } : line);
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            supplierId: form.supplierId,
            orderedOn: form.orderedOn,
            supplierReference: form.supplierReference.trim() || null,
            lines: form.lines.map(({ productId, variant, quantity, totalPrice, received: got }) => ({ productId, variant, quantity: quantity ?? 0, totalPrice: totalPrice ?? 0, received: received.value ? got ?? -1 : null })),
            discount: form.discount ?? 0,
            deliveryFees: form.deliveryFees ?? 0,
        });
        emit('saved');
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
    <form class="supplier-order-form" novalidate @submit.prevent="onSubmit">
        <fieldset class="form-lock" :disabled="saving">
            <p v-if="errors.form" class="supplier-order-form__error" role="alert">{{ errors.form }}</p>

            <FormSection>
                <FormField as="group" :label="t('purchasing.form.supplier')" :error="errors.supplierId">
                    <SupplierSelect v-model="form.supplierId" :suppliers="suppliers" :save="saveSupplier" />
                </FormField>
                <FormField as="group" :label="t('purchasing.form.orderedOn')" :error="errors.orderedOn">
                    <div class="supplier-order-form__date"><BaseDatePicker v-model="form.orderedOn" :aria-label="t('purchasing.form.orderedOn')" /></div>
                </FormField>
                <FormField :label="t('purchasing.form.supplierReference')" :error="errors.supplierReference" :hint="t('purchasing.form.supplierReferenceHint')" optional>
                    <input v-model="form.supplierReference" type="text" maxlength="100" autocomplete="off" class="supplier-order-form__reference">
                </FormField>
            </FormSection>

            <FormSection :title="t('purchasing.form.orderedProducts')" :description="received ? t('purchasing.form.receivedHint') : null">
                <table v-if="form.lines.length" class="supplier-order-form__lines">
                    <thead class="supplier-order-form__head">
                        <tr>
                            <th>{{ t('purchasing.form.product') }}</th>
                            <th>{{ t('purchasing.form.quantity') }}</th>
                            <th v-if="received">{{ t('purchasing.form.received') }}</th>
                            <th>{{ t('purchasing.form.totalPrice') }}</th>
                            <th>{{ t('purchasing.form.unitCost') }}</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, index) in form.lines" :key="`${line.productId}|${line.variant ?? ''}`" :class="['supplier-order-form__line', { 'supplier-order-form__line--received': received }]">
                            <td class="supplier-order-form__label">
                                {{ line.label }}
                                <span v-if="errors[`lines[${index}].quantity`] || errors[`lines[${index}].totalPrice`] || errors[`lines[${index}].received`]" class="supplier-order-form__line-error" role="alert">
                                    {{ errors[`lines[${index}].quantity`] ?? errors[`lines[${index}].totalPrice`] ?? errors[`lines[${index}].received`] }}
                                </span>
                            </td>
                            <td><BaseNumberField v-model="line.quantity" :min="1" :label="t('purchasing.form.quantityOf', { label: line.label })" /></td>
                            <td v-if="received"><BaseNumberField v-model="line.received" :min="0" :label="t('purchasing.form.receivedOf', { label: line.label })" /></td>
                            <td class="supplier-order-form__price"><BaseMoneyField v-model="line.totalPrice" :aria-label="t('purchasing.form.totalPriceOf', { label: line.label })" /></td>
                            <td class="supplier-order-form__unit-cost"><span aria-hidden="true">{{ t('purchasing.form.unitCost') }}</span> <MoneyAmount :cents="unitCost(line, index)" /></td>
                            <td><IconButton :icon="X" :label="t('purchasing.form.remove', { label: line.label })" @click="form.lines.splice(index, 1)" /></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="supplier-order-form__subtotal">
                            <td>{{ t('purchasing.form.products') }}</td>
                            <td><MoneyAmount :cents="subtotal" /></td>
                        </tr>
                    </tfoot>
                </table>
                <p v-if="errors.lines" class="supplier-order-form__line-error" role="alert">{{ errors.lines }}</p>
                <PurchaseLinePicker ref="picker" :products="products" @add="addLine" @create="productOpen = true" />
            </FormSection>

            <FormSection :title="t('purchasing.form.extras')" :description="t('purchasing.form.extrasHint')">
                <FormField :label="t('purchasing.form.discount')" :error="errors.discount">
                    <BaseMoneyField v-model="form.discount" class="supplier-order-form__money" />
                </FormField>
                <FormField :label="t('purchasing.form.deliveryFees')" :error="errors.deliveryFees">
                    <BaseMoneyField v-model="form.deliveryFees" class="supplier-order-form__money" />
                </FormField>
                <p class="supplier-order-form__total">{{ t('purchasing.form.totalPaid') }} <strong><MoneyAmount :cents="total" /></strong></p>
            </FormSection>

            <FormActions sticky>
                <BaseButton variant="ghost" @click="emit('cancel')">{{ t('purchasing.form.cancel') }}</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ order ? t('purchasing.form.save') : t('purchasing.form.place') }}</BaseButton>
            </FormActions>
        </fieldset>
        <BaseModal v-model:open="productOpen" :title="t('purchasing.form.newProductTitle')">
            <ProductForm v-if="productOpen" :product="null" :submit="submitProduct" @saved="onProductCreated" @cancel="productOpen = false" />
        </BaseModal>
    </form>
</template>

<style scoped>
.supplier-order-form { display: flex; flex-direction: column; gap: var(--space-5); }
.supplier-order-form__date,
.supplier-order-form__money { max-width: 11rem; }
.supplier-order-form .supplier-order-form__reference { max-width: 16rem; }
.supplier-order-form__lines,
.supplier-order-form__lines tbody,
.supplier-order-form__lines tfoot { display: block; }
.supplier-order-form__lines { font-size: 0.875rem; }

.supplier-order-form__head {
    position: absolute;
    width: 0.0625rem;
    height: 0.0625rem;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
}

.supplier-order-form__line {
    display: grid;
    grid-template-columns: 7rem 7.5rem minmax(0, 1fr) auto;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) 0;
    border-bottom: 0.0625rem solid var(--color-border);
}

.supplier-order-form__line--received { grid-template-columns: 7rem 7rem 7.5rem minmax(0, 1fr) auto; }
.supplier-order-form__line td { padding: 0; }
.supplier-order-form__label { grid-column: 1 / -1; color: var(--color-ink); font-weight: 500; }

.supplier-order-form__price :deep(.money-field__input) {
    width: 100%;
    min-height: 2.375rem;
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.supplier-order-form__price :deep(.money-field__input:focus) { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 0.1875rem var(--color-accent-soft); }
.supplier-order-form__unit-cost { color: var(--color-muted); text-align: right; font-variant-numeric: tabular-nums; }
.supplier-order-form__unit-cost :deep(.money) { margin-left: var(--space-1); color: var(--color-ink); }

.supplier-order-form__subtotal {
    display: flex;
    justify-content: space-between;
    padding: var(--space-2) 0;
    color: var(--color-muted);
    font-variant-numeric: tabular-nums;
}

.supplier-order-form__subtotal td { padding: 0; }
.supplier-order-form__line-error { display: block; margin: 0; color: var(--color-danger); font-size: 0.8125rem; }

.supplier-order-form__total {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin: 0;
    padding-top: var(--space-3);
    border-top: 0.0625rem solid var(--color-border);
    font-size: 0.875rem;
    font-weight: 500;
}

.supplier-order-form__total strong { font-family: var(--font-display); font-size: 1.2rem; font-weight: 400; color: var(--color-ink); font-variant-numeric: tabular-nums; }
.supplier-order-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
</style>
