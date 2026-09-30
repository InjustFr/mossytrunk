<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import EventComparison from '../components/events/EventComparison.vue';
import EventList from '../components/events/EventList.vue';
import EventForm from '../components/events/EventForm.vue';
import { useEvents } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const { upcoming, past, load, create } = useEvents();
const toast = useToast();
const { t } = useI18n();
const modalOpen = ref(new URLSearchParams(window.location.search).has('new'));

async function onSaved(name) {
    toast.success(t('events.page.created', { name }));
    modalOpen.value = false;
    await load();
}

onMounted(load);
</script>

<template>
    <AppLayout :title="t('events.page.title')">
        <template #actions>
            <BaseButton @click="modalOpen = true">{{ t('events.page.new') }}</BaseButton>
        </template>

        <div class="events-page">
            <BaseCard :title="t('events.page.upcoming')">
                <EventList :events="upcoming" :empty-message="t('events.page.noUpcoming')" />
            </BaseCard>

            <BaseCard :title="t('events.page.past')">
                <EmptyState v-if="past.length === 0">{{ t('events.page.noPast') }}</EmptyState>
                <EventComparison v-else :events="past" />
            </BaseCard>
        </div>

        <BaseModal v-model:open="modalOpen" :title="t('events.page.new')">
            <EventForm :submit="create" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.events-page { display: flex; flex-direction: column; gap: var(--space-5); }
</style>
