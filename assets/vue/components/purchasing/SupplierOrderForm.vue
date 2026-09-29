<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { X } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseDatePicker from '../ui/BaseDatePicker.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import FormField from '../ui/FormField.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import PurchaseLinePicker from './PurchaseLinePicker.vue';
import SupplierSelect from './SupplierSelect.vue';
import { landedCosts } from '../../composables/usePurchasing.js';

const props = defineProps({
    order: { type: Object, default: null },
    products: { type: Array, required: true },
    suppliers: { type: Array, required: true },
    saveSupplier: { type: Function, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['saved', 'cancel']);

const today = () => new Date().toLocaleDateString('sv-SE');
const form = reactive({ supplierId: '', orderedOn: today(), lines: [], discount: 0, deliveryFees: 0 });
const errors = ref({});
const saving = ref(false);

watch(() => props.order, (order) => {
    Object.assign(form, order
        ? {
            supplierId: order.supplier.id,
            orderedOn: order.orderedOn,
            lines: order.lines.map(({ productId, variant, label, orderedQuantity, totalPrice }) => ({ productId, variant, label, quantity: orderedQuantity, totalPrice })),
            discount: order.discount,
            deliveryFees: order.deliveryFees,
        }
        : { supplierId: '', orderedOn: today(), lines: [], discount: 0, deliveryFees: 0 });
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
        return;
    }
    form.lines.push(line);
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.submit({
            supplierId: form.supplierId,
            orderedOn: form.orderedOn,
            lines: form.lines.map(({ productId, variant, quantity, totalPrice }) => ({ productId, variant, quantity: quantity ?? 0, totalPrice: totalPrice ?? 0 })),
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

            <div class="supplier-order-form__row">
                <FormField as="group" label="Fournisseur" :error="errors.supplierId">
                    <SupplierSelect v-model="form.supplierId" :suppliers="suppliers" :save="saveSupplier" />
                </FormField>
                <FormField as="group" label="Commandée le" :error="errors.orderedOn">
                    <BaseDatePicker v-model="form.orderedOn" aria-label="Commandée le" />
                </FormField>
            </div>

            <section class="supplier-order-form__lines" aria-label="Produits commandés">
                <table v-if="form.lines.length" class="supplier-order-form__table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix total (€)</th>
                            <th class="supplier-order-form__number">Coût unitaire</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, index) in form.lines" :key="`${line.productId}|${line.variant ?? ''}`">
                            <td class="supplier-order-form__label">
                                {{ line.label }}
                                <span v-if="errors[`lines[${index}].quantity`] || errors[`lines[${index}].totalPrice`]" class="supplier-order-form__line-error" role="alert">
                                    {{ errors[`lines[${index}].quantity`] ?? errors[`lines[${index}].totalPrice`] }}
                                </span>
                            </td>
                            <td class="supplier-order-form__quantity"><BaseNumberField v-model="line.quantity" :min="1" :label="`Quantité de ${line.label}`" /></td>
                            <td class="supplier-order-form__price"><BaseMoneyField v-model="line.totalPrice" :aria-label="`Prix total de ${line.label}`" /></td>
                            <td class="supplier-order-form__number"><MoneyAmount :cents="unitCost(line, index)" /></td>
                            <td><IconButton :icon="X" :label="`Retirer ${line.label}`" @click="form.lines.splice(index, 1)" /></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Produits</td>
                            <td><MoneyAmount :cents="subtotal" /></td>
                            <td colspan="2" />
                        </tr>
                    </tfoot>
                </table>
                <p v-if="errors.lines" class="supplier-order-form__line-error" role="alert">{{ errors.lines }}</p>
                <PurchaseLinePicker :products="products" @add="addLine" />
            </section>

            <section class="supplier-order-form__extras" aria-label="Remise et livraison">
                <FormField label="Remise globale (€)" :error="errors.discount" hint="Répartie selon le prix de chaque ligne.">
                    <BaseMoneyField v-model="form.discount" />
                </FormField>
                <FormField label="Frais de livraison (€)" :error="errors.deliveryFees" hint="Répartis à parts égales entre les lignes.">
                    <BaseMoneyField v-model="form.deliveryFees" />
                </FormField>
                <p class="supplier-order-form__total">Total payé <strong><MoneyAmount :cents="total" /></strong></p>
            </section>

            <div class="supplier-order-form__actions">
                <BaseButton variant="ghost" @click="emit('cancel')">Annuler</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ order ? 'Enregistrer' : 'Passer la commande' }}</BaseButton>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.supplier-order-form { display: flex; flex-direction: column; gap: var(--space-4); }
.supplier-order-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.supplier-order-form__lines { display: flex; flex-direction: column; gap: var(--space-3); }
.supplier-order-form__table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.supplier-order-form__table th { padding: var(--space-1) var(--space-2); border-bottom: 0.0625rem solid var(--color-border); color: var(--color-muted); font-size: 0.7rem; font-weight: 600; letter-spacing: 0.04rem; text-align: left; text-transform: uppercase; }
.supplier-order-form__table td { padding: var(--space-1) var(--space-2); border-bottom: 0.0625rem solid var(--color-border); vertical-align: middle; }
.supplier-order-form__table tfoot td { border-bottom: none; font-weight: 600; }
.supplier-order-form__quantity { width: 7rem; }
.supplier-order-form__price { width: 7.5rem; }
.supplier-order-form__number { text-align: right; font-variant-numeric: tabular-nums; }
.supplier-order-form__table th.supplier-order-form__number { text-align: right; }
.supplier-order-form__label { min-width: 10rem; }
.supplier-order-form__line-error { display: block; margin: 0; color: var(--color-danger); font-size: 0.8rem; }
.supplier-order-form__extras { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.supplier-order-form__total { grid-column: 1 / -1; display: flex; justify-content: space-between; margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-bg); }
.supplier-order-form__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
.supplier-order-form__error { margin: 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius); background: var(--color-danger-soft); color: var(--color-danger); }
</style>
