<script setup>
import { computed, onMounted, ref } from 'vue';
import { Plus } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import BackLink from '../components/ui/BackLink.vue';
import ChannelCostForm from '../components/channels/ChannelCostForm.vue';
import ChannelCosts from '../components/channels/ChannelCosts.vue';
import ChannelBatchPriceForm from '../components/channels/ChannelBatchPriceForm.vue';
import ChannelPriceForm from '../components/channels/ChannelPriceForm.vue';
import ChannelPrices from '../components/channels/ChannelPrices.vue';
import ChannelSupplies from '../components/channels/ChannelSupplies.vue';
import SalesChannelForm from '../components/channels/SalesChannelForm.vue';
import { mainChannelOf } from '../composables/useChannelPrices.js';
import { visit } from '../composables/useNavigation.js';
import { listUrl, queryText } from '../composables/useQueryState.js';
import { useProducts } from '../composables/useProducts.js';
import { useSalesChannels } from '../composables/useSalesChannels.js';
import { useServices } from '../composables/useServices.js';
import { useToast } from '../composables/useToast.js';
import { normalize } from '../composables/useSearch.js';

const props = defineProps({
    channelId: { type: String, required: true },
});

const { t } = useI18n();
const toast = useToast();
const salesChannels = useSalesChannels();
const services = useServices();
const { activeArticles, activeSupplies, load: loadProducts, batchUpdate } = useProducts();

const channel = ref(null);
const search = queryText('q', '');
const editOpen = ref(false);
const batchOpen = ref(false);
const pricing = ref(null);
const priceOpen = ref(false);

function openPrice(product) {
    pricing.value = product;
    priceOpen.value = true;
}

const main = computed(() => mainChannelOf(salesChannels.channels.value));
const linkedService = computed(() => services.services.value.find((service) => service.key === channel.value?.service && service.connection) ?? null);
const shownProducts = computed(() => {
    const needle = normalize(search.value.trim());
    return activeArticles.value.filter((product) => needle === '' || normalize(`${product.displayName} ${product.reference}`).includes(needle));
});

const loadChannel = () => salesChannels.loadOne(props.channelId, channel);

async function onSaved(name) {
    editOpen.value = false;
    toast.success(t('channels.updated', { name }));
    await Promise.all([loadChannel(), salesChannels.load()]);
}

async function onRemoved() {
    if (await toast.attempt(() => salesChannels.remove(props.channelId), t('channels.removed', { name: channel.value.name }))) visit(listUrl('/channels'));
}

const editingCost = ref(null);
const costOpen = ref(false);

function openCost(cost) {
    editingCost.value = cost;
    costOpen.value = true;
}

const saveCost = (payload) => (editingCost.value ? salesChannels.reviseCost(props.channelId, editingCost.value.id, payload) : salesChannels.addCost(props.channelId, payload));

async function onCostSaved(label) {
    costOpen.value = false;
    toast.success(t(editingCost.value ? 'channels.costs.updated' : 'channels.costs.added', { label }));
    await loadChannel();
}

async function onCostRemoved(cost) {
    await salesChannels.removeCost(props.channelId, cost.id);
    toast.success(t('channels.costs.removed', { label: cost.label }));
    await loadChannel();
}

async function onSuppliesChanged(supplyIds) {
    await toast.attempt(() => salesChannels.offerSupplies(props.channelId, supplyIds), t('channels.supplies.saved'));
    await loadChannel();
}

async function onPriceSaved(name) {
    priceOpen.value = false;
    toast.success(t('channels.prices.saved', { name }));
    await loadProducts();
}

async function onBatchSaved(count) {
    batchOpen.value = false;
    toast.success(t('channels.prices.batchSaved', count));
    await loadProducts();
}

onMounted(() => Promise.all([loadChannel(), salesChannels.load(), loadProducts(), services.load()]));
</script>

<template>
    <AppLayout :title="channel?.name ?? t('channels.detail.fallbackTitle')">
        <template #back><BackLink :href="listUrl('/channels')">{{ t('channels.detail.back') }}</BackLink></template>
        <template #actions>
            <template v-if="channel">
                <ConfirmButton
                    v-if="!channel.main"
                    :label="t('channels.remove', { name: channel.name })"
                    :message="t('channels.removeMessage')"
                    @confirm="onRemoved"
                />
                <BaseButton variant="secondary" @click="editOpen = true">{{ t('common.edit') }}</BaseButton>
            </template>
        </template>

        <div v-if="channel && main" class="channel-page">
            <dl class="channel-page__facts">
                <div>
                    <dt>{{ t('channels.detail.kind') }}</dt>
                    <dd>
                        {{ t(`channels.kinds.${channel.kind}`) }}
                        <StatusBadge v-if="channel.main" tone="success" :title="t('channels.mainHint')">{{ t('channels.main') }}</StatusBadge>
                    </dd>
                </div>
                <div><dt>{{ t('channels.detail.service') }}</dt><dd>{{ channel.serviceLabel ?? t('common.none') }}</dd></div>
            </dl>

            <BaseCard :title="t('channels.costs.title')">
                <template #actions>
                    <BaseButton variant="secondary" @click="openCost(null)"><Plus size="1rem" aria-hidden="true" /> {{ t('channels.costs.add') }}</BaseButton>
                </template>
                <p class="channel-page__intro">{{ t('channels.costs.intro') }}</p>
                <ChannelCosts :costs="channel.costs" @edit="openCost" @remove="onCostRemoved" />
            </BaseCard>

            <BaseCard :title="t('channels.supplies.title')">
                <p class="channel-page__intro">{{ t('channels.supplies.intro') }}</p>
                <ChannelSupplies :supplies="activeSupplies" :offered="channel.supplies" @change="onSuppliesChanged" />
            </BaseCard>

            <BaseCard :title="t('channels.prices.title')">
                <template #actions>
                    <BaseButton variant="secondary" :disabled="shownProducts.length === 0" @click="batchOpen = true">{{ t('channels.prices.batch') }}</BaseButton>
                </template>
                <p class="channel-page__intro">{{ channel.main ? t('channels.prices.mainIntro') : t('channels.prices.intro', { main: main.name }) }}</p>
                <input v-model="search" class="control control--compact channel-page__search" type="search" :placeholder="t('channels.prices.search')" :aria-label="t('channels.prices.search')">
                <ChannelPrices :channel="channel" :main="main" :products="shownProducts" @edit="openPrice" />
            </BaseCard>
        </div>

        <BaseModal v-model:open="editOpen" :title="t('channels.editTitle', { name: channel?.name ?? '' })">
            <SalesChannelForm
                v-if="editOpen && channel"
                :channel="channel"
                :services="services.services.value"
                :submit="(payload) => salesChannels.update(channelId, payload)"
                @saved="onSaved"
                @cancel="editOpen = false"
            />
        </BaseModal>
        <BaseModal v-model:open="costOpen" :title="editingCost ? t('channels.costs.editTitle') : t('channels.costs.add')">
            <ChannelCostForm v-if="costOpen" :key="editingCost?.id ?? 'new'" :cost="editingCost" :submit="saveCost" @saved="onCostSaved" @cancel="costOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="priceOpen" :title="t('channels.prices.editTitle', { name: pricing?.displayName ?? '', channel: channel?.name ?? '' })">
            <ChannelPriceForm
                v-if="priceOpen && pricing && channel && main"
                :key="pricing.id"
                :product="pricing"
                :channel="channel"
                :main="main"
                :submit="(price) => salesChannels.setPrice(pricing.id, channelId, price)"
                @saved="onPriceSaved"
                @cancel="priceOpen = false"
            />
        </BaseModal>
        <BaseModal v-model:open="batchOpen" :title="t('channels.prices.batchTitle', { channel: channel?.name ?? '' })">
            <ChannelBatchPriceForm
                v-if="batchOpen && channel"
                :channel="channel"
                :channels="salesChannels.channels.value"
                :products="shownProducts"
                :submit="batchUpdate"
                @saved="onBatchSaved"
                @cancel="batchOpen = false"
            />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.channel-page { display: flex; flex-direction: column; gap: var(--space-5); }
.channel-page__facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr)); gap: var(--space-4) var(--space-5); margin: 0; }
.channel-page__facts dt { color: var(--color-muted); font-size: var(--font-size-sm); }
.channel-page__facts dd { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-1); margin: 0; font-weight: 600; }
.channel-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); font-size: var(--font-size-md); }

.channel-page__search { width: 100%; max-width: 20rem; margin-bottom: var(--space-4); }
</style>
