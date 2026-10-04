<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import MoneyAmount from '../components/ui/MoneyAmount.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import BackLink from '../components/ui/BackLink.vue';
import MergeSupplierOrderForm from '../components/purchasing/MergeSupplierOrderForm.vue';
import SupplierOrderForm from '../components/purchasing/SupplierOrderForm.vue';
import SupplierOrderReception from '../components/purchasing/SupplierOrderReception.vue';
import { formatDate, formatDateTime } from '../composables/useDate.js';
import { formatCents } from '../composables/useMoney.js';
import { visit } from '../composables/useNavigation.js';
import { listUrl } from '../composables/useQueryState.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { SUPPLIER_ORDER_STATUSES, useSupplierOrders, useSuppliers } from '../composables/usePurchasing.js';
import { useToast } from '../composables/useToast.js';

const { t } = useI18n();

const props = defineProps({
    orderId: { type: String, required: true },
});

const { loadOne, update, remove, receive, merge, list } = useSupplierOrders();
const { suppliers, load: loadSuppliers, save: saveSupplier } = useSuppliers();
const { products, load: loadProducts } = useProducts();
const { load: loadTypes } = useProductTypes();
const toast = useToast();

const order = ref(null);
const editOpen = ref(false);
const mergeOpen = ref(false);
const receiving = ref(false);
const isOrdered = computed(() => order.value?.status === 'ordered');
const status = computed(() => (order.value ? SUPPLIER_ORDER_STATUSES[order.value.status] : null));

const load = () => loadOne(props.orderId, order);

async function saveAndReload(id, payload) {
    const supplier = await saveSupplier(id, payload);
    await loadSuppliers();
    return supplier;
}

async function onSaved() {
    editOpen.value = false;
    toast.success(t('purchasing.detail.updated', { reference: order.value.reference }));
    await load();
}

async function onRemove() {
    try {
        await remove(props.orderId);
        toast.success(t('purchasing.detail.deleted', { reference: order.value.reference }));
        visit(listUrl('/supplier-orders'));
    } catch (error) {
        toast.error(error.message);
    }
}

async function onMerged(absorbed) {
    mergeOpen.value = false;
    toast.success(t('purchasing.detail.merged', { reference: absorbed.reference, target: order.value.reference }));
    await Promise.all([load(), loadProducts()]);
}

async function onReceived() {
    receiving.value = false;
    toast.success(t('purchasing.detail.received', { reference: order.value.reference }));
    await load();
}

onMounted(() => Promise.all([load(), loadSuppliers(), loadProducts(), loadTypes()]));
</script>

<template>
    <AppLayout :title="order?.reference ?? t('purchasing.detail.title')">
        <template #back><BackLink :href="listUrl('/supplier-orders')">{{ t('purchasing.detail.back') }}</BackLink></template>
        <template #actions>
            <template v-if="order && !receiving">
                <ConfirmButton v-if="isOrdered" variant="ghost" :label="t('purchasing.detail.delete')" :message="t('purchasing.detail.deleteMessage', { reference: order.reference })" @confirm="onRemove" />
                <BaseButton variant="secondary" @click="mergeOpen = true">{{ t('purchasing.detail.merge') }}</BaseButton>
                <BaseButton variant="secondary" @click="editOpen = true">{{ t('purchasing.detail.edit') }}</BaseButton>
                <BaseButton v-if="isOrdered" @click="receiving = true">{{ t('purchasing.detail.unpack') }}</BaseButton>
            </template>
        </template>

        <div v-if="order" class="supplier-order-page">
            <dl class="supplier-order-page__facts">
                <div><dt>{{ t('purchasing.detail.supplier') }}</dt><dd>{{ order.supplier.name }}</dd></div>
                <div v-if="order.supplierReference"><dt>{{ t('purchasing.detail.supplierReference') }}</dt><dd>{{ order.supplierReference }}</dd></div>
                <div><dt>{{ t('purchasing.detail.orderedOn') }}</dt><dd>{{ formatDate(order.orderedOn) }}</dd></div>
                <div v-if="order.receivedAt"><dt>{{ t('purchasing.detail.receivedAt') }}</dt><dd>{{ formatDateTime(order.receivedAt) }}</dd></div>
                <div><dt>{{ t('purchasing.detail.products') }}</dt><dd><MoneyAmount :cents="order.subtotal" :currency="order.currency" /></dd></div>
                <div v-if="order.discount"><dt>{{ t('purchasing.detail.discount') }}</dt><dd>−<MoneyAmount :cents="order.discount" :currency="order.currency" /></dd></div>
                <div v-if="order.deliveryFees"><dt>{{ t('purchasing.detail.delivery') }}</dt><dd><MoneyAmount :cents="order.deliveryFees" :currency="order.currency" /></dd></div>
                <div><dt>{{ t('purchasing.detail.totalPaid') }}</dt><dd><MoneyAmount :cents="order.total" :currency="order.currency" /><span v-if="order.currency !== 'EUR'" class="supplier-order-page__muted">&nbsp;{{ t('purchasing.form.inEuros', { amount: formatCents(order.totalInEuros) }) }}</span></dd></div>
                <div v-if="order.currency !== 'EUR'"><dt>{{ t('purchasing.detail.exchangeRate') }}</dt><dd>1 $ = {{ order.exchangeRate }} €</dd></div>
                <div><dt>{{ t('purchasing.detail.status') }}</dt><dd><StatusBadge :tone="status.tone">{{ t(status.label) }}</StatusBadge></dd></div>
            </dl>

            <SupplierOrderReception v-if="receiving" :order="order" :submit="(lines) => receive(orderId, lines)" @received="onReceived" @cancel="receiving = false" />

            <BaseCard v-else>
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>{{ t('purchasing.detail.product') }}</th>
                            <th class="compact-table__number">{{ t('purchasing.detail.ordered') }}</th>
                            <th class="compact-table__number">{{ t('purchasing.detail.receivedQuantity') }}</th>
                            <th class="compact-table__number">{{ t('purchasing.detail.price') }}</th>
                            <th v-if="order.discount || order.deliveryFees" class="compact-table__number">{{ t('purchasing.detail.shares') }}</th>
                            <th class="compact-table__number">{{ t('purchasing.detail.totalCost') }}</th>
                            <th class="compact-table__number">{{ t('purchasing.detail.unitCost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in order.lines" :key="line.id">
                            <td>{{ line.label }}</td>
                            <td class="compact-table__number">{{ line.orderedQuantity }}</td>
                            <td class="compact-table__number">
                                <template v-if="line.receivedQuantity === null">—</template>
                                <template v-else>
                                    {{ line.receivedQuantity }}
                                    <StatusBadge v-if="line.receivedQuantity > line.orderedQuantity" tone="warning" class="supplier-order-page__gap">+{{ line.receivedQuantity - line.orderedQuantity }}</StatusBadge>
                                    <StatusBadge v-else-if="line.receivedQuantity < line.orderedQuantity" tone="danger" class="supplier-order-page__gap">{{ line.receivedQuantity - line.orderedQuantity }}</StatusBadge>
                                </template>
                            </td>
                            <td class="compact-table__number"><MoneyAmount :cents="line.totalPrice" :currency="order.currency" /></td>
                            <td v-if="order.discount || order.deliveryFees" class="compact-table__number supplier-order-page__shares">
                                <span v-if="line.discountShare">−<MoneyAmount :cents="line.discountShare" :currency="order.currency" /></span>
                                <span v-if="line.feesShare">+<MoneyAmount :cents="line.feesShare" :currency="order.currency" /></span>
                            </td>
                            <td class="compact-table__number"><MoneyAmount :cents="line.landedCost" :currency="order.currency" /></td>
                            <td class="compact-table__number">
                                <template v-if="line.unitCost !== null">
                                    <MoneyAmount :cents="line.unitCost" />
                                    <span v-if="line.unitCost !== line.plannedUnitCost" class="supplier-order-page__planned">{{ t('purchasing.detail.planned') }} <MoneyAmount :cents="line.plannedUnitCost" /></span>
                                </template>
                                <span v-else-if="line.receivedQuantity === 0" class="supplier-order-page__muted">{{ t('purchasing.detail.nothingReceived') }}</span>
                                <MoneyAmount v-else :cents="line.plannedUnitCost" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </BaseCard>
        </div>

        <BaseModal v-model:open="editOpen" :title="t('purchasing.detail.editTitle')" variant="drawer">
            <SupplierOrderForm
                v-if="order"
                :order="order"
                :products="products"
                :suppliers="suppliers"
                :save-supplier="saveAndReload"
                :submit="(payload) => update(orderId, payload)"
                :reload-products="loadProducts"
                @saved="onSaved"
                @cancel="editOpen = false"
            />
        </BaseModal>
        <BaseModal v-model:open="mergeOpen" :title="t('purchasing.detail.mergeTitle')">
            <MergeSupplierOrderForm v-if="mergeOpen && order" :order="order" :orders="list" :submit="(absorbedId) => merge(orderId, absorbedId)" @merged="onMerged" @cancel="mergeOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.supplier-order-page { display: flex; flex-direction: column; gap: var(--space-5); }
.supplier-order-page__facts { display: flex; gap: var(--space-6); flex-wrap: wrap; margin: 0; }
.supplier-order-page__facts dt { color: var(--color-muted); font-size: var(--font-size-sm); }
.supplier-order-page__facts dd { margin: 0; font-weight: 600; }
.supplier-order-page__gap { margin-left: var(--space-1); }
.supplier-order-page__planned { display: block; color: var(--color-muted); font-size: var(--font-size-xs); }
.supplier-order-page__shares { color: var(--color-muted); font-size: var(--font-size-md); }
.supplier-order-page__shares span { display: flex; justify-content: flex-end; white-space: nowrap; }
.supplier-order-page__muted { color: var(--color-subtle); }
</style>
