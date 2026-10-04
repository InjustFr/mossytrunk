<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '../layouts/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import DiscountRuleForm from '../components/discounts/DiscountRuleForm.vue';
import DiscountRuleList from '../components/discounts/DiscountRuleList.vue';
import { useDiscountRules } from '../composables/useDiscountRules.js';
import { useProducts } from '../composables/useProducts.js';
import { useProductTypes } from '../composables/useProductTypes.js';
import { takeQuery } from '../composables/useQueryState.js';
import { useToast } from '../composables/useToast.js';

const { rules, currentRules, pastRules, load, create, update, setActive, remove } = useDiscountRules();
const { articles: products, load: loadProducts } = useProducts();
const { types, load: loadTypes } = useProductTypes();
const toast = useToast();
const { t } = useI18n();
const modalOpen = ref(false);
const editing = ref(null);

const modalTitle = computed(() => (editing.value ? t('discounts.page.edit') : t('discounts.page.new')));
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
    toast.success(t(editing.value ? 'discounts.page.updated' : 'discounts.page.created', { name }));
    modalOpen.value = false;
    await load();
}

async function onToggle(rule, running) {
    await toast.attempt(() => setActive(rule.id, running), t(running ? 'discounts.page.started' : 'discounts.page.stopped', { name: rule.name }));
    await load();
}

async function onRemove(rule) {
    await remove(rule.id);
    toast.success(t('discounts.page.removed', { name: rule.name }));
    await load();
}

const requestedRuleId = takeQuery('rule');

function openRequestedRule() {
    if (!requestedRuleId) {
        return;
    }
    const rule = rules.value.find((candidate) => candidate.id === requestedRuleId);
    if (rule) {
        openEdit(rule);
    } else {
        toast.error(t('discounts.page.notFound'));
    }
}

onMounted(async () => {
    await Promise.all([load(), loadProducts(), loadTypes()]);
    openRequestedRule();
});
</script>

<template>
    <AppLayout :title="t('discounts.page.title')">
        <template #actions>
            <BaseButton @click="openCreate">{{ t('discounts.page.new') }}</BaseButton>
        </template>

        <p class="discounts-page__intro">
            {{ t('discounts.page.intro') }}
        </p>
        <div class="discounts-page__sections">
            <BaseCard :title="t('discounts.page.current')">
                <DiscountRuleList
                    :rules="currentRules"
                    :products="products"
                    :types="types"
                    :selected-id="modalOpen ? editing?.id ?? null : null"
                    @edit="openEdit"
                    @toggle="onToggle"
                    @remove="onRemove"
                />
            </BaseCard>
            <BaseCard v-if="pastRules.length" :title="t('discounts.page.past')">
                <DiscountRuleList
                    :rules="pastRules"
                    :products="products"
                    :types="types"
                    :selected-id="modalOpen ? editing?.id ?? null : null"
                    @edit="openEdit"
                    @remove="onRemove"
                />
            </BaseCard>
        </div>

        <BaseModal v-model:open="modalOpen" :title="modalTitle">
            <DiscountRuleForm :rule="editing" :products="products" :types="types" :submit="submit" @saved="onSaved" @cancel="modalOpen = false" />
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
.discounts-page__sections { display: flex; flex-direction: column; gap: var(--space-5); }
.discounts-page__intro { margin: 0 0 var(--space-4); color: var(--color-muted); max-width: 70ch; }
</style>
