<script setup>
import { onMounted, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import EventFilter from '../components/orders/EventFilter.vue';
import OrderForm from '../components/orders/OrderForm.vue';
import OrderList from '../components/orders/OrderList.vue';
import { useEvents } from '../composables/useEvents.js';
import { useOrders } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';
import { useToast } from '../composables/useToast.js';

const { orders, eventFilter, load, place } = useOrders();
const { products, load: loadProducts } = useProducts();
const { events, load: loadEvents } = useEvents();
const toast = useToast();
const lastPlacedId = ref(null);

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

@media (max-width: 1000px) {
    .orders-page { grid-template-columns: 1fr; }
    .orders-page__form { position: static; order: -1; }
}
</style>
