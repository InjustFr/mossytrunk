<script setup>
import { onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import EventForm from '../components/events/EventForm.vue';
import EventHeader from '../components/events/EventHeader.vue';
import EventReport from '../components/events/EventReport.vue';
import ExpenseForm from '../components/events/ExpenseForm.vue';
import ExpenseList from '../components/events/ExpenseList.vue';
import StockDiscrepancies from '../components/stock/StockDiscrepancies.vue';
import { useEvent } from '../composables/useEvents.js';
import { useStock } from '../composables/useStock.js';
import { useToast } from '../composables/useToast.js';
import { useTypeColors } from '../composables/useTypeColor.js';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { event, report, load, update, addExpense, reviseExpense, removeExpense } = useEvent(props.eventId);
const { colors: typeColors, load: loadTypes } = useTypeColors();
const toast = useToast();
const { stockChecks, dismiss } = useStock();
const checks = ref([]);
const loadChecks = async () => { checks.value = await stockChecks(props.eventId); };
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

async function onDismissed(line) {
    await dismiss(line.checkId, line.id);
    toast.success(`Écart de « ${line.label} » classé.`);
    await loadChecks();
}

async function onExpenseRemoved(expense) {
    await removeExpense(expense.id);
    toast.success(`Dépense « ${expense.label} » supprimée.`);
    await load();
}

onMounted(() => Promise.all([load(), loadTypes(), loadChecks()]));
</script>

<template>
    <AppLayout :title="event?.name ?? 'Événement'">
        <template #back><a class="back-link" href="/evenements"><ArrowLeft size="0.875rem" aria-hidden="true" /> Événements</a></template>
        <template #actions>
            <template v-if="event">
                <BaseButton variant="secondary" @click="editOpen = true">Modifier</BaseButton>
                <BaseButton variant="secondary" :href="`/evenements/${event.id}/inventaire`">Faire l'inventaire</BaseButton>
                <BaseButton @click="openExpense()">Ajouter une dépense</BaseButton>
            </template>
        </template>

        <div v-if="event" class="event-detail-page">
            <EventHeader :event="event" />
            <StockDiscrepancies :checks="checks" @dismiss="onDismissed" />

            <EventReport :report="report" :event-id="event.id" :upcoming="event.timing === 'upcoming'" :type-colors="typeColors">
                <template #aside>
                    <section class="event-detail-page__expenses" aria-labelledby="event-expenses">
                        <h3 id="event-expenses" class="event-detail-page__expenses-title">Dépenses</h3>
                        <ExpenseList :expenses="event.expenses" :total="event.expensesTotal" @edit="openExpense" @remove="onExpenseRemoved" />
                    </section>
                </template>
            </EventReport>
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
.event-detail-page__expenses-title { margin: 0 0 var(--space-2); font-size: 1.2rem; color: var(--color-muted); }
</style>
