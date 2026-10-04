<script setup>
import { computed, onMounted, ref } from 'vue';
import { CircleCheck } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseSwitch from '../components/ui/BaseSwitch.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import ProgressBar from '../components/ui/ProgressBar.vue';
import BackLink from '../components/ui/BackLink.vue';
import OrderToCheck from '../components/orders/OrderToCheck.vue';
import { useEvent } from '../composables/useEvents.js';
import { useOrderCheck } from '../composables/useOrderCheck.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, load: loadEvent } = useEvent(props.eventId);
const orderCheck = useOrderCheck(props.eventId);
const toast = useToast();
const { t } = useI18n();

const orders = ref([]);
const toCheckOnly = ref(false);
const saving = ref(new Set());

const checkedCount = computed(() => orders.value.filter((order) => order.checked).length);
const allChecked = computed(() => orders.value.length > 0 && checkedCount.value === orders.value.length);
const visible = computed(() => (toCheckOnly.value ? orders.value.filter((order) => !order.checked) : orders.value));
const percent = computed(() => (orders.value.length ? Math.round((checkedCount.value / orders.value.length) * 100) : 0));
const stockCheckUrl = computed(() => `/events/${props.eventId}/stock-check`);

const setChecked = (id, checked) => {
    orders.value = orders.value.map((order) => (order.id === id ? { ...order, checked } : order));
};

async function onToggle(order) {
    const checked = !order.checked;
    setChecked(order.id, checked);
    saving.value = new Set([...saving.value, order.id]);
    const saved = await toast.attempt(() => (checked ? orderCheck.check(order.id) : orderCheck.uncheck(order.id)));
    if (!saved) setChecked(order.id, !checked);
    saving.value = new Set([...saving.value].filter((id) => id !== order.id));
}

onMounted(() => Promise.all([orderCheck.load(orders), loadEvent()]));
</script>

<template>
    <AppLayout :title="t('check.title')">
        <template #back><BackLink :href="`/events/${eventId}`">{{ event?.name ?? t('check.eventFallback') }}</BackLink></template>
        <template #actions>
            <BaseButton variant="secondary" :href="stockCheckUrl">{{ t('check.toStockCheck') }}</BaseButton>
        </template>

        <div class="order-check-page">
            <section v-if="allChecked" class="order-check-page__done" role="status">
                <CircleCheck class="order-check-page__done-icon" size="1.25rem" aria-hidden="true" />
                <p class="order-check-page__done-text">{{ t('check.allChecked', orders.length) }}</p>
                <BaseButton variant="secondary" :href="`/events/${eventId}`">{{ t('check.backToEvent') }}</BaseButton>
                <BaseButton :href="stockCheckUrl">{{ t('check.toStockCheck') }}</BaseButton>
            </section>

            <BaseCard>
                <p class="order-check-page__intro">{{ t('check.intro') }}</p>
                <div class="order-check-page__progress">
                    <ProgressBar :percent="percent" :label="t('check.progressLabel')" class="order-check-page__bar" />
                    <span class="order-check-page__count">{{ t('check.progress', { checked: checkedCount, total: orders.length }, orders.length) }}</span>
                    <label class="order-check-page__filter"><BaseSwitch v-model="toCheckOnly" /> {{ t('check.toCheckOnly') }}</label>
                </div>

                <EmptyState v-if="orders.length === 0">{{ t('check.empty') }}</EmptyState>
                <EmptyState v-else-if="visible.length === 0">{{ t('check.nothingLeft') }}</EmptyState>
                <ol v-else class="order-check-page__orders">
                    <li v-for="order in visible" :key="order.id"><OrderToCheck :order="order" :saving="saving.has(order.id)" @toggle="onToggle" /></li>
                </ol>

                <div class="actions-row order-check-page__actions">
                    <BaseButton variant="ghost" :href="`/events/${eventId}`">{{ t('check.stop') }}</BaseButton>
                    <BaseButton :href="stockCheckUrl">{{ t('check.toStockCheck') }}</BaseButton>
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.order-check-page { display: flex; flex-direction: column; gap: var(--space-4); }
.order-check-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); }
.order-check-page__progress { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-3) var(--space-4); margin-bottom: var(--space-4); }
.order-check-page__bar { flex: 1 1 12rem; }
.order-check-page__count { color: var(--color-ink); font-weight: 600; font-size: var(--font-size-md); }
.order-check-page__filter { display: inline-flex; align-items: center; gap: var(--space-2); font-size: var(--font-size-md); }
.order-check-page__orders { display: flex; flex-direction: column; gap: var(--space-2); margin: 0; padding: 0; list-style: none; }
.order-check-page__actions { margin-top: var(--space-4); }
.order-check-page__done { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-3); padding: var(--space-3) var(--space-4); border: 0.0625rem solid var(--color-accent); border-left-width: 0.25rem; border-radius: var(--radius); background: var(--color-accent-soft); }
.order-check-page__done-icon { flex: none; color: var(--color-accent-strong); }
.order-check-page__done-text { flex: 1; margin: 0; color: var(--color-accent-strong); font-weight: 600; }
</style>
