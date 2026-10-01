<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    item: { type: Object, required: true },
    products: { type: Array, required: true },
    submit: { type: Function, required: true },
});
const emit = defineEmits(['linked']);
const { t } = useI18n();

const productId = ref(props.item.linkedTo?.productId ?? '');
const variant = ref(props.item.linkedTo?.variant ?? '');
const error = ref(null);
const saving = ref(false);

const product = computed(() => props.products.find((p) => p.id === productId.value) ?? null);
const productOptions = computed(() => props.products.map((p) => ({ value: p.id, label: p.displayName })));
const variantOptions = computed(() => (product.value?.variants ?? []).map((v) => ({ value: v, label: v })));

watch(productId, () => {
    const match = (product.value?.variants ?? []).find((v) => v.toLowerCase() === (props.item.variation ?? '').toLowerCase());
    variant.value = match ?? '';
    error.value = null;
});

async function onLink() {
    if (!product.value) {
        error.value = t('import.row.chooseProduct');
        return;
    }
    if (product.value.variants.length && !variant.value) {
        error.value = t('import.row.chooseVariant', { product: product.value.displayName });
        return;
    }
    saving.value = true;
    error.value = null;
    try {
        await props.submit(props.item.id, product.value.id, variant.value);
        emit('linked', props.item);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}
const itemName = computed(() => (props.item.variation ? `${props.item.label} — ${props.item.variation}` : props.item.label));
</script>

<template>
    <li :class="['external-item', { 'external-item--linked': item.linkedTo }]">
        <div class="external-item__source">
            <span class="external-item__title" :title="item.label">{{ item.label }}</span>
            <span v-if="item.variation" class="external-item__variation">{{ item.variation }}</span>
        </div>
        <div class="external-item__target">
            <BaseCombobox v-model="productId" :options="productOptions" :placeholder="t('import.row.productPlaceholder')" :aria-label="t('import.row.productFor', { item: itemName })" />
            <BaseSelect v-if="variantOptions.length" v-model="variant" :options="variantOptions" :placeholder="t('import.row.variantPlaceholder')" :aria-label="t('import.row.variantFor', { item: itemName })" />
            <BaseButton :variant="item.linkedTo ? 'ghost' : 'secondary'" :loading="saving" @click="onLink">{{ item.linkedTo ? t('import.row.edit') : t('import.row.link') }}</BaseButton>
        </div>
        <p v-if="error" class="external-item__error" role="alert">{{ error }}</p>
    </li>
</template>

<style scoped>
.external-item { display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-3) 0; border-bottom: 0.0625rem solid var(--color-border); }
.external-item__source { display: flex; flex-direction: column; min-width: 0; }
.external-item__title { overflow: hidden; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.external-item__variation { color: var(--color-muted); font-size: 0.85rem; }
.external-item__target { display: flex; align-items: center; gap: var(--space-2); }
.external-item__target > :first-child { flex: 1; min-width: 0; }
.external-item--linked .external-item__title { font-weight: 400; color: var(--color-muted); }
.external-item__error { margin: 0; color: var(--color-danger); font-size: 0.85rem; }
</style>
