<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import IdentifyLineForm from '../components/orders/IdentifyLineForm.vue';
import MergeOrderForm from '../components/orders/MergeOrderForm.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import OrderLines from '../components/orders/OrderLines.vue';
import OrderTotals from '../components/orders/OrderTotals.vue';
import OrderMargin from '../components/orders/OrderMargin.vue';
import PaymentMethod from '../components/orders/PaymentMethod.vue';
import { useOrder } from '../composables/useOrders.js';
import { useProducts } from '../composables/useProducts.js';
import { useToast } from '../composables/useToast.js';
import { formatDateTime } from '../composables/useDate.js';
import { visit } from '../composables/useNavigation.js';

const props = defineProps({
    orderId: { type: String, required: true },
});

const { t } = useI18n();
const { order, load, remove, identifyLine, mergeWith, candidates } = useOrder(props.orderId);
const mergeOpen = ref(false);

async function onMerged(other) {
    mergeOpen.value = false;
    toast.success(t('orders.merge.done', { reference: other.reference }));
    await load();
}
const { products, load: loadProducts } = useProducts();
const toast = useToast();
const identifying = ref(null);
const identifyOpen = computed({ get: () => identifying.value !== null, set: (open) => { if (!open) identifying.value = null; } });

async function onIdentified(name) {
    identifying.value = null;
    toast.success(t('orders.identify.saved', { name }));
    await load();
}

async function onDelete() {
    await remove();
    visit('/orders');
}

onMounted(() => Promise.all([load(), loadProducts()]));
</script>

<template>
    <AppLayout :title="order ? t('orders.detail.title', { reference: order.reference }) : t('orders.detail.titleFallback')">
        <template #back><a class="back-link" href="/orders"><ArrowLeft size="0.875rem" aria-hidden="true" /> {{ t('orders.detail.back') }}</a></template>
        <template #actions>
            <BaseButton v-if="order" variant="secondary" @click="mergeOpen = true">{{ t('orders.merge.open') }}</BaseButton>
            <ConfirmButton v-if="order" :label="t('orders.detail.delete')" :confirm-label="t('orders.detail.confirmDelete')" @confirm="onDelete" />
        </template>

        <div v-if="order" class="order-detail-page">
            <p class="order-detail-page__meta">
                {{ formatDateTime(order.placedAt) }} ·
                <a v-if="order.event" :href="`/events/${order.event.id}`">{{ order.event.name }}</a>
                <template v-else>{{ t('orders.shop', { source: order.sourceLabel }) }}</template>
                <span v-if="order.source !== 'manual'"> · {{ t('orders.detail.importedFrom', { source: order.sourceLabel }) }}</span>
                <template v-if="order.paymentMethod"> · <PaymentMethod :method="order.paymentMethod" /></template>
                <template v-if="order.importedSales.length"> · {{ t('orders.detail.externalReferences', { source: order.sourceLabel, references: order.importedSales.map((sale) => sale.reference).join(', ') }) }}</template>
            </p>
            <div class="order-detail-page__grid">
                <BaseCard :title="t('orders.detail.items')">
                    <OrderLines :lines="order.lines" identifiable @identify="identifying = $event" />
                </BaseCard>
                <div class="order-detail-page__side">
                    <BaseCard :title="t('orders.detail.amount')">
                        <OrderTotals :subtotal="order.subtotal" :discounts="order.discounts" :shipping="order.shipping" :total="order.total" link-rules />
                    </BaseCard>
                    <BaseCard :title="t('orders.detail.margin')">
                        <OrderMargin :total="order.total" :cost-of-goods="order.costOfGoods" :margin="order.margin" />
                    </BaseCard>
                </div>
            </div>
        </div>

        <BaseModal v-model:open="mergeOpen" :title="t('orders.merge.title')">
            <MergeOrderForm v-if="mergeOpen && order" :order="order" :candidates="candidates" :submit="mergeWith" @merged="onMerged" @cancel="mergeOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="identifyOpen" :title="t('orders.identify.title')">
            <IdentifyLineForm v-if="identifying" :line="identifying" :products="products" :submit="(payload) => identifyLine(identifying.id, payload)" @saved="onIdentified" @cancel="identifying = null" />
        </BaseModal>
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
