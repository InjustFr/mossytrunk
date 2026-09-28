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

async function onToggle(rule, active) {
    await setActive(rule.id, active);
    toast.success(active ? `Remise « ${rule.name} » activée.` : `Remise « ${rule.name} » désactivée.`);
    await load();
}

async function onRemove(rule) {
    await remove(rule.id);
    toast.success(`Remise « ${rule.name} » supprimée.`);
    await load();
}

onMounted(() => Promise.all([load(), loadProducts(), loadTypes()]));
</script>

<template>
    <AppLayout title="Remises">
        <template #actions>
            <BaseButton @click="openCreate">Nouvelle remise</BaseButton>
        </template>

        <p class="discounts-page__intro">
            Les remises par lot s'appliquent automatiquement aux nouvelles commandes, en choisissant la combinaison la plus avantageuse pour le client.
            Une remise peut viser des types entiers (ex. Print + Sticker : « 3 articles pour 30 € ») et/ou des produits précis.
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
