<script setup>
import { computed, ref, watch } from 'vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    listing: { type: Object, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['linked']);

const productId = ref(props.listing.linkedTo?.productId ?? '');
const variant = ref(props.listing.linkedTo?.variant ?? '');
const error = ref(null);
const saving = ref(false);

const product = computed(() => props.products.find((p) => p.id === productId.value) ?? null);
const productOptions = computed(() => props.products.map((p) => ({ value: p.id, label: p.displayName })));
const variantOptions = computed(() => (product.value?.variants ?? []).map((v) => ({ value: v, label: v })));

watch(productId, () => {
    const match = (product.value?.variants ?? []).find((v) => v.toLowerCase() === (props.listing.variation ?? '').toLowerCase());
    variant.value = match ?? '';
    error.value = null;
});

async function onLink() {
    if (!product.value) {
        error.value = 'Choisissez le produit vendu par cette annonce.';
        return;
    }
    if (product.value.variants.length && !variant.value) {
        error.value = `Choisissez la variante de « ${product.value.displayName} ».`;
        return;
    }
    saving.value = true;
    error.value = null;
    try {
        await props.submit(props.listing.id, product.value.id, variant.value);
        emit('linked', props.listing);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <li :class="['etsy-listing', { 'etsy-listing--linked': listing.linkedTo }]">
        <div class="etsy-listing__source">
            <span class="etsy-listing__title" :title="listing.title">{{ listing.title }}</span>
            <span v-if="listing.variation" class="etsy-listing__variation">{{ listing.variation }}</span>
        </div>
        <div class="etsy-listing__target">
            <BaseCombobox v-model="productId" :options="productOptions" placeholder="Produit MossyTrunk…" :aria-label="`Produit pour ${listing.title}`" />
            <BaseSelect v-if="variantOptions.length" v-model="variant" :options="variantOptions" placeholder="Variante" :aria-label="`Variante pour ${listing.title}`" />
            <BaseButton :variant="listing.linkedTo ? 'ghost' : 'secondary'" :loading="saving" @click="onLink">{{ listing.linkedTo ? 'Modifier' : 'Associer' }}</BaseButton>
        </div>
        <p v-if="error" class="etsy-listing__error" role="alert">{{ error }}</p>
    </li>
</template>

<style scoped>
.etsy-listing { display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-3) 0; border-bottom: 0.0625rem solid var(--color-border); }
.etsy-listing__source { display: flex; flex-direction: column; min-width: 0; }
.etsy-listing__title { overflow: hidden; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.etsy-listing__variation { color: var(--color-muted); font-size: 0.85rem; }
.etsy-listing__target { display: flex; align-items: center; gap: var(--space-2); }
.etsy-listing__target > :first-child { flex: 1; min-width: 0; }
.etsy-listing--linked .etsy-listing__title { font-weight: 400; color: var(--color-muted); }
.etsy-listing__error { margin: 0; color: var(--color-danger); font-size: 0.85rem; }
</style>
