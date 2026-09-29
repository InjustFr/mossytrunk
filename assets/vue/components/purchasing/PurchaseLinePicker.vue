<script setup>
import { computed, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';

const props = defineProps({
    products: { type: Array, required: true },
});
const emit = defineEmits(['add']);

const productId = ref('');
const variant = ref('');
const quantity = ref(1);
const totalPrice = ref(null);
const error = ref(null);

const product = computed(() => props.products.find((p) => p.id === productId.value) ?? null);
const needsVariant = computed(() => (product.value?.variants.length ?? 0) > 0);
const productOptions = computed(() => props.products.map((p) => ({ value: p.id, label: p.displayName })));
const variantOptions = computed(() => (product.value?.variants ?? []).map((v) => ({ value: v, label: v })));

watch(productId, () => {
    variant.value = '';
    error.value = null;
});

function add() {
    if (!product.value) {
        error.value = 'Choisissez un produit.';
        return;
    }
    if (needsVariant.value && !variant.value) {
        error.value = `Choisissez une variante pour « ${product.value.displayName} ».`;
        return;
    }
    const chosen = needsVariant.value ? variant.value : null;
    emit('add', {
        productId: product.value.id,
        variant: chosen,
        label: chosen ? `${product.value.displayName} — ${chosen}` : product.value.displayName,
        quantity: Math.max(1, quantity.value ?? 1),
        totalPrice: totalPrice.value ?? 0,
    });
    productId.value = '';
    quantity.value = 1;
    totalPrice.value = null;
    error.value = null;
}
</script>

<template>
    <div class="purchase-line-picker">
        <FormField label="Produit">
            <BaseCombobox v-model="productId" :options="productOptions" placeholder="Rechercher un produit…" />
        </FormField>
        <div class="purchase-line-picker__details">
            <FormField v-if="needsVariant" as="group" label="Variante" class="purchase-line-picker__variant">
                <BaseSelect v-model="variant" :options="variantOptions" aria-label="Variante" />
            </FormField>
            <FormField as="group" label="Quantité" class="purchase-line-picker__quantity">
                <BaseNumberField v-model="quantity" :min="1" label="Quantité commandée" />
            </FormField>
            <FormField label="Prix total (€)" class="purchase-line-picker__price">
                <BaseMoneyField v-model="totalPrice" />
            </FormField>
            <BaseButton variant="secondary" class="purchase-line-picker__add" @click="add">Ajouter</BaseButton>
        </div>
        <p v-if="error" class="purchase-line-picker__error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
.purchase-line-picker {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-3);
    border: 0.0625rem dashed var(--color-border-strong);
    border-radius: var(--radius);
}

.purchase-line-picker__details { display: flex; align-items: flex-end; gap: var(--space-2); }
.purchase-line-picker__variant { flex: 1 1 8rem; min-width: 0; }
.purchase-line-picker__quantity { flex: 0 0 6.5rem; }
.purchase-line-picker__price { flex: 0 0 7.5rem; }
.purchase-line-picker__add { margin-left: auto; }
.purchase-line-picker__error { margin: 0; color: var(--color-danger); font-size: 0.85rem; }
</style>
