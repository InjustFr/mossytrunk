<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EventList from '../components/events/EventList.vue';
import EventForm from '../components/events/EventForm.vue';
import { useEvents } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const { upcoming, past, load, create } = useEvents();
const toast = useToast();
const modalOpen = ref(false);

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

        <section class="events-page__section" aria-labelledby="events-upcoming">
            <h2 id="events-upcoming" class="events-page__heading">À venir</h2>
            <EventList :events="upcoming" empty-message="Aucun événement à venir. Créez-en un pour y rattacher des commandes." />
        </section>

        <section class="events-page__section" aria-labelledby="events-past">
            <h2 id="events-past" class="events-page__heading">Passés</h2>
            <EventList :events="past" empty-message="Aucun événement passé." />
        </section>

        <BaseModal v-model:open="modalOpen" title="Nouvel événement">
            <EventForm :submit="create" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.events-page__section + .events-page__section { margin-top: var(--space-6); }

.events-page__heading {
    margin-bottom: var(--space-3);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.09rem;
    text-transform: uppercase;
    color: var(--color-muted);
}
</style>
