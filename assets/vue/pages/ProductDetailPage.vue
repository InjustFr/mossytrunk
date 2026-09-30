<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import MoneyAmount from '../components/ui/MoneyAmount.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import PriceHistory from '../components/products/PriceHistory.vue';
import ProductDesign from '../components/products/ProductDesign.vue';
import ProductForm from '../components/products/ProductForm.vue';
import ProductMovements from '../components/products/ProductMovements.vue';
import RestockForm from '../components/products/RestockForm.vue';
import StockHistory from '../components/products/StockHistory.vue';
import { useDesignBoard, useGabarits } from '../composables/useDesigns.js';
import { formatRatio } from '../composables/useMoney.js';
import { visit } from '../composables/useNavigation.js';
import { plural } from '../composables/usePlural.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useStock } from '../composables/useStock.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    productId: { type: String, required: true },
});

const { get, update, designProduct, savePrice, forgetPrice } = useProducts();
const { restock } = useStock();
const { gabarits, load: loadGabarits } = useGabarits();
const { board, load: loadBoard } = useDesignBoard();
const { load: loadTypes } = useProductTypes();
const toast = useToast();

const detail = ref(null);
const version = ref(0);
const editOpen = ref(false);
const restockOpen = ref(false);

const product = computed(() => detail.value?.product ?? null);
const margin = computed(() => (product.value && product.value.stockUnitCost > 0 ? product.value.sellingPrice - product.value.stockUnitCost : null));
const designs = computed(() => [...(board.value?.collections.flatMap((c) => c.designs) ?? []), ...(board.value?.standalone ?? [])]);

async function load() {
    detail.value = await get(props.productId);
    version.value += 1;
}

async function onSaved(name) {
    editOpen.value = false;
    toast.success(`Produit « ${name} » mis à jour.`);
    await load();
}

async function onRestocked({ quantity }) {
    restockOpen.value = false;
    toast.success(`${plural(quantity, 'unité ajoutée', 'unités ajoutées')} au stock.`);
    await load();
}

async function onPriceSaved(changeId, payload) {
    await savePrice(props.productId, changeId, payload);
    toast.success('Historique des prix mis à jour.');
    await load();
}

async function onPriceForgotten(changeId) {
    try {
        await forgetPrice(props.productId, changeId);
        toast.success('Prix supprimé de l\'historique.');
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

function onDesigned(designId) {
    toast.success('Design enregistré.');
    visit(`/designs/${designId}`);
}

onMounted(() => Promise.all([load(), loadGabarits(), loadBoard(), loadTypes()]));
</script>

<template>
    <AppLayout :title="product?.displayName ?? 'Produit'">
        <template #back><a class="back-link" href="/products"><ArrowLeft size="0.875rem" aria-hidden="true" /> Produits</a></template>
        <template #actions>
            <template v-if="product">
                <BaseButton variant="secondary" @click="editOpen = true">Modifier</BaseButton>
                <BaseButton @click="restockOpen = true">Réapprovisionner</BaseButton>
            </template>
        </template>

        <div v-if="product" class="product-page">
            <dl class="product-page__facts">
                <div><dt>Référence</dt><dd>{{ product.reference }}</dd></div>
                <div><dt>Type</dt><dd>{{ product.typeName ?? 'Sans type' }}</dd></div>
                <div><dt>Variantes</dt><dd>{{ product.variants.join(', ') || 'Unique' }}</dd></div>
                <div><dt>Prix de vente</dt><dd><MoneyAmount :cents="product.sellingPrice" /></dd></div>
                <div>
                    <dt>Coût du stock</dt>
                    <dd>
                        <MoneyAmount v-if="product.stockUnitCost > 0" :cents="product.stockUnitCost" />
                        <span v-else class="product-page__muted">Jamais acheté</span>
                    </dd>
                </div>
                <div>
                    <dt>Marge</dt>
                    <dd v-if="margin !== null"><MoneyAmount :cents="margin" /> <span class="product-page__muted">{{ formatRatio(margin, product.sellingPrice) }}</span></dd>
                    <dd v-else class="product-page__muted">—</dd>
                </div>
                <div>
                    <dt>En réserve</dt>
                    <dd>
                        {{ product.onHand }}
                        <StatusBadge v-if="product.negativeStock" tone="danger">Négatif</StatusBadge>
                        <StatusBadge v-else-if="product.lowStock" tone="warning">Stock bas</StatusBadge>
                    </dd>
                </div>
                <div><dt>Vendus</dt><dd>{{ product.unitsSold }} en {{ product.salesYear }} <span class="product-page__muted">({{ detail.unitsSoldEver }} au total)</span></dd></div>
            </dl>

            <div class="product-page__grid">
                <div class="product-page__main">
                    <BaseCard title="Réserve">
                        <StockHistory :key="version" :product="product" />
                    </BaseCard>
                    <BaseCard title="Mouvements">
                        <ProductMovements :movements="detail.movements" />
                    </BaseCard>
                </div>
                <aside class="product-page__side">
                    <BaseCard title="Prix de vente">
                        <PriceHistory :history="detail.priceHistory" :save="onPriceSaved" :forget="onPriceForgotten" />
                    </BaseCard>
                    <BaseCard title="Design">
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

        <BaseModal v-model:open="editOpen" title="Modifier le produit">
            <ProductForm v-if="product" :product="product" :submit="(payload) => update(productId, payload)" @saved="onSaved" @cancel="editOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="restockOpen" :title="`Réapprovisionner ${product?.displayName ?? ''}`">
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
