<script setup>
import { computed, onMounted, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import SelectionBar from '../components/ui/SelectionBar.vue';
import EventFilter from '../components/orders/EventFilter.vue';
import OrderForm from '../components/orders/OrderForm.vue';
import OrderList from '../components/orders/OrderList.vue';
import ImportProblem from '../components/import/ImportProblem.vue';
import ExternalItemLinker from '../components/import/ExternalItemLinker.vue';
import ExternalItemsNotice from '../components/import/ExternalItemsNotice.vue';
import { useImport } from '../composables/useImport.js';
import { isReady, useServices } from '../composables/useServices.js';
import { useEvents } from '../composables/useEvents.js';
import { useOrders } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';
import { useToast } from '../composables/useToast.js';

const { orders, eventFilter, load, place, removeSelected } = useOrders();
const { products, load: loadProducts } = useProducts();
const { events, load: loadEvents } = useEvents();
const services = useServices();
const toast = useToast();
const { t } = useI18n();
const importers = shallowRef([]);
const readyImporters = computed(() => importers.value.filter((importer) => isReady(importer.service)));
const linking = shallowRef(null);
const linkerOpen = computed({
    get: () => linking.value !== null,
    set: (open) => { if (!open) linking.value = null; },
});
const formOpen = ref(false);
const lastPlacedId = ref(null);
const checkedIds = ref([]);

async function deleteChecked() {
    const { deleted } = await removeSelected(checkedIds.value);
    toast.success(t('orders.selection.deleted', deleted));
    checkedIds.value = [];
    await load();
}

async function onPlaced(order) {
    toast.success(t('orders.page.placed', { reference: order.reference }));
    lastPlacedId.value = order.id;
    await load();
}

async function runImport(importer) {
    if (await importer.run()) {
        await Promise.all([load(), loadProducts()]);
    }
}

async function onItemLinked(importer, item) {
    toast.success(t('orders.page.itemLinked', { label: item.label }));
    await importer.loadItems();
}

async function reimport(importer) {
    await runImport(importer);
    if (importer.unlinked.value.length === 0) {
        linking.value = null;
    }
}

watch(eventFilter, () => {
    checkedIds.value = [];
    load();
});
onMounted(async () => {
    await Promise.all([load(), loadProducts(), loadEvents(), services.load()]);
    importers.value = services.added.value.map(useImport);
    await Promise.all(importers.value.map((importer) => importer.loadItems()));
});
</script>

<template>
    <AppLayout :title="t('orders.page.title')">
        <template #actions>
            <BaseButton
                v-for="importer in readyImporters"
                :key="importer.service.key"
                variant="secondary"
                :loading="importer.importing.value"
                @click="runImport(importer)"
            >
                {{ t('orders.page.importFrom', { service: importer.service.label }) }}
            </BaseButton>
            <BaseButton @click="formOpen = true">{{ t('orders.page.new') }}</BaseButton>
        </template>

        <template v-for="importer in importers" :key="importer.service.key">
            <Transition name="orders-page__problem">
                <ImportProblem v-if="importer.problem.value" class="orders-page__problem" :problem="importer.problem.value" @dismiss="importer.dismiss" />
            </Transition>
            <ExternalItemsNotice
                v-if="importer.unlinked.value.length"
                class="orders-page__problem"
                :label="importer.service.label"
                :count="importer.unlinked.value.length"
                @open="linking = importer"
            />
        </template>

        <BaseCard>
            <template #actions>
                <EventFilter v-model="eventFilter" :events="events" />
            </template>
            <OrderList v-model:checked-ids="checkedIds" :orders="orders" :highlight-id="lastPlacedId" />
        </BaseCard>
        <SelectionBar :count="checkedIds.length" :summary="t('orders.selection.count', checkedIds.length)" @clear="checkedIds = []">
            <ConfirmButton
                variant="danger"
                :label="t('orders.selection.delete')"
                :confirm-label="t('orders.selection.confirmDelete', checkedIds.length)"
                :message="t('orders.selection.deleteMessage', checkedIds.length)"
                @confirm="deleteChecked"
            />
        </SelectionBar>

        <BaseModal v-model:open="linkerOpen" :title="linking ? t('orders.page.serviceItems', { service: linking.service.label }) : t('orders.page.items')">
            <ExternalItemLinker
                v-if="linking"
                :label="linking.service.label"
                :items="linking.items.value"
                :products="products"
                :submit="linking.link"
                :importing="linking.importing.value"
                :can-import="isReady(linking.service)"
                @linked="onItemLinked(linking, $event)"
                @reimport="reimport(linking)"
            />
        </BaseModal>
        <BaseModal v-model:open="formOpen" :title="t('orders.page.new')" variant="drawer">
            <OrderForm :products="products" :submit="place" @placed="onPlaced" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.orders-page__problem { margin-bottom: var(--space-4); }

.orders-page__problem-enter-active,
.orders-page__problem-leave-active { transition: opacity var(--transition), transform var(--transition); }
.orders-page__problem-enter-from,
.orders-page__problem-leave-to { opacity: 0; transform: translateY(-0.375rem); }
</style>
