<script setup>
import { onMounted } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import OrderLines from '../components/orders/OrderLines.vue';
import OrderTotals from '../components/orders/OrderTotals.vue';
import OrderMargin from '../components/orders/OrderMargin.vue';
import PaymentMethod from '../components/orders/PaymentMethod.vue';
import { useOrder } from '../composables/useOrders.js';
import { formatDateTime } from '../composables/useDate.js';
import { visit } from '../composables/useNavigation.js';

const props = defineProps({
    orderId: { type: String, required: true },
});

const { order, load, remove } = useOrder(props.orderId);

async function onDelete() {
    await remove();
    visit('/commandes');
}

onMounted(load);
</script>

<template>
    <AppLayout :title="order ? `Commande ${order.reference}` : 'Commande'">
        <template #back><a class="back-link" href="/commandes"><ArrowLeft size="0.875rem" aria-hidden="true" /> Commandes</a></template>
        <template #actions>
            <ConfirmButton v-if="order" label="Supprimer la commande" confirm-label="Confirmer la suppression" @confirm="onDelete" />
        </template>

        <div v-if="order" class="order-detail-page">
            <p class="order-detail-page__meta">
                {{ formatDateTime(order.placedAt) }} ·
                <a :href="`/evenements/${order.event.id}`">{{ order.event.name }}</a>
                <span v-if="order.source === 'sumup'"> · importée de SumUp</span>
                <template v-if="order.paymentMethod"> · <PaymentMethod :method="order.paymentMethod" /></template>
            </p>
            <div class="order-detail-page__grid">
                <BaseCard title="Articles">
                    <OrderLines :lines="order.lines" />
                </BaseCard>
                <div class="order-detail-page__side">
                    <BaseCard title="Montant">
                        <OrderTotals :subtotal="order.subtotal" :discounts="order.discounts" :total="order.total" />
                    </BaseCard>
                    <BaseCard title="Marge">
                        <OrderMargin :total="order.total" :cost-of-goods="order.costOfGoods" :margin="order.margin" />
                    </BaseCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.order-detail-page { display: flex; flex-direction: column; gap: var(--space-4); }
.order-detail-page__meta { margin: 0; color: var(--color-muted); }
.order-detail-page__grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(17.5rem, 1fr); gap: var(--space-4); align-items: start; }
.order-detail-page__side { display: flex; flex-direction: column; gap: var(--space-4); }

@media (max-width: 56.25rem) {
    .order-detail-page__grid { grid-template-columns: 1fr; }
}
</style>
