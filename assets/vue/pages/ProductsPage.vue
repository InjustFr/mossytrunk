<script setup>
import { computed, onMounted, ref } from 'vue';
import { Tags } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ProductBatchForm from '../components/products/ProductBatchForm.vue';
import ProductFilters from '../components/products/ProductFilters.vue';
import MoveVariantForm from '../components/products/MoveVariantForm.vue';
import ProductForm from '../components/products/ProductForm.vue';
import ProductList from '../components/products/ProductList.vue';
import ProductTypesModal from '../components/products/ProductTypesModal.vue';
import RestockForm from '../components/products/RestockForm.vue';
import StockHistory from '../components/products/StockHistory.vue';
import SelectionBar from '../components/products/SelectionBar.vue';
import { useProductFilters } from '../composables/useProductFilters.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useStock } from '../composables/useStock.js';
import { useToast } from '../composables/useToast.js';
import { typeColors } from '../composables/useTypeColor.js';

const { products, load, create, update, batchUpdate, moveVariant, remove } = useProducts();
const { types, load: loadTypes, variantsOf } = useProductTypes();
const filters = useProductFilters(products);
const toast = useToast();
const { t } = useI18n();
const { restock } = useStock();

const colors = computed(() => typeColors(types.value));
const modalOpen = ref(false);
const typesOpen = ref(false);
const batchOpen = ref(false);
const moving = ref(null);
const moveOpen = computed({ get: () => moving.value !== null, set: (open) => { if (!open) moving.value = null; } });
const editing = ref(null);
const restocking = ref(null);
const restockOpen = computed({ get: () => restocking.value !== null, set: (open) => { if (!open) restocking.value = null; } });
const viewingStock = ref(null);
const historyOpen = computed({ get: () => viewingStock.value !== null, set: (open) => { if (!open) viewingStock.value = null; } });

const modalTitle = computed(() => t(editing.value ? 'products.page.editTitle' : 'products.page.new'));
const submit = (payload) => (editing.value ? update(editing.value.id, payload) : create(payload));
const selectedProducts = computed(() => products.value.filter((product) => filters.selectedIds.value.includes(product.id)));
const submitBatch = (changes) => batchUpdate({ productIds: filters.selectedIds.value, ...changes });

function openCreate() {
    editing.value = null;
    modalOpen.value = true;
}

function openEdit(product) {
    editing.value = product;
    modalOpen.value = true;
}

async function onSaved(name) {
    toast.success(t(editing.value ? 'products.toast.updated' : 'products.toast.added', { name }));
    modalOpen.value = false;
    await load();
}

async function onMoved({ variant, target }) {
    toast.success(t(variant ? 'products.toast.movedToVariant' : 'products.toast.moved', { target, variant }));
    moving.value = null;
    await load();
}

async function onRestocked({ quantity, variant }) {
    const name = restocking.value.displayName;
    toast.success(t('products.toast.restockedInto', { name: variant ? `${name} — ${variant}` : name }, quantity));
    restocking.value = null;
    await load();
}

async function onRemove(product) {
    try {
        await remove(product.id);
        toast.success(t('products.toast.removed', { name: product.displayName }));
        filters.selectedIds.value = filters.selectedIds.value.filter((id) => id !== product.id);
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

async function onTypeSaved(name, created) {
    toast.success(t(created ? 'products.toast.typeCreated' : 'products.toast.typeUpdated', { name }));
    await load();
}

async function onBatchSaved(count) {
    toast.success(t('products.toast.batchUpdated', count));
    batchOpen.value = false;
    filters.clearSelection();
    await load();
}

onMounted(() => Promise.all([load(), loadTypes()]));
</script>

<template>
    <AppLayout :title="t('products.page.title')">
        <template #actions>
            <BaseButton variant="secondary" @click="typesOpen = true"><Tags size="1rem" aria-hidden="true" /> {{ t('products.page.types') }}</BaseButton>
            <BaseButton @click="openCreate">{{ t('products.page.new') }}</BaseButton>
        </template>

        <BaseCard>
            <ProductFilters
                v-model:type-id="filters.typeId.value"
                v-model:variants="filters.variants.value"
                :variant-options="variantsOf(filters.typeId.value)"
                v-model:search="filters.search.value"
                v-model:missing-cost="filters.missingCost.value"
                v-model:low-stock="filters.lowStock.value"
                :low-stock-count="filters.lowStockCount.value"
                :types="types"
                :missing-cost-count="filters.missingCostCount.value"
                :type-colors="colors"
            />
            <ProductList
                v-model:checked-ids="filters.selectedIds.value"
                :products="filters.filtered.value"
                :stock-variants="filters.variants.value"
                :all-selected="filters.allVisibleSelected.value"
                :type-colors="colors"
                :selected-id="modalOpen ? editing?.id ?? null : null"
                @toggle-all="filters.toggleAllVisible"
                @edit="openEdit"
                @move="moving = $event"
                @restock="restocking = $event"
                @history="viewingStock = $event"
                @remove="onRemove"
            />
        </BaseCard>

        <SelectionBar :count="filters.selectedIds.value.length" @edit="batchOpen = true" @clear="filters.clearSelection" />

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <ProductForm :product="editing" :submit="submit" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="moveOpen" :title="t(moving?.variants.length ? 'products.page.moveVariantTitle' : 'products.page.makeVariantTitle')">
            <MoveVariantForm v-if="moving" :product="moving" :products="products" :submit="(payload) => moveVariant(moving.id, payload)" @moved="onMoved" @cancel="moving = null" />
        </BaseModal>
        <BaseModal v-model:open="restockOpen" :title="t('products.page.restockTitle', { name: restocking?.displayName ?? '' })">
            <RestockForm v-if="restocking" :product="restocking" :submit="restock" @saved="onRestocked" @cancel="restocking = null" />
        </BaseModal>
        <BaseModal v-model:open="historyOpen" :title="t('products.page.stockTitle', { name: viewingStock?.displayName ?? '' })">
            <StockHistory v-if="viewingStock" :product="viewingStock" />
        </BaseModal>
        <ProductTypesModal v-model:open="typesOpen" @saved="onTypeSaved" @renamed="load" />
        <BaseModal v-model:open="batchOpen" :title="t('products.page.batchTitle')">
            <ProductBatchForm :count="filters.selectedIds.value.length" :products="selectedProducts" :submit="submitBatch" @saved="onBatchSaved" @cancel="batchOpen = false" />
        </BaseModal>
    </AppLayout>
</template>
