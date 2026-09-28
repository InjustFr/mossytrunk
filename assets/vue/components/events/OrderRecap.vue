<script setup>
import { ref, watch } from 'vue';
import MoneyAmount from '../ui/MoneyAmount.vue';

// Sold articles as a nested accordion: type → product → variants. The list can be hidden (choice remembered).
defineProps({
    groups: { type: Array, required: true },
});

const STORAGE_KEY = 'mossytrunk.orderRecap.visible';
const readVisible = () => {
    try {
        return window.localStorage.getItem(STORAGE_KEY) !== 'false';
    } catch {
        return true;
    }
};
const visible = ref(readVisible());
watch(visible, (value) => {
    try {
        window.localStorage.setItem(STORAGE_KEY, String(value));
    } catch {
        // storage unavailable: keep the choice for this page only
    }
});
</script>

<template>
    <div class="order-recap">
        <label class="order-recap__toggle">
            <input v-model="visible" type="checkbox">
            Afficher le détail des articles
        </label>

        <Transition name="order-recap__list">
            <div v-if="visible && groups.length" class="order-recap__list" data-test="order-recap">
                <details v-for="group in groups" :key="group.type" class="order-recap__group">
                    <summary class="order-recap__row order-recap__row--group">
                        <span class="order-recap__chevron" aria-hidden="true">›</span>
                        <span class="order-recap__label">{{ group.type }}</span>
                        <span class="order-recap__quantity">{{ group.quantity }} art.</span>
                        <MoneyAmount class="order-recap__amount" :cents="group.sales" />
                    </summary>

                    <template v-for="product in group.products" :key="product.name">
                        <details v-if="product.variants.length" class="order-recap__product">
                            <summary class="order-recap__row order-recap__row--product">
                                <span class="order-recap__chevron" aria-hidden="true">›</span>
                                <span class="order-recap__label">{{ product.name }}</span>
                                <span class="order-recap__quantity">{{ product.quantity }}</span>
                                <MoneyAmount class="order-recap__amount" :cents="product.sales" />
                            </summary>
                            <div v-for="variant in product.variants" :key="variant.variant" class="order-recap__row order-recap__row--variant">
                                <span class="order-recap__label">{{ variant.variant }}</span>
                                <span class="order-recap__quantity">{{ variant.quantity }}</span>
                                <MoneyAmount class="order-recap__amount" :cents="variant.sales" />
                            </div>
                        </details>
                        <div v-else class="order-recap__row order-recap__row--product order-recap__row--leaf">
                            <span class="order-recap__label">
                                {{ product.name }}
                                <span v-if="product.unknownCost" class="order-recap__warning" title="Prix d'achat non renseigné (0 €)">⚠︎</span>
                            </span>
                            <span class="order-recap__quantity">{{ product.quantity }}</span>
                            <MoneyAmount class="order-recap__amount" :cents="product.sales" />
                        </div>
                    </template>
                </details>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.order-recap { display: flex; flex-direction: column; gap: var(--space-2); margin-top: var(--space-3); }

.order-recap__toggle { display: flex; align-items: center; gap: var(--space-2); font-size: 0.9rem; color: var(--color-muted); cursor: pointer; }
.order-recap__toggle input { accent-color: var(--color-accent); }

.order-recap__list { border-top: 1px solid var(--color-border); }

.order-recap__row {
    display: grid;
    grid-template-columns: 1em minmax(0, 1fr) 70px 100px;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) 0;
    border-bottom: 1px solid var(--color-border);
    list-style: none;
}

.order-recap__row::-webkit-details-marker { display: none; }
summary.order-recap__row { cursor: pointer; }
summary.order-recap__row:hover { background: #fafaf8; }

.order-recap__row--group { font-weight: 600; }
.order-recap__row--product { padding-left: var(--space-4); }
.order-recap__row--leaf { grid-template-columns: minmax(0, 1fr) 70px 100px; padding-left: calc(var(--space-4) + 1em + var(--space-2)); }
.order-recap__row--variant { grid-template-columns: minmax(0, 1fr) 70px 100px; padding-left: calc(var(--space-6) + 1em + var(--space-2)); color: var(--color-muted); }

.order-recap__chevron { color: var(--color-muted); transition: transform var(--transition); }
details[open] > summary > .order-recap__chevron { transform: rotate(90deg); }

.order-recap__quantity,
.order-recap__amount { text-align: right; font-variant-numeric: tabular-nums; }
.order-recap__warning { margin-left: var(--space-1); color: var(--color-warning); }

.order-recap__list-enter-active,
.order-recap__list-leave-active { transition: opacity var(--transition); }
.order-recap__list-enter-from,
.order-recap__list-leave-to { opacity: 0; }
</style>
