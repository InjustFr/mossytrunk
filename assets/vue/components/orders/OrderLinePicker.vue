<script setup>
import { computed, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import FormField from '../ui/FormField.vue';
import { formatCents } from '../../composables/useMoney.js';

const props = defineProps({
    products: { type: Array, required: true },
});
const emit = defineEmits(['add']);

const productId = ref('');
const variant = ref('');
const quantity = ref(1);
const error = ref(null);

const product = computed(() => props.products.find((p) => p.id === productId.value) ?? null);
const needsVariant = computed(() => (product.value?.variants.length ?? 0) > 0);
const productOptions = computed(() => props.products.map((p) => ({ value: p.id, label: `${p.displayName} — ${formatCents(p.sellingPrice)}` })));
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
    emit('add', { productId: product.value.id, variant: needsVariant.value ? variant.value : null, quantity: Math.max(1, quantity.value ?? 1) });
    productId.value = '';
    quantity.value = 1;
    error.value = null;
}
</script>

<template>
    <div class="order-line-picker">
        <FormField label="Produit" class="order-line-picker__product">
            <BaseCombobox v-model="productId" :options="productOptions" placeholder="Rechercher un produit…" />
        </FormField>
        <Transition name="order-line-picker__slide">
            <FormField v-if="needsVariant" label="Variante" class="order-line-picker__variant">
                <BaseSelect v-model="variant" :options="variantOptions" />
            </FormField>
        </Transition>
        <FormField label="Quantité" class="order-line-picker__quantity">
            <BaseNumberField v-model="quantity" :min="1" @keydown.enter.prevent="add" />
        </FormField>
        <BaseButton variant="secondary" class="order-line-picker__add" @click="add">Ajouter</BaseButton>
        <p v-if="error" class="order-line-picker__error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
.order-line-picker {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 5.625rem auto;
    grid-template-areas:
        "product product product"
        "variant quantity add";
    gap: var(--space-2);
    align-items: end;
}

.order-line-picker__product { grid-area: product; }
.order-line-picker__variant { grid-area: variant; }
.order-line-picker__quantity { grid-area: quantity; }
.order-line-picker__add { grid-area: add; }
.order-line-picker__error { grid-column: 1 / -1; margin: 0; color: var(--color-danger); font-size: 0.85rem; }

.order-line-picker__slide-enter-active,
.order-line-picker__slide-leave-active { transition: opacity var(--transition); }
.order-line-picker__slide-enter-from,
.order-line-picker__slide-leave-to { opacity: 0; }

</style>
