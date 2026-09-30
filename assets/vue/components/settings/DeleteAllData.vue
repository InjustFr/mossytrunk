<script setup>
import { TriangleAlert } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import ConfirmButton from '../ui/ConfirmButton.vue';

const { t } = useI18n();

const emit = defineEmits(['delete-orders', 'delete-products']);
</script>

<template>
    <div class="delete-all-data">
        <p class="delete-all-data__warning">
            <TriangleAlert size="1rem" aria-hidden="true" />
            {{ t('settings.danger.warning') }}
        </p>
        <div class="delete-all-data__row">
            <div>
                <h3 class="delete-all-data__title">{{ t('settings.danger.orders.title') }}</h3>
                <p class="delete-all-data__detail">{{ t('settings.danger.orders.detail') }}</p>
            </div>
            <ConfirmButton
                :label="t('settings.danger.orders.action')"
                :confirm-label="t('settings.danger.confirm')"
                :message="t('settings.danger.orders.message')"
                variant="danger"
                @confirm="emit('delete-orders')"
            />
        </div>
        <div class="delete-all-data__row">
            <div>
                <h3 class="delete-all-data__title">{{ t('settings.danger.products.title') }}</h3>
                <p class="delete-all-data__detail">{{ t('settings.danger.products.detail') }}</p>
            </div>
            <ConfirmButton
                :label="t('settings.danger.products.action')"
                :confirm-label="t('settings.danger.confirm')"
                :message="t('settings.danger.products.message')"
                variant="danger"
                @confirm="emit('delete-products')"
            />
        </div>
    </div>
</template>

<style scoped>
.delete-all-data { display: flex; flex-direction: column; gap: var(--space-4); }
.delete-all-data__warning {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
.delete-all-data__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-3);
    padding-top: var(--space-4);
    border-top: 0.0625rem solid var(--color-border);
}
.delete-all-data__title { margin: 0; }
.delete-all-data__detail { margin: var(--space-1) 0 0; max-width: 36rem; color: var(--color-muted); font-size: 0.9rem; }
</style>
