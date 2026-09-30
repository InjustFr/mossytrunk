<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft, Plus } from '@lucide/vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import BaseSwitch from '../components/ui/BaseSwitch.vue';
import ConfirmButton from '../components/ui/ConfirmButton.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import DeclinationCard from '../components/designs/DeclinationCard.vue';
import DesignForm from '../components/designs/DesignForm.vue';
import { useDesign, useDesignBoard, useGabarits } from '../composables/useDesigns.js';
import { formatDateTime } from '../composables/useDate.js';
import { visit } from '../composables/useNavigation.js';
import { plural } from '../composables/usePlural.js';
import { useToast } from '../composables/useToast.js';

const props = defineProps({
    designId: { type: String, required: true },
});

const { design, load, update, remove, workOn, decline, adjust, withdraw, tick, validate } = useDesign(props.designId);
const { board, load: loadBoard } = useDesignBoard();
const { gabarits, load: loadGabarits } = useGabarits();
const toast = useToast();
const editOpen = ref(false);

const validated = computed(() => design.value?.status === 'validated');
const available = computed(() => gabarits.value.filter((gabarit) => !design.value?.declinations.some((d) => d.gabarit.id === gabarit.id)));
const pending = computed(() => design.value?.declinations.filter((d) => !d.productId) ?? []);
const hasProducts = computed(() => (design.value?.declinations.length ?? 0) > pending.value.length);
const ready = computed(() => pending.value.length > 0 && pending.value.every((d) => d.ready));
const productNames = computed(() => pending.value.map((d) => d.displayName).join(', '));

async function run(action, success) {
    try {
        await action();
        if (success) toast.success(success);
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

const onDecline = (gabarit) => run(() => decline(gabarit.id), `Décliné en « ${gabarit.name} ».`);
const onTick = (declination, adaptation, done) => run(() => tick(declination.id, adaptation, done));
const onWithdraw = (declination) => run(() => withdraw(declination.id), `Déclinaison « ${declination.gabarit.name} » retirée.`);
const onBench = (current) => run(() => workOn(current), current ? 'Remis sur l\'établi.' : 'Mis de côté.');

async function submitAdjustment(declination, payload) {
    await adjust(declination.id, payload);
    toast.success(`Déclinaison « ${declination.gabarit.name} » enregistrée.`);
    await load();
}

async function onValidate() {
    try {
        const { productsCreated } = await validate();
        toast.success(`Sorti de l'atelier : ${plural(productsCreated, 'produit créé', 'produits créés')}.`);
        await load();
    } catch (error) {
        toast.error(error.message);
    }
}

async function onRemove() {
    await remove();
    toast.success(`Design « ${design.value.name} » supprimé.`);
    visit('/designs');
}

async function onSaved({ name }) {
    editOpen.value = false;
    toast.success(`Design « ${name} » mis à jour.`);
    await load();
}

onMounted(() => Promise.all([load(), loadGabarits(), loadBoard()]));
</script>

<template>
    <AppLayout :title="design?.name ?? 'Design'">
        <template #back><a class="back-link" href="/designs"><ArrowLeft size="0.875rem" aria-hidden="true" /> Créations</a></template>
        <template #actions>
            <template v-if="design">
                <ConfirmButton v-if="!hasProducts" variant="ghost" label="Supprimer" :message="`Le design « ${design.name} » et ses déclinaisons seront supprimés.`" @confirm="onRemove" />
                <BaseButton variant="secondary" @click="editOpen = true">Modifier</BaseButton>
                <ConfirmButton
                    v-if="ready"
                    variant="primary"
                    :label="hasProducts ? 'Créer les nouveaux produits' : 'Sortir de l\'atelier'"
                    confirm-label="Créer les produits"
                    :message="`Produits créés : ${productNames}. Ces déclinaisons ne pourront plus changer.`"
                    @confirm="onValidate"
                />
            </template>
        </template>

        <div v-if="design" class="design-page">
            <div class="design-page__meta">
                <span v-if="design.collection">Collection <strong>{{ design.collection.name }}</strong></span>
                <span v-else>Sans collection</span>
                <StatusBadge v-if="validated" tone="success">Sorti de l'atelier le {{ formatDateTime(design.validatedAt) }}</StatusBadge>
                <label v-if="!validated" class="design-page__bench"><BaseSwitch :model-value="design.current" @update:model-value="onBench" /> Sur l'établi</label>
            </div>
            <p v-if="design.notes" class="design-page__notes">{{ design.notes }}</p>

            <div class="design-page__decline">
                <span class="design-page__decline-label">Décliner sur</span>
                <BaseButton v-for="gabarit in available" :key="gabarit.id" variant="secondary" @click="onDecline(gabarit)">
                    <Plus size="0.875rem" aria-hidden="true" /> {{ gabarit.name }}
                </BaseButton>
                <span v-if="gabarits.length === 0" class="design-page__hint">Créez d'abord des gabarits depuis la page Créations.</span>
                <span v-else-if="available.length === 0" class="design-page__hint">Décliné sur tous les gabarits.</span>
            </div>

            <EmptyState v-if="design.declinations.length === 0">Choisissez les supports sur lesquels décliner ce design.</EmptyState>
            <div v-else class="design-page__declinations">
                <DeclinationCard
                    v-for="declination in design.declinations"
                    :key="declination.id"
                    :declination="declination"
                    :locked="Boolean(declination.productId)"
                    @tick="(adaptation, done) => onTick(declination, adaptation, done)"
                    :submit="(payload) => submitAdjustment(declination, payload)"
                    @withdraw="onWithdraw(declination)"
                />
            </div>
        </div>

        <BaseModal v-model:open="editOpen" title="Modifier le design">
            <DesignForm v-if="design" :design="design" :collections="board?.collections ?? []" :submit="update" @saved="onSaved" @cancel="editOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.design-page { display: flex; flex-direction: column; gap: var(--space-4); }
.design-page__meta { display: flex; align-items: center; gap: var(--space-4); color: var(--color-muted); }
.design-page__bench { display: inline-flex; align-items: center; gap: var(--space-2); color: var(--color-text); font-size: 0.9rem; }
.design-page__notes { max-width: 45rem; margin: 0; white-space: pre-line; }
.design-page__decline { display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap; }
.design-page__decline-label { font-weight: 600; }
.design-page__hint { color: var(--color-muted); font-size: 0.85rem; }
.design-page__declinations { display: grid; grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr)); gap: var(--space-4); align-items: start; }
</style>
