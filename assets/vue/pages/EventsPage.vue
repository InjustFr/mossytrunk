<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import EventComparison from '../components/events/EventComparison.vue';
import EventList from '../components/events/EventList.vue';
import EventForm from '../components/events/EventForm.vue';
import ResultBars from '../components/reporting/ResultBars.vue';
import { formatDate } from '../composables/useDate.js';
import { useEvents } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const { upcoming, past, load, create } = useEvents();
const toast = useToast();
const modalOpen = ref(false);

const resultBars = computed(() => [...past.value]
    .sort((a, b) => b.result - a.result)
    .map((event) => ({ id: event.id, label: event.name, meta: formatDate(event.startDate), value: event.result, href: `/evenements/${event.id}` })));

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
            <BaseCard>
                <section aria-labelledby="events-upcoming">
                    <h2 id="events-upcoming" class="events-page__heading">À venir</h2>
                    <EventList :events="upcoming" empty-message="Aucun événement à venir. Créez-en un pour y rattacher des commandes." />
                </section>
            </BaseCard>

            <section class="events-page__past" aria-labelledby="events-past">
                <h2 id="events-past" class="events-page__heading">Passés</h2>
                <EmptyState v-if="past.length === 0">Aucun événement passé.</EmptyState>
                <template v-else>
                    <BaseCard title="Résultat par événement">
                        <ResultBars :items="resultBars" label="Résultat par événement, du meilleur au moins bon" />
                    </BaseCard>
                    <BaseCard title="Comparaison">
                        <EventComparison :events="past" />
                    </BaseCard>
                </template>
            </section>
        </div>

        <BaseModal v-model:open="modalOpen" title="Nouvel événement">
            <EventForm :submit="create" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.events-page { display: flex; flex-direction: column; gap: var(--space-6); }
.events-page__past { display: flex; flex-direction: column; gap: var(--space-4); }
.events-page__heading { margin: 0 0 var(--space-2); font-family: var(--font-display); font-weight: 400; font-size: 1.35rem; }
</style>
