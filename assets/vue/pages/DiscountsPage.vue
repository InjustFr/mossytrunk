<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import DiscountRuleForm from '../components/discounts/DiscountRuleForm.vue';
import DiscountRuleList from '../components/discounts/DiscountRuleList.vue';
import { useDiscountRules } from '../composables/useDiscountRules.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { useToast } from '../composables/useToast.js';

const { rules, load, create, update, setActive, remove } = useDiscountRules();
const { products, load: loadProducts } = useProducts();
const { types, load: loadTypes } = useProductTypes();
const toast = useToast();
const modalOpen = ref(false);
const editing = ref(null);

const modalTitle = computed(() => (editing.value ? 'Modifier la remise' : 'Nouvelle remise'));
const submit = (payload) => (editing.value ? update(editing.value.id, payload) : create(payload));

function openCreate() {
    editing.value = null;
    modalOpen.value = true;
}

function openEdit(rule) {
    editing.value = rule;
    modalOpen.value = true;
}

async function onSaved(name) {
    toast.success(editing.value ? `Remise « ${name} » mise à jour.` : `Remise « ${name} » créée.`);
    modalOpen.value = false;
    await load();
}

async function onToggle(rule, running) {
    try {
        await setActive(rule.id, running);
        toast.success(running ? `Remise « ${rule.name} » lancée à partir d'aujourd'hui.` : `Remise « ${rule.name} » arrêtée : elle s'est terminée hier.`);
    } catch (error) {
        toast.error(error.message);
    }
    await load();
}

async function onRemove(rule) {
    await remove(rule.id);
    toast.success(`Remise « ${rule.name} » supprimée.`);
    await load();
}

const requestedRuleId = new URLSearchParams(window.location.search).get('rule');

function openRequestedRule() {
    if (!requestedRuleId) {
        return;
    }
    const rule = rules.value.find((candidate) => candidate.id === requestedRuleId);
    if (rule) {
        openEdit(rule);
    } else {
        toast.error('Cette remise n\'existe plus.');
    }
}

onMounted(async () => {
    await Promise.all([load(), loadProducts(), loadTypes()]);
    openRequestedRule();
});
</script>

<template>
    <AppLayout title="Remises">
        <template #actions>
            <BaseButton @click="openCreate">Nouvelle remise</BaseButton>
        </template>

        <p class="discounts-page__intro">
            Une remise combine des conditions (une quantité d'un type ou d'un produit précis) et une action : prix fixe, remise en € ou en %.
            Elles s'appliquent automatiquement aux commandes passées pendant leur période de validité, la plus avantageuse d'abord, et plusieurs remises peuvent se cumuler sur une commande.
        </p>
        <BaseCard>
            <DiscountRuleList
                :rules="rules"
                :products="products"
                :selected-id="modalOpen ? editing?.id ?? null : null"
                @edit="openEdit"
                @toggle="onToggle"
                @remove="onRemove"
            />
        </BaseCard>

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <DiscountRuleForm :rule="editing" :products="products" :types="types" :submit="submit" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.discounts-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); max-width: 70ch; }
</style>
