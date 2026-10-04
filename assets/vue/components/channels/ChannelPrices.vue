<script setup>
import { Pencil } from '@lucide/vue';
import { VisuallyHidden } from 'reka-ui';
import { useI18n } from 'vue-i18n';
import DataTable from '../ui/DataTable.vue';
import EmptyState from '../ui/EmptyState.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { ownPriceOn, priceOn } from '../../composables/useChannelPrices.js';

const props = defineProps({
    channel: { type: Object, required: true },
    main: { type: Object, required: true },
    products: { type: Array, required: true },
});
const emit = defineEmits(['edit']);
const { t } = useI18n();

const follows = (product) => !props.channel.main && ownPriceOn(product, props.channel) === null;
</script>

<template>
    <EmptyState v-if="products.length === 0">{{ t('channels.prices.empty') }}</EmptyState>
    <DataTable v-else :items="products" remember-page>
        <template #head>
            <tr>
                <th scope="col">{{ t('channels.prices.product') }}</th>
                <th v-if="!channel.main" scope="col" class="data-table__cell--number">{{ t('channels.prices.mainPrice', { main: main.name }) }}</th>
                <th scope="col" class="data-table__cell--number">{{ t('channels.prices.price', { channel: channel.name }) }}</th>
                <th scope="col" class="data-table__cell--actions"><VisuallyHidden>{{ t('channels.prices.actions') }}</VisuallyHidden></th>
            </tr>
        </template>
        <template #default="{ rows: page }">
            <tr v-for="product in page" :key="product.id">
                <td>
                    <a class="channel-prices__product" :href="`/products/${product.id}`">{{ product.displayName }}</a>
                    <span class="channel-prices__reference">{{ product.reference }}</span>
                </td>
                <td v-if="!channel.main" class="data-table__cell--number"><MoneyAmount :cents="product.sellingPrice" /></td>
                <td :class="['data-table__cell--number', { 'channel-prices__follows': follows(product) }]" :title="follows(product) ? t('channels.prices.follows', { main: main.name }) : null">
                    <MoneyAmount :cents="priceOn(product, channel)" />
                </td>
                <td class="data-table__cell--actions">
                    <IconButton :icon="Pencil" :label="t('channels.prices.edit', { name: product.displayName })" @click="emit('edit', product)" />
                </td>
            </tr>
        </template>
    </DataTable>
</template>

<style scoped>
.channel-prices__product { color: inherit; text-decoration: none; font-weight: 500; }
.channel-prices__product:hover { text-decoration: underline; text-underline-offset: 0.1875rem; }
.channel-prices__reference { display: block; color: var(--color-muted); font-size: var(--font-size-sm); }
.channel-prices__follows { color: var(--color-muted); }
</style>
