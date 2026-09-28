<script setup>
import { onMounted } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import EventList from '../components/events/EventList.vue';
import EventForm from '../components/events/EventForm.vue';
import { useEvents } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const { events, load, create } = useEvents();
const toast = useToast();

async function onSaved(name) {
    toast.success(`Événement « ${name} » créé.`);
    await load();
}

onMounted(load);
</script>

<template>
    <AppLayout title="Événements">
        <div class="events-page">
            <EventList class="events-page__list" :events="events" />
            <BaseCard class="events-page__form">
                <EventForm :submit="create" @saved="onSaved" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.events-page {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: var(--space-4);
    align-items: start;
}

@media (max-width: 900px) {
    .events-page { grid-template-columns: 1fr; }
}
</style>
