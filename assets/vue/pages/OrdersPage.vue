<script setup>
import { onMounted, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import EventFilter from '../components/orders/EventFilter.vue';
import OrderForm from '../components/orders/OrderForm.vue';
import OrderList from '../components/orders/OrderList.vue';
import SumUpImportProblem from '../components/orders/SumUpImportProblem.vue';
import { useEvents } from '../composables/useEvents.js';
import { useOrders } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';
import { useSumUpImport } from '../composables/useSumUpImport.js';
import { useToast } from '../composables/useToast.js';

const { orders, eventFilter, load, place } = useOrders();
const { products, load: loadProducts } = useProducts();
const { events, load: loadEvents } = useEvents();
const toast = useToast();
const lastPlacedId = ref(null);
const sumUp = useSumUpImport();

async function importFromSumUp() {
    if (await sumUp.run()) {
        await Promise.all([load(), loadProducts()]);
    }
}

async function onPlaced(order) {
    toast.success(`Commande ${order.reference} enregistrée.`);
    lastPlacedId.value = order.id;
    await load();
}

watch(eventFilter, load);
onMounted(() => Promise.all([load(), loadProducts(), loadEvents()]));
</script>

<template>
    <AppLayout title="Commandes">
        <template #actions>
            <BaseButton variant="secondary" :loading="sumUp.importing.value" @click="importFromSumUp">Importer depuis SumUp</BaseButton>
        </template>

        <Transition name="orders-page__problem">
            <SumUpImportProblem v-if="sumUp.problem.value" class="orders-page__problem" :problem="sumUp.problem.value" @dismiss="sumUp.dismiss" />
        </Transition>

        <div class="orders-page">
            <BaseCard class="orders-page__list" title="Historique">
                <template #actions>
                    <EventFilter v-model="eventFilter" :events="events" />
                </template>
                <OrderList :orders="orders" :highlight-id="lastPlacedId" />
            </BaseCard>
            <BaseCard class="orders-page__form">
                <OrderForm :products="products" :submit="place" @placed="onPlaced" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.orders-page {
    display: grid;
    grid-template-columns: minmax(0, 3fr) minmax(360px, 2fr);
    gap: var(--space-4);
    align-items: start;
}

.orders-page__form { position: sticky; top: var(--space-4); }
.orders-page__problem { margin-bottom: var(--space-4); }

.orders-page__problem-enter-active,
.orders-page__problem-leave-active { transition: opacity var(--transition), transform var(--transition); }
.orders-page__problem-enter-from,
.orders-page__problem-leave-to { opacity: 0; transform: translateY(-6px); }

@media (max-width: 1000px) {
    .orders-page { grid-template-columns: 1fr; }
    .orders-page__form { position: static; order: -1; }
}
</style>
