<script setup>
import { reactive, ref } from 'vue';
import { Pencil } from '@lucide/vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseMoneyField from '../ui/BaseMoneyField.vue';
import EmptyState from '../ui/EmptyState.vue';
import FormField from '../ui/FormField.vue';
import IconButton from '../ui/IconButton.vue';
import MoneyAmount from '../ui/MoneyAmount.vue';
import TypeSelect from '../products/TypeSelect.vue';
import VariantsInput from '../products/VariantsInput.vue';

const props = defineProps({
    gabarits: { type: Array, required: true },
    save: { type: Function, required: true },
});
const emit = defineEmits(['saved']);

const empty = () => ({ name: '', typeId: '', sellingPrice: null, buyingPrice: 0, variants: [], adaptations: [] });
const editingId = ref(null);
const form = reactive(empty());
const errors = ref({});
const saving = ref(false);

function edit(gabarit) {
    editingId.value = gabarit?.id ?? null;
    Object.assign(form, gabarit
        ? { name: gabarit.name, typeId: gabarit.typeId ?? '', sellingPrice: gabarit.sellingPrice, buyingPrice: gabarit.buyingPrice, variants: [...gabarit.variants], adaptations: [...gabarit.adaptations] }
        : empty());
    errors.value = {};
}

async function onSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        await props.save(editingId.value, { ...form, typeId: form.typeId || null, sellingPrice: form.sellingPrice ?? -1, buyingPrice: form.buyingPrice ?? 0 });
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
    <div class="gabarit-manager">
        <EmptyState v-if="gabarits.length === 0">Aucun gabarit. Un gabarit décrit un support (tirage 15×15, sticker brillant…) et ce qu'il faut adapter pour y poser un design.</EmptyState>
        <ul v-else class="gabarit-manager__list">
            <li v-for="gabarit in gabarits" :key="gabarit.id" :class="['gabarit-manager__item', { 'gabarit-manager__item--editing': gabarit.id === editingId }]">
                <div class="gabarit-manager__summary">
                    <strong>{{ gabarit.name }}</strong>
                    <span class="gabarit-manager__meta">
                        {{ gabarit.typeName ?? 'Sans type' }}, <MoneyAmount :cents="gabarit.sellingPrice" />
                        <template v-if="gabarit.variants.length">, {{ gabarit.variants.join(', ') }}</template>
                    </span>
                    <span v-if="gabarit.adaptations.length" class="gabarit-manager__meta">À adapter : {{ gabarit.adaptations.join(', ') }}</span>
                </div>
                <IconButton :icon="Pencil" :label="`Modifier ${gabarit.name}`" @click="edit(gabarit)" />
            </li>
        </ul>

        <form class="gabarit-manager__form" novalidate @submit.prevent="onSubmit">
            <fieldset class="form-lock" :disabled="saving">
                <h3 class="gabarit-manager__title">{{ editingId ? 'Modifier le gabarit' : 'Nouveau gabarit' }}</h3>
                <FormField label="Nom du gabarit" :error="errors.name" hint="Ex. « Tirage 15×15 », « Sticker mat »">
                    <input v-model="form.name" type="text">
                </FormField>
                <FormField as="group" label="Type des produits créés" :error="errors.typeId">
                    <TypeSelect v-model="form.typeId" />
                </FormField>
                <div class="gabarit-manager__row">
                    <FormField label="Prix de vente (€)" :error="errors.sellingPrice">
                        <BaseMoneyField v-model="form.sellingPrice" />
                    </FormField>
                    <FormField label="Prix d'achat (€)" :error="errors.buyingPrice">
                        <BaseMoneyField v-model="form.buyingPrice" />
                    </FormField>
                </div>
                <FormField as="group" label="Variantes proposées" :error="errors.variants">
                    <VariantsInput v-model="form.variants" />
                </FormField>
                <FormField as="group" label="Adaptations à faire" :error="errors.adaptations" hint="La liste à cocher de chaque déclinaison sur ce gabarit.">
                    <VariantsInput v-model="form.adaptations" input-label="Nouvelle adaptation" placeholder="Ex. fond perdu 3 mm, puis Entrée" item-label="l'adaptation" />
                </FormField>
                <div class="gabarit-manager__actions">
                    <BaseButton v-if="editingId" variant="ghost" @click="edit(null)">Annuler</BaseButton>
                    <BaseButton type="submit" :loading="saving">{{ editingId ? 'Enregistrer' : 'Ajouter le gabarit' }}</BaseButton>
                </div>
            </fieldset>
        </form>
    </div>
</template>

<style scoped>
.gabarit-manager { display: flex; flex-direction: column; gap: var(--space-4); }
.gabarit-manager__list { display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none; }
.gabarit-manager__item { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 0.0625rem solid var(--color-border); }
.gabarit-manager__item--editing { background: var(--color-accent-soft); }
.gabarit-manager__summary { display: flex; flex-direction: column; }
.gabarit-manager__meta { color: var(--color-muted); font-size: 0.85rem; }
.gabarit-manager__form { display: flex; flex-direction: column; gap: var(--space-3); }
.gabarit-manager__title { margin: 0; font-size: 1rem; }
.gabarit-manager__row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); }
.gabarit-manager__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
