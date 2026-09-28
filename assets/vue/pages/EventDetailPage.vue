<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import EventForm from '../components/events/EventForm.vue';
import EventHeader from '../components/events/EventHeader.vue';
import ExpenseList from '../components/events/ExpenseList.vue';
import { useEvent } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, load, update, addExpense, removeExpense } = useEvent(props.eventId);
const toast = useToast();
const editing = ref(false);

async function refresh(message) {
    toast.success(message);
    await load();
}

async function onEventSaved(name) {
    editing.value = false;
    await refresh(`Événement « ${name} » mis à jour.`);
}

onMounted(load);
</script>

<template>
    <AppLayout :title="event?.name ?? 'Événement'">
        <template #back><a class="event-detail-page__back" href="/evenements">← Événements</a></template>
        <template #actions>
            <BaseButton v-if="event && !editing" variant="secondary" @click="editing = true">Modifier</BaseButton>
        </template>

        <div v-if="event" class="event-detail-page">
            <BaseCard v-if="editing">
                <EventForm :event="event" :submit="update" @saved="onEventSaved" @cancel="editing = false" />
            </BaseCard>
            <EventHeader v-else :event="event" />

            <BaseCard title="Dépenses">
                <ExpenseList
                    :expenses="event.expenses"
                    :total="event.expensesTotal"
                    :add="addExpense"
                    :remove="removeExpense"
                    @changed="refresh"
                />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.event-detail-page { display: flex; flex-direction: column; gap: var(--space-4); }
.event-detail-page__back { color: var(--color-muted); text-decoration: none; }
.event-detail-page__back:hover { color: var(--color-text); }
</style>
