<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import DiscountRuleForm from '../components/discounts/DiscountRuleForm.vue';
import DiscountRuleList from '../components/discounts/DiscountRuleList.vue';
import { useDiscountRules } from '../composables/useDiscountRules.js';
import { useProducts } from '../composables/useProducts.js';
import { useToast } from '../composables/useToast.js';

const { rules, load, create, update, setActive, remove } = useDiscountRules();
const { products, load: loadProducts } = useProducts();
const toast = useToast();
const editing = ref(null);

const submit = (payload) => (editing.value ? update(editing.value.id, payload) : create(payload));

async function onSaved(name) {
    toast.success(editing.value ? `Remise « ${name} » mise à jour.` : `Remise « ${name} » créée.`);
    editing.value = null;
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
    if (editing.value?.id === rule.id) {
        editing.value = null;
    }
    await load();
}

onMounted(() => Promise.all([load(), loadProducts()]));
</script>

<template>
    <AppLayout title="Remises">
        <div class="discounts-page">
            <div class="discounts-page__list">
                <p class="discounts-page__intro">
                    Les remises par lot s'appliquent automatiquement aux nouvelles commandes, en choisissant la combinaison la plus avantageuse pour le client.
                </p>
                <DiscountRuleList
                    :rules="rules"
                    :selected-id="editing?.id ?? null"
                    @edit="editing = $event"
                    @toggle="onToggle"
                    @remove="onRemove"
                />
            </div>
            <BaseCard class="discounts-page__form">
                <DiscountRuleForm :rule="editing" :products="products" :submit="submit" @saved="onSaved" @cancel="editing = null" />
            </BaseCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.discounts-page {
    display: grid;
    grid-template-columns: minmax(0, 3fr) minmax(320px, 2fr);
    gap: var(--space-4);
    align-items: start;
}

.discounts-page__list { display: flex; flex-direction: column; gap: var(--space-3); }
.discounts-page__intro { margin: 0; color: var(--color-muted); }

@media (max-width: 900px) {
    .discounts-page { grid-template-columns: 1fr; }
}
</style>
