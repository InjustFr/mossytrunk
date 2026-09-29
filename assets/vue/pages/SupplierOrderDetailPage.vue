<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import MoneyAmount from '../components/ui/MoneyAmount.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import SupplierOrderForm from '../components/purchasing/SupplierOrderForm.vue';
import SupplierOrderReception from '../components/purchasing/SupplierOrderReception.vue';
import { formatDate, formatDateTime } from '../composables/useDate.js';
import { visit } from '../composables/useNavigation.js';
import { useProducts } from '../composables/useProducts.js';
import { SUPPLIER_ORDER_STATUSES, useSupplierOrders, useSuppliers } from '../composables/usePurchasing.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    orderId: { type: String, required: true },
});

const { get, update, remove, receive } = useSupplierOrders();
const { suppliers, load: loadSuppliers, save: saveSupplier } = useSuppliers();
const { products, load: loadProducts } = useProducts();
const toast = useToast();

const order = ref(null);
const editOpen = ref(false);
const receiving = ref(false);
const isOrdered = computed(() => order.value?.status === 'ordered');
const status = computed(() => (order.value ? SUPPLIER_ORDER_STATUSES[order.value.status] : null));

const load = async () => { order.value = await get(props.orderId); };

async function saveAndReload(id, payload) {
    const supplier = await saveSupplier(id, payload);
    await loadSuppliers();
    return supplier;
}

async function onSaved() {
    editOpen.value = false;
    toast.success(`Commande ${order.value.reference} mise à jour.`);
    await load();
}

async function onRemove() {
    try {
        await remove(props.orderId);
        toast.success(`Commande ${order.value.reference} supprimée.`);
        visit('/commandes-fournisseurs');
    } catch (error) {
        toast.error(error.message);
    }
}

async function onReceived() {
    receiving.value = false;
    toast.success(`Commande ${order.value.reference} réceptionnée : le stock est à jour.`);
    await load();
}

onMounted(() => Promise.all([load(), loadSuppliers(), loadProducts()]));
</script>

<template>
    <AppLayout :title="order?.reference ?? 'Commande fournisseur'">
        <template #back><a class="back-link" href="/commandes-fournisseurs"><ArrowLeft size="0.875rem" aria-hidden="true" /> Commandes fournisseurs</a></template>
        <template #actions>
            <template v-if="isOrdered && !receiving">
                <ConfirmButton variant="ghost" label="Supprimer" :message="`La commande ${order.reference} sera supprimée définitivement.`" @confirm="onRemove" />
                <BaseButton variant="secondary" @click="editOpen = true">Modifier</BaseButton>
                <BaseButton @click="receiving = true">Réceptionner</BaseButton>
            </template>
        </template>

        <div v-if="order" class="supplier-order-page">
            <dl class="supplier-order-page__facts">
                <div><dt>Fournisseur</dt><dd>{{ order.supplier.name }}</dd></div>
                <div><dt>Commandée le</dt><dd>{{ formatDate(order.orderedOn) }}</dd></div>
                <div v-if="order.receivedAt"><dt>Reçue le</dt><dd>{{ formatDateTime(order.receivedAt) }}</dd></div>
                <div><dt>Total payé</dt><dd><MoneyAmount :cents="order.total" /></dd></div>
                <div><dt>Statut</dt><dd><StatusBadge :tone="status.tone">{{ status.label }}</StatusBadge></dd></div>
            </dl>

            <SupplierOrderReception v-if="receiving" :order="order" :submit="(lines) => receive(orderId, lines)" @received="onReceived" @cancel="receiving = false" />

            <BaseCard v-else>
                <table class="supplier-order-page__lines">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="supplier-order-page__number">Commandé</th>
                            <th class="supplier-order-page__number">Reçu</th>
                            <th class="supplier-order-page__number">Prix payé</th>
                            <th class="supplier-order-page__number">Coût unitaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in order.lines" :key="line.id">
                            <td>{{ line.label }}</td>
                            <td class="supplier-order-page__number">{{ line.orderedQuantity }}</td>
                            <td class="supplier-order-page__number">
                                <template v-if="line.receivedQuantity === null">—</template>
                                <template v-else>
                                    {{ line.receivedQuantity }}
                                    <StatusBadge v-if="line.receivedQuantity > line.orderedQuantity" tone="warning">+{{ line.receivedQuantity - line.orderedQuantity }}</StatusBadge>
                                    <StatusBadge v-else-if="line.receivedQuantity < line.orderedQuantity" tone="danger">{{ line.receivedQuantity - line.orderedQuantity }}</StatusBadge>
                                </template>
                            </td>
                            <td class="supplier-order-page__number"><MoneyAmount :cents="line.totalPrice" /></td>
                            <td class="supplier-order-page__number">
                                <template v-if="line.unitCost !== null">
                                    <MoneyAmount :cents="line.unitCost" />
                                    <span v-if="line.unitCost !== line.plannedUnitCost" class="supplier-order-page__planned">prévu <MoneyAmount :cents="line.plannedUnitCost" /></span>
                                </template>
                                <span v-else-if="line.receivedQuantity === 0" class="supplier-order-page__muted">Rien reçu</span>
                                <MoneyAmount v-else :cents="line.plannedUnitCost" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </BaseCard>
        </div>

        <BaseModal v-model:open="editOpen" title="Modifier la commande fournisseur" variant="drawer">
            <SupplierOrderForm
                v-if="order"
                :order="order"
                :products="products"
                :suppliers="suppliers"
                :save-supplier="saveAndReload"
                :submit="(payload) => update(orderId, payload)"
                @saved="onSaved"
                @cancel="editOpen = false"
            />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.supplier-order-page { display: flex; flex-direction: column; gap: var(--space-5); }
.supplier-order-page__facts { display: flex; gap: var(--space-6); flex-wrap: wrap; margin: 0; }
.supplier-order-page__facts dt { color: var(--color-muted); font-size: 0.8rem; }
.supplier-order-page__facts dd { margin: 0; font-weight: 600; }
.supplier-order-page__lines { width: 100%; border-collapse: collapse; }
.supplier-order-page__lines th { padding: var(--space-2); border-bottom: 0.0625rem solid var(--color-border); color: var(--color-muted); font-size: 0.7rem; font-weight: 600; letter-spacing: 0.04rem; text-align: left; text-transform: uppercase; }
.supplier-order-page__lines td { padding: var(--space-2); border-bottom: 0.0625rem solid var(--color-border); }
.supplier-order-page__number { text-align: right; font-variant-numeric: tabular-nums; }
.supplier-order-page__lines th.supplier-order-page__number { text-align: right; }
.supplier-order-page__lines .status-badge { margin-left: var(--space-1); }
.supplier-order-page__planned { display: block; color: var(--color-muted); font-size: 0.75rem; }
.supplier-order-page__muted { color: var(--color-subtle); }
</style>
