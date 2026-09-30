<script setup>
import { computed } from 'vue';
import { PackageSearch } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import ConfirmButton from '../ui/ConfirmButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import { formatDateTime } from '../../composables/useDate.js';

const props = defineProps({
    checks: { type: Array, required: true },
});
const emit = defineEmits(['dismiss']);
const { t } = useI18n();

const open = computed(() => props.checks.flatMap((check) => check.lines
    .filter((line) => line.unexplained > 0)
    .map((line) => ({ ...line, checkId: check.id, checkedAt: check.checkedAt }))));
const units = computed(() => open.value.reduce((sum, line) => sum + line.unexplained, 0));
const missedSales = computed(() => open.value.reduce((sum, line) => sum + line.missedSales, 0));
</script>

<template>
    <section v-if="open.length" class="stock-discrepancies" role="alert" aria-labelledby="stock-discrepancies-title">
        <PackageSearch class="stock-discrepancies__icon" size="1.25rem" aria-hidden="true" />
        <div class="stock-discrepancies__content">
            <i18n-t id="stock-discrepancies-title" keypath="stock.discrepancies.title" :plural="units" tag="h3" class="stock-discrepancies__title" scope="global">
                <template #count>{{ units }}</template>
                <template #amount><MoneyAmount :cents="missedSales" /></template>
            </i18n-t>
            <p class="stock-discrepancies__hint">
                {{ t('stock.discrepancies.hint') }}
            </p>
            <ul class="stock-discrepancies__lines">
                <li v-for="line in open" :key="line.id" class="stock-discrepancies__line">
                    <i18n-t keypath="stock.discrepancies.line" :plural="line.unexplained" tag="span" scope="global">
                        <template #label><strong>{{ line.label }}</strong></template>
                        <template #count>{{ line.unexplained }}</template>
                    </i18n-t>
                    <span class="stock-discrepancies__date">{{ t('stock.discrepancies.checkedAt', { date: formatDateTime(line.checkedAt) }) }}</span>
                    <ConfirmButton
                        variant="ghost"
                        :label="t('stock.discrepancies.dismiss')"
                        :message="t('stock.discrepancies.dismissMessage', { label: line.label })"
                        :confirm-label="t('stock.discrepancies.dismissConfirm')"
                        @confirm="emit('dismiss', line)"
                    />
                </li>
            </ul>
        </div>
    </section>
</template>

<style scoped>
.stock-discrepancies {
    display: flex;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 0.0625rem solid var(--color-warning);
    border-left-width: 0.25rem;
    border-radius: var(--radius);
    background: var(--color-warning-soft);
}

.stock-discrepancies__icon { flex: none; color: var(--color-warning); }
.stock-discrepancies__content { flex: 1; }
.stock-discrepancies__title { margin: 0; color: var(--color-warning); font-size: 1rem; }
.stock-discrepancies__hint { margin: var(--space-1) 0 var(--space-2); color: var(--color-text); font-size: 0.9rem; }
.stock-discrepancies__lines { display: flex; flex-direction: column; gap: var(--space-1); margin: 0; padding: 0; list-style: none; }
.stock-discrepancies__line { display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap; }
.stock-discrepancies__date { color: var(--color-muted); font-size: 0.8rem; }
</style>
