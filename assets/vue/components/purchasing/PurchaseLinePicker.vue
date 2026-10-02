<script setup>
import { computed, ref, watch } from 'vue';
import { ToggleGroupItem, ToggleGroupRoot } from 'reka-ui';
import { Plus } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import BaseButton from '../ui/BaseButton.vue';
import BaseCombobox from '../ui/BaseCombobox.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import BaseNumberField from '../ui/BaseNumberField.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import VariantPicker from '../products/VariantPicker.vue';
import { useProductTypes } from '../../composables/useProductTypes.js';

const { t } = useI18n();

const props = defineProps({
    products: { type: Array, required: true },
});
const emit = defineEmits(['add', 'create']);
const { activeTypes, variantsOf } = useProductTypes();

const PRODUCT = 'product';
const TYPE = 'type';
const mode = ref(PRODUCT);
const productId = ref('');
const variant = ref('');
const typeId = ref('');
const typeVariants = ref([]);
const quantity = ref(1);
const totalPrice = ref(null);
const unitPrice = ref(null);
const error = ref(null);

const purchasable = computed(() => props.products.filter((p) => !p.archived));
const product = computed(() => purchasable.value.find((p) => p.id === productId.value) ?? null);
const needsVariant = computed(() => (product.value?.activeVariants.length ?? 0) > 0);
const productOptions = computed(() => purchasable.value.map((p) => ({ value: p.id, label: p.displayName })));
const variantOptions = computed(() => (product.value?.activeVariants ?? []).map((v) => ({ value: v, label: v })));
const typeOptions = computed(() => activeTypes.value
    .filter((type) => purchasable.value.some((p) => p.typeId === type.id))
    .map((type) => ({ value: type.id, label: type.name })));

const label = (item, chosen) => (chosen ? `${item.displayName} — ${chosen}` : item.displayName);
const same = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();
const wanted = (candidate) => typeVariants.value.length === 0 || typeVariants.value.some((chosen) => same(chosen, candidate));

const typeItems = computed(() => purchasable.value
    .filter((p) => p.typeId === typeId.value)
    .flatMap((p) => {
        if (p.activeVariants.length === 0) {
            return typeVariants.value.length === 0 ? [{ product: p, variant: null }] : [];
        }
        return p.activeVariants.filter(wanted).map((chosen) => ({ product: p, variant: chosen }));
    }));

watch(productId, () => {
    variant.value = '';
    error.value = null;
});

watch(typeId, () => {
    typeVariants.value = [];
    error.value = null;
});

watch(mode, () => { error.value = null; });

function reset() {
    productId.value = '';
    typeId.value = '';
    quantity.value = 1;
    totalPrice.value = null;
    unitPrice.value = null;
    error.value = null;
}

function addProduct() {
    if (!product.value) {
        error.value = t('purchasing.picker.chooseProduct');
        return;
    }
    if (needsVariant.value && !variant.value) {
        error.value = t('purchasing.picker.chooseVariant', { product: product.value.displayName });
        return;
    }
    const chosen = needsVariant.value ? variant.value : null;
    emit('add', {
        productId: product.value.id,
        variant: chosen,
        label: label(product.value, chosen),
        quantity: Math.max(1, quantity.value ?? 1),
        totalPrice: totalPrice.value ?? 0,
    });
    reset();
}

function choose(id) {
    mode.value = PRODUCT;
    productId.value = id ?? '';
}

defineExpose({ choose });

function addType() {
    if (!typeId.value) {
        error.value = t('purchasing.picker.chooseType');
        return;
    }
    if (typeItems.value.length === 0) {
        error.value = t('purchasing.picker.nothingToAdd');
        return;
    }
    const units = Math.max(1, quantity.value ?? 1);
    for (const item of typeItems.value) {
        emit('add', {
            productId: item.product.id,
            variant: item.variant,
            label: label(item.product, item.variant),
            quantity: units,
            totalPrice: (unitPrice.value ?? 0) * units,
        });
    }
    reset();
}
</script>

<template>
    <div class="purchase-line-picker">
        <ToggleGroupRoot
            :model-value="mode"
            type="single"
            class="purchase-line-picker__modes"
            :aria-label="t('purchasing.picker.mode')"
            @update:model-value="(value) => value && (mode = value)"
        >
            <ToggleGroupItem :value="PRODUCT" class="purchase-line-picker__mode">{{ t('purchasing.picker.oneProduct') }}</ToggleGroupItem>
            <ToggleGroupItem :value="TYPE" class="purchase-line-picker__mode">{{ t('purchasing.picker.wholeType') }}</ToggleGroupItem>
        </ToggleGroupRoot>
        <BaseButton variant="ghost" class="purchase-line-picker__create" @click="emit('create')"><Plus size="1rem" aria-hidden="true" /> {{ t('purchasing.form.newProduct') }}</BaseButton>

        <template v-if="mode === PRODUCT">
            <label class="purchase-line-picker__field purchase-line-picker__product">
                <span class="purchase-line-picker__label">{{ t('purchasing.picker.product') }}</span>
                <BaseCombobox v-model="productId" :options="productOptions" :placeholder="t('purchasing.picker.searchProduct')" />
            </label>
            <div v-if="needsVariant" class="purchase-line-picker__field purchase-line-picker__variant">
                <span class="purchase-line-picker__label" aria-hidden="true">{{ t('purchasing.picker.variant') }}</span>
                <BaseSelect v-model="variant" :options="variantOptions" :aria-label="t('purchasing.picker.variant')" />
            </div>
            <div class="purchase-line-picker__field purchase-line-picker__quantity">
                <span class="purchase-line-picker__label" aria-hidden="true">{{ t('purchasing.picker.quantity') }}</span>
                <BaseNumberField v-model="quantity" :min="1" :label="t('purchasing.picker.orderedQuantity')" />
            </div>
            <label class="purchase-line-picker__field purchase-line-picker__price">
                <span class="purchase-line-picker__label">{{ t('purchasing.picker.totalPrice') }}</span>
                <BaseMoneyField v-model="totalPrice" />
            </label>
            <BaseButton variant="secondary" class="purchase-line-picker__add" @click="addProduct">{{ t('purchasing.picker.add') }}</BaseButton>
        </template>

        <template v-else>
            <div class="purchase-line-picker__field purchase-line-picker__product">
                <span class="purchase-line-picker__label" aria-hidden="true">{{ t('purchasing.picker.type') }}</span>
                <BaseSelect v-model="typeId" :options="typeOptions" :placeholder="t('purchasing.picker.chooseTypePlaceholder')" :aria-label="t('purchasing.picker.type')" />
            </div>
            <div v-if="typeId && variantsOf(typeId).length" class="purchase-line-picker__field purchase-line-picker__product">
                <span class="purchase-line-picker__label" aria-hidden="true">{{ t('purchasing.picker.onlyVariants') }}</span>
                <VariantPicker v-model="typeVariants" :options="variantsOf(typeId)" />
            </div>
            <div class="purchase-line-picker__field purchase-line-picker__quantity">
                <span class="purchase-line-picker__label" aria-hidden="true">{{ t('purchasing.picker.quantityEach') }}</span>
                <BaseNumberField v-model="quantity" :min="1" :label="t('purchasing.picker.quantityEach')" />
            </div>
            <label class="purchase-line-picker__field purchase-line-picker__price">
                <span class="purchase-line-picker__label">{{ t('purchasing.picker.unitPrice') }}</span>
                <BaseMoneyField v-model="unitPrice" />
            </label>
            <BaseButton variant="secondary" class="purchase-line-picker__add" :disabled="!typeId" @click="addType">
                {{ t('purchasing.picker.addLines', { count: typeItems.length }, typeItems.length) }}
            </BaseButton>
        </template>
        <p v-if="error" class="purchase-line-picker__error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
.purchase-line-picker {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: var(--space-3) var(--space-2);
    padding: var(--space-3);
    border-radius: var(--radius);
    background: var(--color-bg);
}

.purchase-line-picker__modes { display: flex; flex: 1 1 auto; gap: var(--space-2); }
.purchase-line-picker__create { margin-left: auto; }

.purchase-line-picker__mode {
    padding: var(--space-1) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: 62.4375rem;
    background: var(--color-surface);
    cursor: pointer;
    font-size: 0.8125rem;
    transition: background var(--transition), color var(--transition), border-color var(--transition);
}

.purchase-line-picker__mode:hover { border-color: var(--color-ink); }
.purchase-line-picker__mode:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: 0.125rem; }
.purchase-line-picker__mode[data-state="on"] { background: var(--color-ink); border-color: var(--color-ink); color: var(--color-surface); }

.purchase-line-picker__field { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; }
.purchase-line-picker__label { color: var(--color-muted); font-size: 0.8125rem; }
.purchase-line-picker__product { flex: 1 1 100%; }
.purchase-line-picker__variant { flex: 1 1 7rem; }
.purchase-line-picker__quantity { flex: 0 0 7rem; }
.purchase-line-picker__price { flex: 0 0 7.5rem; }
.purchase-line-picker__price :deep(.money-field__input) {
    width: 100%;
    min-height: 2.375rem;
    padding: var(--space-2) var(--space-3);
    border: 0.0625rem solid var(--color-border-strong);
    border-radius: var(--radius);
    background: var(--color-surface);
    transition: border-color var(--transition), box-shadow var(--transition);
}

.purchase-line-picker__price :deep(.money-field__input:focus) { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 0.1875rem var(--color-accent-soft); }

.purchase-line-picker__add { margin-left: auto; }
.purchase-line-picker__error { flex: 1 1 100%; margin: 0; color: var(--color-danger); font-size: 0.8125rem; }
</style>
