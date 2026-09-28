<script setup>
import { onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EventForm from '../components/events/EventForm.vue';
import EventHeader from '../components/events/EventHeader.vue';
import EventReport from '../components/events/EventReport.vue';
import ExpenseForm from '../components/events/ExpenseForm.vue';
import ExpenseList from '../components/events/ExpenseList.vue';
import { useEvent } from '../composables/useEvents.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, report, load, update, addExpense, reviseExpense, removeExpense } = useEvent(props.eventId);
const toast = useToast();
const editOpen = ref(false);
const expenseOpen = ref(false);
const editingExpense = ref(null);

const submitExpense = (payload) => (editingExpense.value ? reviseExpense(editingExpense.value.id, payload) : addExpense(payload));

function openExpense(expense = null) {
    editingExpense.value = expense;
    expenseOpen.value = true;
}

async function onEventSaved(name) {
    editOpen.value = false;
    toast.success(`Événement « ${name} » mis à jour.`);
    await load();
}

async function onExpenseSaved(label) {
    expenseOpen.value = false;
    toast.success(editingExpense.value ? `Dépense « ${label} » modifiée.` : `Dépense « ${label} » ajoutée.`);
    await load();
}

async function onExpenseRemoved(expense) {
    await removeExpense(expense.id);
    toast.success(`Dépense « ${expense.label} » supprimée.`);
    await load();
}

onMounted(load);
</script>

<template>
    <AppLayout :title="event?.name ?? 'Événement'">
        <template #back><a class="back-link" href="/evenements"><ArrowLeft size="0.875rem" aria-hidden="true" /> Événements</a></template>
        <template #actions>
            <template v-if="event">
                <BaseButton variant="secondary" @click="editOpen = true">Modifier</BaseButton>
                <BaseButton @click="openExpense()">Ajouter une dépense</BaseButton>
            </template>
        </template>

        <div v-if="event" class="event-detail-page">
            <EventHeader :event="event" />

            <EventReport v-if="report" :report="report" :event-id="event.id" :upcoming="event.timing === 'upcoming'" />

            <BaseCard title="Dépenses" class="event-detail-page__expenses">
                <ExpenseList :expenses="event.expenses" :total="event.expensesTotal" @edit="openExpense" @remove="onExpenseRemoved" />
            </BaseCard>
        </div>

        <BaseModal v-model:open="editOpen" title="Modifier l'événement">
            <EventForm v-if="event" :event="event" :submit="update" @saved="onEventSaved" @cancel="editOpen = false" />
        </BaseModal>
        <BaseModal v-model:open="expenseOpen" :title="editingExpense ? 'Modifier la dépense' : 'Nouvelle dépense'">
            <ExpenseForm :expense="editingExpense" :submit="submitExpense" @saved="onExpenseSaved" @cancel="expenseOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.event-detail-page { display: flex; flex-direction: column; gap: var(--space-5); }
.event-detail-page__expenses { max-width: 40rem; }
</style>
