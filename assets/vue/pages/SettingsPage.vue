<script setup>
import { onMounted } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import SumUpSettingsForm from '../components/settings/SumUpSettingsForm.vue';
import { useToast } from '../composables/useToast.js';
import { useWorkspaceSettings } from '../composables/useWorkspaceSettings.js';

const { settings, load, saveSumUp, removeSumUpApiKey } = useWorkspaceSettings();
const toast = useToast();

async function onSaved() {
    toast.success('Paramètres SumUp enregistrés.');
    await load();
}

async function onRemoved() {
    toast.success('Clé API SumUp supprimée.');
    await load();
}

onMounted(load);
</script>

<template>
    <AppLayout title="Paramètres">
        <div v-if="settings" class="settings-page">
            <p class="settings-page__workspace">Espace de travail <strong>{{ settings.name }}</strong></p>
            <BaseCard title="SumUp">
                <SumUpSettingsForm :sum-up="settings.sumUp" :submit="saveSumUp" :remove-api-key="removeSumUpApiKey" @saved="onSaved" @removed="onRemoved" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.settings-page { display: flex; flex-direction: column; gap: var(--space-4); }
.settings-page__workspace { margin: 0; color: var(--color-muted); }
</style>
