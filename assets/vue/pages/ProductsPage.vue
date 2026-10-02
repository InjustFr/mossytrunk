<script setup>
import { computed, onMounted, ref } from 'vue';
import { Download, Tags } from '@lucide/vue';
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
import StockPotential from '../components/stock/StockPotential.vue';
import { sumPotential } from '../composables/useStockPotential.js';
import ProductTypesModal from '../components/products/ProductTypesModal.vue';
import RestockForm from '../components/products/RestockForm.vue';
import StockHistory from '../components/products/StockHistory.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import SelectionBar from '../components/ui/SelectionBar.vue';
import { KINDS, useProductFilters } from '../composables/useProductFilters.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useSalesChannels } from '../composables/useSalesChannels.js';
import { useServices } from '../composables/useServices.js';
import { useStock } from '../composables/useStock.js';
import { useToast } from '../composables/useToast.js';
import { typeColors } from '../composables/useTypeColor.js';

const { products, activeProducts, load, create, update, batchUpdate, removeSelected, moveVariant, remove, archive, restore } = useProducts();
const { types, activeTypes, load: loadTypes, allVariantsOf } = useProductTypes();
const filters = useProductFilters(products);
const toast = useToast();
const { t } = useI18n();
const { restock } = useStock();
const { channels, load: loadChannels } = useSalesChannels();

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

const typesOfKind = computed(() => {
    const used = new Set(products.value.filter((product) => product.kind === filters.kind.value).map((product) => product.typeId));
    const unused = new Set(products.value.map((product) => product.typeId));
    return (filters.archived.value ? types.value : activeTypes.value).filter((type) => used.has(type.id) || !unused.has(type.id));
});
const shownPotential = computed(() => sumPotential(filters.filtered.value));
const creatingSupply = computed(() => filters.kind.value === KINDS.supply);
const modalTitle = computed(() => t(editing.value ? 'products.page.editTitle' : creatingSupply.value ? 'products.page.newSupply' : 'products.page.new'));
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

async function onRemoveSelected() {
    try {
        const { deleted } = await removeSelected(filters.selectedIds.value);
        toast.success(t('products.toast.selectionRemoved', deleted));
        filters.clearSelection();
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

async function onArchive(product) {
    await archive(product.id);
    toast.success(t('products.toast.archived', { name: product.displayName }));
    filters.selectedIds.value = filters.selectedIds.value.filter((id) => id !== product.id);
    await load();
}

async function onRestore(product) {
    await restore(product.id);
    toast.success(t('products.toast.restored', { name: product.displayName }));
    await load();
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

const services = useServices();
const catalogueExports = computed(() => services.services.value.filter((service) => service.exportsCatalogue));

onMounted(() => Promise.all([load(), loadTypes(), services.load(), loadChannels()]));
</script>

<template>
    <AppLayout :title="t('products.page.title')">
        <template #actions>
            <BaseButton
                v-for="service in catalogueExports"
                :key="service.key"
                :href="`/api/services/${service.key}/catalogue.csv`"
                variant="secondary"
                download
                data-turbo="false"
            >
                <Download size="1rem" aria-hidden="true" /> {{ t('products.page.exportFor', { service: service.label }) }}
            </BaseButton>
            <BaseButton variant="secondary" @click="typesOpen = true"><Tags size="1rem" aria-hidden="true" /> {{ t('products.page.types') }}</BaseButton>
            <BaseButton @click="openCreate">{{ t(creatingSupply ? 'products.page.newSupply' : 'products.page.new') }}</BaseButton>
        </template>

        <BaseCard>
            <ProductFilters
                v-model:kind="filters.kind.value"
                v-model:type-id="filters.typeId.value"
                v-model:variants="filters.variants.value"
                v-model:archived="filters.archived.value"
                :archived-count="filters.archivedCount.value"
                :variant-options="allVariantsOf(filters.typeId.value)"
                v-model:search="filters.search.value"
                v-model:missing-cost="filters.missingCost.value"
                v-model:low-stock="filters.lowStock.value"
                :low-stock-count="filters.lowStockCount.value"
                :types="typesOfKind"
                :missing-cost-count="filters.missingCostCount.value"
                :type-colors="colors"
            />
            <section v-if="filters.kind.value === KINDS.article && shownPotential.units > 0" class="products-page__potential" :aria-label="t('stock.potential.filtered')">
                <StockPotential :potential="shownPotential" :with-note="false" />
            </section>
            <ProductList
                v-model:checked-ids="filters.selectedIds.value"
                :products="filters.filtered.value"
                :stock-variants="filters.variants.value"
                :channels="channels"
                :priced="filters.kind.value === KINDS.article"
                :all-selected="filters.allVisibleSelected.value"
                :type-colors="colors"
                :selected-id="modalOpen ? editing?.id ?? null : null"
                @toggle-all="filters.toggleAllVisible"
                @edit="openEdit"
                @move="moving = $event"
                @restock="restocking = $event"
                @history="viewingStock = $event"
                @remove="onRemove"
                @archive="onArchive"
                @restore="onRestore"
            />
        </BaseCard>

        <SelectionBar :count="filters.selectedIds.value.length" :summary="t('products.selection.count', filters.selectedIds.value.length)" @clear="filters.clearSelection">
            <BaseButton @click="batchOpen = true">{{ t('products.selection.edit') }}</BaseButton>
            <ConfirmButton
                variant="danger"
                :label="t('products.selection.delete')"
                :confirm-label="t('products.selection.confirmDelete', filters.selectedIds.value.length)"
                :message="t('products.selection.deleteMessage')"
                @confirm="onRemoveSelected"
            />
        </SelectionBar>

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <ProductForm :product="editing" :kind="filters.kind.value" :submit="submit" :channels="channels" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="moveOpen" :title="t(moving?.variants.length ? 'products.page.moveVariantTitle' : 'products.page.makeVariantTitle')">
            <MoveVariantForm v-if="moving" :product="moving" :products="activeProducts" :submit="(payload) => moveVariant(moving.id, payload)" @moved="onMoved" @cancel="moving = null" />
        </BaseModal>
        <BaseModal v-model:open="restockOpen" :title="t('products.page.restockTitle', { name: restocking?.displayName ?? '' })">
            <RestockForm v-if="restocking" :product="restocking" :submit="restock" @saved="onRestocked" @cancel="restocking = null" />
        </BaseModal>
        <BaseModal v-model:open="historyOpen" :title="t('products.page.stockTitle', { name: viewingStock?.displayName ?? '' })">
            <StockHistory v-if="viewingStock" :product="viewingStock" />
        </BaseModal>
        <ProductTypesModal v-model:open="typesOpen" @saved="onTypeSaved" @renamed="load" @changed="load" />
        <BaseModal v-model:open="batchOpen" :title="t('products.page.batchTitle')" :focus-field="false">
            <ProductBatchForm :count="filters.selectedIds.value.length" :products="selectedProducts" :channels="channels" :submit="submitBatch" @saved="onBatchSaved" @cancel="batchOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.products-page__potential { margin-bottom: var(--space-4); padding: var(--space-3) var(--space-4); border-radius: var(--radius); background: var(--color-bg); }
</style>
