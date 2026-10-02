<script setup>
import { Pencil, Trash2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import ConfirmButton from '../ui/ConfirmButton.vue';
import IconButton from '../ui/IconButton.vue';
import StatusBadge from '../ui/StatusBadge.vue';
import { costSummary } from '../../composables/useChannelCosts.js';

const { t } = useI18n();

defineProps({
    channels: { type: Array, required: true },
});
const emit = defineEmits(['edit', 'remove']);
</script>

<template>
    <ul class="sales-channels">
        <li v-for="channel in channels" :key="channel.id" class="sales-channels__row">
            <div class="sales-channels__text">
                <h3 class="sales-channels__name">
                    <a class="sales-channels__link" :href="`/channels/${channel.id}`">{{ channel.name }}</a>
                    <StatusBadge>{{ t(`channels.kinds.${channel.kind}`) }}</StatusBadge>
                    <StatusBadge v-if="channel.main" tone="success" :title="t('channels.mainHint')">{{ t('channels.main') }}</StatusBadge>
                </h3>
                <p class="sales-channels__service">
                    {{ channel.serviceLabel ? t('channels.linkedTo', { service: channel.serviceLabel }) : t('channels.notLinked') }}
                    <template v-if="channel.costs.length"> · {{ t('channels.costs.summary', { costs: costSummary(channel.costs) }) }}</template>
                </p>
            </div>
            <div class="sales-channels__actions">
                <IconButton :icon="Pencil" :label="t('channels.edit', { name: channel.name })" @click="emit('edit', channel)" />
                <ConfirmButton
                    v-if="!channel.main"
                    :icon="Trash2"
                    :label="t('channels.remove', { name: channel.name })"
                    :message="t('channels.removeMessage')"
                    @confirm="emit('remove', channel)"
                />
            </div>
        </li>
    </ul>
</template>

<style scoped>
.sales-channels { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }

.sales-channels__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-3) 0;
    border-top: 0.0625rem solid var(--color-border);
}

.sales-channels__row:first-child {
    border-top: none;
}

.sales-channels__text { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; }
.sales-channels__name { display: flex; align-items: center; flex-wrap: wrap; gap: var(--space-2); margin: 0; font-size: 0.9375rem; }
.sales-channels__link { color: inherit; text-decoration: none; }
.sales-channels__link:hover { text-decoration: underline; text-underline-offset: 0.1875rem; }
.sales-channels__service { margin: 0; color: var(--color-muted); font-size: 0.8125rem; }
.sales-channels__actions { display: flex; gap: var(--space-1); }
</style>
