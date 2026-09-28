<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EventList from '../components/events/EventList.vue';
import EventForm from '../components/events/EventForm.vue';
import { useEvents } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const { events, load, create } = useEvents();
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

        <EventList :events="events" />

        <BaseModal v-model:open="modalOpen" title="Nouvel événement">
            <EventForm :submit="create" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>
