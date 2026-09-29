<script setup>
import { onMounted, ref } from 'vue';
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
const modalOpen = ref(new URLSearchParams(window.location.search).has('nouveau'));

async function onSaved(name) {
    toast.success(`Événement « ${name} » créé.`);
    modalOpen.value = false;
    await load();
}

onMounted(load);
</script>

<template>
    <AppLayout title="Événements">
        <template #actions>
            <BaseButton @click="modalOpen = true">Nouvel événement</BaseButton>
        </template>

        <div class="events-page">
            <BaseCard title="À venir">
                <EventList :events="upcoming" empty-message="Aucun événement à venir. Créez-en un pour y rattacher des commandes." />
            </BaseCard>

            <BaseCard title="Passés">
                <EmptyState v-if="past.length === 0">Aucun événement passé.</EmptyState>
                <EventComparison v-else :events="past" />
            </BaseCard>
        </div>

        <BaseModal v-model:open="modalOpen" title="Nouvel événement">
            <EventForm :submit="create" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.events-page { display: flex; flex-direction: column; gap: var(--space-5); }
</style>
