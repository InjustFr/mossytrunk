<script setup>
import { reactive, ref } from 'vue';
import { Pencil } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import EmptyState from '../ui/EmptyState.vue';
import FormField from '../ui/FormField.vue';
import IconButton from '../ui/IconButton.vue';

const props = defineProps({
    suppliers: { type: Array, required: true },
    save: { type: Function, required: true },
});
const emit = defineEmits(['saved']);

const editingId = ref(null);
const form = reactive({ name: '', contact: '', notes: '' });
const errors = ref({});
const saving = ref(false);

function edit(supplier) {
    editingId.value = supplier?.id ?? null;
    Object.assign(form, { name: supplier?.name ?? '', contact: supplier?.contact ?? '', notes: supplier?.notes ?? '' });
    errors.value = {};
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.save(editingId.value, { ...form });
        emit('saved', form.name);
        edit(null);
    } catch (error) {
        errors.value = error.fieldErrors ?? {};
        if (Object.keys(errors.value).length === 0) {
            errors.value = { name: error.message };
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="supplier-manager">
        <EmptyState v-if="suppliers.length === 0">Aucun fournisseur pour l'instant.</EmptyState>
        <ul v-else class="supplier-manager__list">
            <li v-for="supplier in suppliers" :key="supplier.id" :class="['supplier-manager__item', { 'supplier-manager__item--editing': supplier.id === editingId }]">
                <div>
                    <strong>{{ supplier.name }}</strong>
                    <span v-if="supplier.contact" class="supplier-manager__contact">{{ supplier.contact }}</span>
                    <p v-if="supplier.notes" class="supplier-manager__notes">{{ supplier.notes }}</p>
                </div>
                <IconButton :icon="Pencil" :label="`Modifier ${supplier.name}`" @click="edit(supplier)" />
            </li>
        </ul>

        <form class="supplier-manager__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <h3 class="supplier-manager__title">{{ editingId ? 'Modifier le fournisseur' : 'Ajouter un fournisseur' }}</h3>
                <FormField label="Nom" :error="errors.name">
                    <input v-model="form.name" type="text">
                </FormField>
                <FormField label="Contact" :error="errors.contact" hint="E-mail, téléphone, site…">
                    <input v-model="form.contact" type="text">
                </FormField>
                <FormField label="Notes" :error="errors.notes">
                    <textarea v-model="form.notes" rows="2" />
                </FormField>
                <div class="supplier-manager__actions">
                    <BaseButton v-if="editingId" variant="ghost" @click="edit(null)">Annuler</BaseButton>
                    <BaseButton type="submit" :loading="saving">{{ editingId ? 'Enregistrer' : 'Ajouter le fournisseur' }}</BaseButton>
                </div>
            </fieldset>
        </form>
    </div>
</template>

<style scoped>
.supplier-manager { display: flex; flex-direction: column; gap: var(--space-4); }
.supplier-manager__list { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.supplier-manager__item { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 0.0625rem solid var(--color-border); }
.supplier-manager__item--editing { background: var(--color-accent-soft); }
.supplier-manager__contact { display: block; color: var(--color-muted); font-size: 0.85rem; }
.supplier-manager__notes { margin: var(--space-1) 0 0; color: var(--color-muted); font-size: 0.85rem; white-space: pre-line; }
.supplier-manager__form { display: flex; flex-direction: column; gap: var(--space-3); }
.supplier-manager__title { margin: 0; font-size: 1rem; }
.supplier-manager__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
