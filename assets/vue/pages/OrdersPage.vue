<script setup>
import { onMounted, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EventFilter from '../components/orders/EventFilter.vue';
import OrderForm from '../components/orders/OrderForm.vue';
import OrderList from '../components/orders/OrderList.vue';
import SumUpImportProblem from '../components/orders/SumUpImportProblem.vue';
import EtsyListingLinker from '../components/etsy/EtsyListingLinker.vue';
import EtsyListingsNotice from '../components/etsy/EtsyListingsNotice.vue';
import { useEtsy } from '../composables/useEtsy.js';
import { useWorkspaceSettings } from '../composables/useWorkspaceSettings.js';
import { useEvents } from '../composables/useEvents.js';
import { useOrders } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';
import { useSumUpImport } from '../composables/useSumUpImport.js';
import { useToast } from '../composables/useToast.js';

const { orders, eventFilter, load, place } = useOrders();
const { products, load: loadProducts } = useProducts();
const { events, load: loadEvents } = useEvents();
const toast = useToast();
const sumUp = useSumUpImport();
const etsy = useEtsy();
const { settings, load: loadSettings } = useWorkspaceSettings();
const linkerOpen = ref(false);
const formOpen = ref(false);
const lastPlacedId = ref(null);

// The drawer stays open after saving so several orders can be typed in a row, the list refreshing beside it.
async function onPlaced(order) {
    toast.success(`Commande ${order.reference} enregistrée.`);
    lastPlacedId.value = order.id;
    await load();
}

async function importFromSumUp() {
    if (await sumUp.run()) {
        await Promise.all([load(), loadProducts()]);
    }
}

async function importFromEtsy() {
    if (await etsy.run()) {
        await load();
    }
}

async function onListingLinked(listing) {
    toast.success(`Annonce « ${listing.title} » associée.`);
    await etsy.loadListings();
}

async function reimportEtsy() {
    await importFromEtsy();
    if (etsy.unlinked.value.length === 0) {
        linkerOpen.value = false;
    }
}

watch(eventFilter, load);
onMounted(async () => {
    await Promise.all([load(), loadProducts(), loadEvents(), loadSettings()]);
    if (settings.value?.etsy.connected) {
        await etsy.loadListings();
    }
});
</script>

<template>
    <AppLayout title="Commandes">
        <template #actions>
            <BaseButton variant="secondary" :loading="sumUp.importing.value" @click="importFromSumUp">Importer depuis SumUp</BaseButton>
            <BaseButton v-if="settings?.etsy.connected" variant="secondary" :loading="etsy.importing.value" @click="importFromEtsy">Importer depuis Etsy</BaseButton>
            <BaseButton @click="formOpen = true">Nouvelle commande</BaseButton>
        </template>

        <Transition name="orders-page__problem">
            <SumUpImportProblem v-if="sumUp.problem.value" class="orders-page__problem" :problem="sumUp.problem.value" @dismiss="sumUp.dismiss" />
        </Transition>

        <EtsyListingsNotice v-if="etsy.unlinked.value.length" class="orders-page__problem" :count="etsy.unlinked.value.length" @open="linkerOpen = true" />

        <BaseCard>
            <template #actions>
                <EventFilter v-model="eventFilter" :events="events" />
            </template>
            <OrderList :orders="orders" :highlight-id="lastPlacedId" />
        </BaseCard>

        <BaseModal v-model:open="linkerOpen" title="Annonces Etsy">
            <EtsyListingLinker
                :listings="etsy.listings.value"
                :products="products"
                :submit="etsy.link"
                :importing="etsy.importing.value"
                @linked="onListingLinked"
                @reimport="reimportEtsy"
            />
        </BaseModal>
        <BaseModal v-model:open="formOpen" title="Nouvelle commande" variant="drawer">
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
