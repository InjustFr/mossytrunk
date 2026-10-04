<script setup>
import { onMounted, ref } from 'vue';
import { Plus } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import SalesChannelForm from '../components/channels/SalesChannelForm.vue';
import SalesChannels from '../components/channels/SalesChannels.vue';
import { useSalesChannels } from '../composables/useSalesChannels.js';
import { useServices } from '../composables/useServices.js';
import { useToast } from '../composables/useToast.js';

const { t } = useI18n();
const toast = useToast();
const salesChannels = useSalesChannels();
const services = useServices();

const editing = ref(null);
const modalOpen = ref(false);

function open(channel) {
    editing.value = channel;
    modalOpen.value = true;
}

const save = (payload) => (editing.value ? salesChannels.update(editing.value.id, payload) : salesChannels.create(payload));

async function onSaved(name) {
    toast.success(t(editing.value ? 'channels.updated' : 'channels.created', { name }));
    modalOpen.value = false;
    await salesChannels.load();
}

async function onRemoved(channel) {
    try {
        await salesChannels.remove(channel.id);
        toast.success(t('channels.removed', { name: channel.name }));
    } catch (error) {
        toast.error(error.message);
    }
    await salesChannels.load();
}

onMounted(() => Promise.all([salesChannels.load(), services.load()]));
</script>

<template>
    <AppLayout :title="t('channels.title')">
        <template #actions>
            <BaseButton @click="open(null)"><Plus size="1rem" aria-hidden="true" /> {{ t('channels.add') }}</BaseButton>
        </template>

        <BaseCard>
            <p class="channels-page__intro">{{ t('channels.intro') }}</p>
            <SalesChannels v-if="salesChannels.channels.value.length" :channels="salesChannels.channels.value" @edit="open" @remove="onRemoved" />
            <EmptyState v-else>{{ t('channels.empty') }}</EmptyState>
        </BaseCard>

        <BaseModal v-model:open="modalOpen" :title="editing ? t('channels.editTitle', { name: editing.name }) : t('channels.add')">
            <SalesChannelForm
                v-if="modalOpen"
                :key="editing?.id ?? 'new'"
                :channel="editing"
                :services="services.services.value"
                :submit="save"
                @saved="onSaved"
                @cancel="modalOpen = false"
            />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.channels-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); font-size: var(--font-size-md); }
</style>
