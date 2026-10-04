<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseSwitch from '../components/ui/BaseSwitch.vue';
import StockCountTable from '../components/stock/StockCountTable.vue';
import { useEvent } from '../composables/useEvents.js';
import { useStock } from '../composables/useStock.js';
import { useToast } from '../composables/useToast.js';
import { visit } from '../composables/useNavigation.js';
import FormError from '../components/ui/FormError.vue';
import BackLink from '../components/ui/BackLink.vue';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, load } = useEvent(props.eventId);
const { stockSheet, takeStockCheck } = useStock();
const toast = useToast();
const { t } = useI18n();

const sheet = ref([]);
const counts = reactive({});
const search = ref('');
const soldOnly = ref(true);
const saving = ref(false);
const error = ref(null);

const keyOf = (line) => `${line.productId}|${line.variant ?? ''}`;
const counted = computed(() => sheet.value.filter((line) => counts[keyOf(line)] !== null && counts[keyOf(line)] !== undefined));
const visible = computed(() => {
    const needle = search.value.trim().toLowerCase();
    return sheet.value.filter((line) => (!soldOnly.value || line.supply || line.soldAtEvent > 0 || counts[keyOf(line)] != null)
        && (needle === '' || line.label.toLowerCase().includes(needle)));
});

async function onSubmit() {
    saving.value = true;
    error.value = null;
    try {
        await takeStockCheck(props.eventId, counted.value.map((line) => ({ productId: line.productId, variant: line.variant, counted: counts[keyOf(line)] })));
        toast.success(t('stock.check.saved', counted.value.length));
        visit(`/events/${props.eventId}`);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await Promise.all([stockSheet(props.eventId, sheet), load()]);
    soldOnly.value = sheet.value.some((line) => line.soldAtEvent > 0);
});
</script>

<template>
    <AppLayout :title="t('stock.check.title')">
        <template #back><BackLink :href="`/events/${eventId}`">{{ event?.name ?? t('stock.check.eventFallback') }}</BackLink></template>
        <template #actions>
            <BaseButton :loading="saving" :disabled="counted.length === 0" @click="onSubmit">{{ t('stock.check.save') }}</BaseButton>
        </template>

        <BaseCard>
            <p class="stock-check-page__intro">
                {{ t('stock.check.intro') }}
            </p>
            <FormError v-if="error" class="stock-check-page__error">{{ error }}</FormError>
            <div class="stock-check-page__filters">
                <input v-model="search" class="control control--compact stock-check-page__search" type="search" :placeholder="t('stock.check.searchPlaceholder')" :aria-label="t('stock.check.searchLabel')">
                <label class="stock-check-page__sold-only"><BaseSwitch v-model="soldOnly" /> {{ t('stock.check.soldOnly') }}</label>
                <span class="stock-check-page__count">{{ t('stock.check.counted', counted.length) }}</span>
            </div>
            <StockCountTable :lines="visible" :counts="counts" :key-of="keyOf" />
        </BaseCard>
    </AppLayout>
</template>

<style scoped>
.stock-check-page__intro { margin: 0 0 var(--space-3); color: var(--color-muted); }
.stock-check-page__filters { display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap; margin-bottom: var(--space-3); }
.stock-check-page__search { min-width: 13.75rem; }
.stock-check-page__sold-only { display: inline-flex; align-items: center; gap: var(--space-2); font-size: var(--font-size-md); }
.stock-check-page__count { margin-left: auto; color: var(--color-muted); font-size: var(--font-size-md); }
.stock-check-page__error { margin-bottom: var(--space-3); }
</style>
