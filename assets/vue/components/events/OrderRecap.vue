<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronRight, TriangleAlert } from '@lucide/vue';
import { CollapsibleContent, CollapsibleRoot, CollapsibleTrigger } from 'reka-ui';
import BaseCheckbox from '../ui/BaseCheckbox.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import TypeMark from '../ui/TypeMark.vue';

defineProps({
    groups: { type: Array, required: true },
    typeColors: { type: Map, required: true },
});

const { t } = useI18n();

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
            <BaseCheckbox v-model="visible" />
            {{ t('events.recap.showItems') }}
        </label>

        <Transition name="fade">
            <div v-if="visible && groups.length" class="order-recap__list" data-test="order-recap">
                <CollapsibleRoot v-for="group in groups" :key="group.type ?? ''" class="order-recap__group">
                    <CollapsibleTrigger class="order-recap__row order-recap__row--group">
                        <ChevronRight class="order-recap__chevron" size="0.875rem" aria-hidden="true" />
                        <span class="order-recap__label order-recap__label--type"><TypeMark :color="typeColors.get(group.type)" />{{ group.type ?? t('events.recap.untyped') }}</span>
                        <span class="order-recap__quantity">{{ t('events.recap.itemCount', { count: group.quantity }) }}</span>
                        <MoneyAmount class="order-recap__amount" :cents="group.sales" />
                    </CollapsibleTrigger>
                    <CollapsibleContent>

                    <template v-for="product in group.products" :key="product.name">
                        <CollapsibleRoot v-if="product.variants.length" class="order-recap__product">
                            <CollapsibleTrigger class="order-recap__row order-recap__row--product">
                                <ChevronRight class="order-recap__chevron" size="0.875rem" aria-hidden="true" />
                                <span class="order-recap__label">{{ product.name }}</span>
                                <span class="order-recap__quantity">{{ product.quantity }}</span>
                                <MoneyAmount class="order-recap__amount" :cents="product.sales" />
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <div v-for="variant in product.variants" :key="variant.variant" class="order-recap__row order-recap__row--variant">
                                <span class="order-recap__label">{{ variant.variant }}</span>
                                <span class="order-recap__quantity">{{ variant.quantity }}</span>
                                <MoneyAmount class="order-recap__amount" :cents="variant.sales" />
                                </div>
                            </CollapsibleContent>
                        </CollapsibleRoot>
                        <div v-else class="order-recap__row order-recap__row--product order-recap__row--leaf">
                            <span class="order-recap__label">
                                {{ product.name }}
                                <TriangleAlert v-if="product.unknownCost" class="order-recap__warning" size="0.875rem" :aria-label="t('events.recap.unknownCost')" role="img" />
                            </span>
                            <span class="order-recap__quantity">{{ product.quantity }}</span>
                            <MoneyAmount class="order-recap__amount" :cents="product.sales" />
                        </div>
                    </template>
                    </CollapsibleContent>
                </CollapsibleRoot>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.order-recap { display: flex; flex-direction: column; gap: var(--space-2); margin-top: var(--space-3); }

.order-recap__toggle { display: flex; align-items: center; gap: var(--space-2); font-size: 0.9rem; color: var(--color-muted); cursor: pointer; }

.order-recap__list { border-top: 0.0625rem solid var(--color-border); }

.order-recap__row {
    display: grid;
    grid-template-columns: 0.9375rem minmax(0, 1fr) 4.375rem 6.25rem;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) 0;
    border-bottom: 0.0625rem solid var(--color-border);
}

button.order-recap__row {
    width: 100%;
    border-width: 0 0 0.0625rem;
    border-color: var(--color-border);
    background: none;
    color: inherit;
    font: inherit;
    text-align: left;
    cursor: pointer;
}

button.order-recap__row:hover { background: var(--color-hover); }
button.order-recap__row:focus-visible { outline: 0.125rem solid var(--color-accent); outline-offset: -0.125rem; }

.order-recap__row--group { font-weight: 600; }
.order-recap__label--type { display: inline-flex; align-items: center; gap: var(--space-2); }
.order-recap__row--product { padding-left: var(--space-4); }
.order-recap__row--leaf { grid-template-columns: minmax(0, 1fr) 4.375rem 6.25rem; padding-left: calc(var(--space-4) + 0.9375rem + var(--space-2)); }
.order-recap__row--variant { grid-template-columns: minmax(0, 1fr) 4.375rem 6.25rem; padding-left: calc(var(--space-6) + 0.9375rem + var(--space-2)); color: var(--color-muted); }

.order-recap__chevron { color: var(--color-muted); transition: transform var(--transition); }
.order-recap__row[data-state="open"] > .order-recap__chevron { transform: rotate(90deg); }

.order-recap__quantity,
.order-recap__amount { text-align: right; font-variant-numeric: tabular-nums; }
.order-recap__warning { margin-left: var(--space-1); color: var(--color-warning); vertical-align: -0.125rem; }
</style>
