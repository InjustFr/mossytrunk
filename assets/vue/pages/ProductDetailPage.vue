<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft, Pencil } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import IconButton from '../components/ui/IconButton.vue';
import MoneyAmount from '../components/ui/MoneyAmount.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import ChannelPriceForm from '../components/channels/ChannelPriceForm.vue';
import PriceHistory from '../components/products/PriceHistory.vue';
import ProductDesign from '../components/products/ProductDesign.vue';
import ProductForm from '../components/products/ProductForm.vue';
import ProductMovements from '../components/products/ProductMovements.vue';
import RestockForm from '../components/products/RestockForm.vue';
import StockHistory from '../components/products/StockHistory.vue';
import { useDesignBoard, useGabarits } from '../composables/useDesigns.js';
import { formatRatio } from '../composables/useMoney.js';
import { visit } from '../composables/useNavigation.js';
import { listUrl } from '../composables/useQueryState.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useSalesChannels } from '../composables/useSalesChannels.js';
import { mainChannelOf, otherChannelsOf, ownPriceOn, priceOn } from '../composables/useChannelPrices.js';
import { useStock } from '../composables/useStock.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    productId: { type: String, required: true },
});

const { loadOne, update, designProduct, savePrice, forgetPrice } = useProducts();
const { restock } = useStock();
const { gabarits, load: loadGabarits } = useGabarits();
const { board, load: loadBoard } = useDesignBoard();
const { load: loadTypes } = useProductTypes();
const { channels, load: loadChannels, setPrice } = useSalesChannels();
const toast = useToast();
const { t } = useI18n();

const detail = ref(null);
const version = ref(0);
const editOpen = ref(false);
const restockOpen = ref(false);
const pricingChannel = ref(null);
const channelPriceOpen = computed({ get: () => pricingChannel.value !== null, set: (open) => { if (!open) pricingChannel.value = null; } });
const mainChannel = computed(() => mainChannelOf(channels.value));
const mainPriceLabel = computed(() => (mainChannel.value ? t('products.detail.mainPrice', { channel: mainChannel.value.name }) : t('products.detail.sellingPrice')));

const product = computed(() => detail.value?.product ?? null);
const supply = computed(() => product.value?.kind === 'supply');
const margin = computed(() => (product.value && !supply.value && product.value.stockUnitCost > 0 ? product.value.sellingPrice - product.value.stockUnitCost : null));
const designs = computed(() => [...(board.value?.collections.flatMap((c) => c.designs) ?? []), ...(board.value?.standalone ?? [])]);

async function load() {
    await loadOne(props.productId, detail);
    version.value += 1;
}

async function onSaved(name) {
    editOpen.value = false;
    toast.success(t('products.toast.updated', { name }));
    await load();
}

async function onChannelPriceSaved(name) {
    toast.success(t('channels.prices.saved', { name }));
    pricingChannel.value = null;
    await load();
}

async function onRestocked({ quantity }) {
    restockOpen.value = false;
    toast.success(t('products.toast.restocked', quantity));
    await load();
}

async function onPriceSaved(changeId, payload) {
    await savePrice(props.productId, changeId, payload);
    toast.success(t('products.toast.priceSaved'));
    await load();
}

async function onPriceForgotten(changeId) {
    try {
        await forgetPrice(props.productId, changeId);
        toast.success(t('products.toast.priceForgotten'));
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

function onDesigned(designId) {
    toast.success(t('products.toast.designed'));
    visit(`/designs/${designId}`);
}

onMounted(() => Promise.all([load(), loadGabarits(), loadBoard(), loadTypes(), loadChannels()]));
</script>

<template>
    <AppLayout :title="product?.displayName ?? t('products.detail.fallbackTitle')">
        <template #back><a class="back-link" :href="listUrl('/products')"><ArrowLeft size="0.875rem" aria-hidden="true" /> {{ t('products.detail.back') }}</a></template>
        <template #actions>
            <template v-if="product">
                <BaseButton variant="secondary" @click="editOpen = true">{{ t('products.detail.edit') }}</BaseButton>
                <BaseButton @click="restockOpen = true">{{ t('products.detail.restock') }}</BaseButton>
            </template>
        </template>

        <div v-if="product" class="product-page">
            <dl class="product-page__facts">
                <div><dt>{{ t('products.detail.reference') }}</dt><dd>{{ product.reference }}</dd></div>
                <div><dt>{{ t('products.detail.type') }}</dt><dd>{{ product.typeName }}</dd></div>
                <div><dt>{{ t('products.detail.variants') }}</dt><dd>{{ product.variants.join(', ') || t('products.single') }}</dd></div>
                <div v-if="supply"><dt>{{ t('products.form.kind') }}</dt><dd><StatusBadge :title="t('products.detail.supplyHint')">{{ t('products.detail.supply') }}</StatusBadge></dd></div>
                <div v-if="!supply"><dt>{{ mainPriceLabel }}</dt><dd><MoneyAmount :cents="product.sellingPrice" /></dd></div>
                <div v-for="channel in supply ? [] : otherChannelsOf(channels)" :key="channel.id">
                    <dt>{{ t('products.form.channelPrice', { channel: channel.name }) }}</dt>
                    <dd>
                        <MoneyAmount :cents="priceOn(product, channel)" />
                        <span v-if="ownPriceOn(product, channel) === null" class="product-page__muted"> · {{ t('products.list.followsSellingPrice', { channel: mainChannel?.name ?? '' }) }}</span>
                        <IconButton :icon="Pencil" :label="t('products.detail.editChannelPrice', { channel: channel.name })" @click="pricingChannel = channel" />
                    </dd>
                </div>
                <div>
                    <dt>{{ t('products.detail.stockCost') }}</dt>
                    <dd>
                        <MoneyAmount v-if="product.stockUnitCost > 0" :cents="product.stockUnitCost" />
                        <span v-else class="product-page__muted">{{ t('products.detail.neverBought') }}</span>
                    </dd>
                </div>
                <div v-if="!supply">
                    <dt>{{ t('products.detail.margin') }}</dt>
                    <dd v-if="margin !== null"><MoneyAmount :cents="margin" /> <span class="product-page__muted">{{ formatRatio(margin, product.sellingPrice) }}</span></dd>
                    <dd v-else class="product-page__muted">—</dd>
                </div>
                <div>
                    <dt>{{ t('products.detail.onHand') }}</dt>
                    <dd>
                        {{ product.onHand }}
                        <StatusBadge v-if="product.negativeStock" tone="danger">{{ t('products.negative') }}</StatusBadge>
                        <StatusBadge v-else-if="product.lowStock" tone="warning">{{ t('products.lowStock') }}</StatusBadge>
                    </dd>
                </div>
                <div v-if="!supply"><dt>{{ t('products.detail.sold') }}</dt><dd>{{ t('products.detail.soldIn', { count: product.unitsSold, year: product.salesYear }) }} <span class="product-page__muted">{{ t('products.detail.soldEver', { count: detail.unitsSoldEver }) }}</span></dd></div>
            </dl>

            <div class="product-page__grid">
                <div class="product-page__main">
                    <BaseCard :title="t('products.detail.stock')">
                        <StockHistory :key="version" :product="product" />
                    </BaseCard>
                    <BaseCard :title="t('products.detail.movements')">
                        <ProductMovements :movements="detail.movements" />
                    </BaseCard>
                </div>
                <aside class="product-page__side">
                    <BaseCard v-if="!supply" :title="mainPriceLabel">
                        <PriceHistory :history="detail.priceHistory" :save="onPriceSaved" :forget="onPriceForgotten" />
                    </BaseCard>
                    <BaseCard v-if="!supply" :title="t('products.detail.design')">
                        <ProductDesign
                            :product="product"
                            :design="detail.design"
                            :gabarits="gabarits"
                            :designs="designs"
                            :submit="(payload) => designProduct(productId, payload)"
                            @designed="onDesigned"
                        />
                    </BaseCard>
                </aside>
            </div>
        </div>

        <BaseModal v-model:open="editOpen" :title="t('products.page.editTitle')">
            <ProductForm v-if="product" :product="product" :channels="channels" :submit="(payload) => update(productId, payload)" @saved="onSaved" @cancel="editOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="channelPriceOpen" :title="t('channels.prices.editTitle', { name: product?.displayName ?? '', channel: pricingChannel?.name ?? '' })">
            <ChannelPriceForm
                v-if="product && pricingChannel && mainChannel"
                :key="pricingChannel.id"
                :product="product"
                :channel="pricingChannel"
                :main="mainChannel"
                :submit="(price) => setPrice(productId, pricingChannel.id, price)"
                @saved="onChannelPriceSaved"
                @cancel="pricingChannel = null"
            />
        </BaseModal>
        <BaseModal v-model:open="restockOpen" :title="t('products.page.restockTitle', { name: product?.displayName ?? '' })">
            <RestockForm v-if="product" :product="product" :submit="restock" @saved="onRestocked" @cancel="restockOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.product-page { display: flex; flex-direction: column; gap: var(--space-5); }
.product-page__facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr)); gap: var(--space-4) var(--space-5); margin: 0; }
.product-page__facts dt { color: var(--color-muted); font-size: 0.8rem; }
.product-page__facts dd { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-1); margin: 0; font-weight: 600; font-variant-numeric: tabular-nums; }
.product-page__muted { color: var(--color-muted); font-weight: 400; }
.product-page__grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(16rem, 1fr); gap: var(--space-5); align-items: start; }
.product-page__main, .product-page__side { display: flex; flex-direction: column; gap: var(--space-5); }

@media (max-width: 60rem) {
    .product-page__grid { grid-template-columns: 1fr; }
}
</style>
